<?php
/** SQLite protocol experiment. Not a WordPress DB adapter or deployable executor. */
namespace AlexCuadra\EditorialSourceExperiment;

final class EffectJournal {
    private $db;
    private $clock;
    public function __construct(\PDO $db, callable $clock) {
        if (!defined('AC_EDITORIAL_SOURCE_EXPERIMENT') || AC_EDITORIAL_SOURCE_EXPERIMENT !== true
            || wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress'
            || $db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'sqlite') { throw new \RuntimeException('sqlite_experiment_only'); }
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db = $db; $this->clock = $clock;
    }
    private function outside() {
        if (wp_get_environment_type() !== 'local' || home_url() !== 'http://wordpress') { throw new \RuntimeException('sqlite_experiment_only'); }
        if ($this->db->inTransaction()) { throw new \RuntimeException('outer_transaction'); }
    }
    private function query($sql, $args = []) {
        $s = $this->db->prepare($sql); $s->execute($args); return $s;
    }
    /** Explicit experimental DDL; never called by constructor or installed in WP. */
    public function install() {
        $this->outside();
        $this->db->exec('CREATE TABLE exp_events (event_id TEXT PRIMARY KEY, event_hash TEXT NOT NULL,
            project_id INTEGER NOT NULL, operation TEXT NOT NULL, receipt TEXT NOT NULL)');
        $this->db->exec("CREATE TABLE exp_effects (event_id TEXT PRIMARY KEY, project_id INTEGER NOT NULL,
            kind TEXT NOT NULL CHECK(kind='project-cache-purge'), status TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0, available_at INTEGER NOT NULL,
            token TEXT, lease_until INTEGER, last_outcome TEXT)");
    }
    /** All writes in $writeFixture must use THIS SQLite connection. Not WP APIs. */
    public function record($event, callable $writeFixture) {
        $this->outside();
        if (!is_array($event) || array_diff(array_keys($event), ['event_id','event_hash','project_id','operation'])
            || !is_string($event['event_id'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $event['event_id'])
            || !is_string($event['event_hash'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $event['event_hash'])
            || !is_int($event['project_id'] ?? null) || $event['project_id'] < 1
            || !in_array($event['operation'] ?? null, ['apply','recover'], true)) { throw new \RuntimeException('invalid_event'); }
        $this->db->beginTransaction();
        try {
            $prior = $this->query('SELECT * FROM exp_events WHERE event_id=?', [$event['event_id']])->fetch(\PDO::FETCH_ASSOC);
            if ($prior) {
                if ($prior['event_hash'] !== $event['event_hash'] || (int)$prior['project_id'] !== $event['project_id']
                    || $prior['operation'] !== $event['operation']) { throw new \RuntimeException('event_reused'); }
                return json_decode($prior['receipt'], true, 512, JSON_THROW_ON_ERROR);
            }
            // Claim the event before any fixture source/approval/receipt writes.
            $this->query('INSERT INTO exp_events VALUES (?,?,?,?,?)', [$event['event_id'],$event['event_hash'],$event['project_id'],$event['operation'],'']);
            $receipt = $writeFixture($this->db);
            if (!is_array($receipt)) { throw new \RuntimeException('invalid_receipt'); }
            $encoded = json_encode($receipt, JSON_THROW_ON_ERROR);
            if (strlen($encoded) > 65536) { throw new \RuntimeException('receipt_too_large'); }
            $this->query('UPDATE exp_events SET receipt=? WHERE event_id=?', [$encoded,$event['event_id']]);
            $this->query("INSERT INTO exp_effects (event_id,project_id,kind,status,available_at)
                VALUES (?,?,'project-cache-purge','pending',?)", [$event['event_id'],$event['project_id'],($this->clock)()]);
            if (!$this->db->commit()) { throw new \RuntimeException('commit_unknown'); }
            // Never purge here. A separate connection/process reads committed rows.
            return $receipt;
        } finally {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
        }
    }
    public function claim($eventId) {
        $this->outside(); $now = ($this->clock)(); $token = bin2hex(random_bytes(16));
        // Atomic CAS: SQLite serializes writers; only one claimant gets rowCount=1.
        $changed = $this->query("UPDATE exp_effects SET status='running',attempts=attempts+1,token=?,lease_until=?
            WHERE event_id=? AND attempts<8 AND EXISTS (SELECT 1 FROM exp_events e
                WHERE e.event_id=exp_effects.event_id AND e.project_id=exp_effects.project_id AND e.receipt<>'') AND ((status IN ('pending','retry') AND available_at<=?)
            OR (status='running' AND lease_until<=?))", [$token,$now+30,$eventId,$now,$now])->rowCount();
        if ($changed !== 1) {
            $this->query("UPDATE exp_effects SET status='attention',token=NULL,lease_until=NULL,last_outcome='attempt_limit'
                WHERE event_id=? AND attempts>=8 AND ((status='running' AND lease_until<=?)
                OR (status='retry' AND available_at<=?))", [$eventId,$now,$now]);
            return null;
        }
        return $this->query('SELECT * FROM exp_effects WHERE event_id=? AND token=?', [$eventId,$token])->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    public function settle(array $claim, $outcome) {
        $this->outside(); $now = ($this->clock)();
        if (!in_array($outcome, ['accepted','retry','uncertain'], true)) { throw new \RuntimeException('invalid_purge_outcome'); }
        $status = $outcome === 'accepted' ? 'accepted' : ((int)$claim['attempts'] >= 8 ? 'attention' : 'retry');
        $delay = min(300, 2 ** min(8, (int)$claim['attempts']));
        $count = $this->query('UPDATE exp_effects SET status=?,last_outcome=?,available_at=?,token=NULL,lease_until=NULL
            WHERE event_id=? AND status=\'running\' AND token=? AND lease_until>?',
            [$status,$outcome,$now+$delay,$claim['event_id'],$claim['token'],$now])->rowCount();
        return $count === 1 ? $status : 'lease_lost';
    }
    public function consume($eventId, callable $purge) {
        $claim = $this->claim($eventId);
        if (!$claim) { return 'not_claimed'; }
        // A lease can expire while the external API runs: execution is at-least-once.
        // The stable key identifies the same logical effect across all attempts.
        try {
            $outcome = $purge((int)$claim['project_id'], hash('sha256', $claim['event_id'].'|'.$claim['kind'].'|'.$claim['project_id']));
            if (!in_array($outcome, ['accepted','retry','uncertain'], true)) { $outcome = 'uncertain'; }
        } catch (\Throwable $e) { $outcome = 'uncertain'; }
        return $this->settle($claim, $outcome);
    }
    public function effect($eventId) {
        $this->outside();
        return $this->query('SELECT * FROM exp_effects WHERE event_id=?', [$eventId])->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
