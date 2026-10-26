<?php
/** Isolated SQLite laboratory only. Not a WordPress adapter or installable plugin. */
namespace AlexCuadra\EditorialBlogProposalLab;
require_once __DIR__ . '/planner.php';

final class Repository {
    private const LIMITS = ['proposals' => 16, 'revisions' => 64, 'requests' => 128,
        'bytes' => 8388608, 'document' => 786432, 'versions' => 16];
    private \PDO $db;
    private array $limits;
    public function __construct(\PDO $db, array $limits = []) {
        if (PHP_SAPI !== 'cli' || defined('ABSPATH') || !defined('AC_BLOG_PROPOSALS_STORAGE_LAB')
            || AC_BLOG_PROPOSALS_STORAGE_LAB !== true || $db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'sqlite') { reject('storage_lab_only'); }
        foreach ($limits as $key => $value) {
            if (!isset(self::LIMITS[$key]) || !positive($value) || $value > self::LIMITS[$key]) { reject('limits'); }
        }
        $this->limits = array_replace(self::LIMITS, $limits); $this->db = $db;
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys=ON'); $db->exec('PRAGMA busy_timeout=2000');
        if ((int)$db->query('PRAGMA foreign_keys')->fetchColumn() !== 1) { reject('foreign_keys'); }
    }
    private function sql(string $sql, array $values = []) {
        $query = $this->db->prepare($sql); $query->execute($values); return $query;
    }
    private function transaction(callable $action) {
        if ($this->db->inTransaction()) { reject('outer_transaction'); }
        $entered = false;
        try {
            $this->db->exec('BEGIN IMMEDIATE'); $entered = true;
            $result = $action();
            $this->db->exec('COMMIT'); $entered = false; return $result;
        } catch (\Throwable $e) {
            if ($entered) { $this->db->exec('ROLLBACK'); }
            if ($e instanceof \PDOException) {
                reject(in_array((int)($e->errorInfo[1] ?? 0), [5, 6], true) ? 'storage_busy' : 'storage_failure');
            }
            throw $e;
        }
    }
    /** Explicit fresh dedicated database setup. No upgrades or table replacement. */
    public function install(): void {
        $this->transaction(function () {
            if ($this->db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetchColumn() != 0) { reject('storage_not_empty'); }
            $this->db->exec('CREATE TABLE blog_proposals (
                id INTEGER PRIMARY KEY AUTOINCREMENT, owner_id INTEGER NOT NULL CHECK(owner_id>0),
                version INTEGER NOT NULL CHECK(version>0), UNIQUE(id,owner_id))');
            $this->db->exec('CREATE TABLE blog_revisions (
                proposal_id INTEGER NOT NULL, owner_id INTEGER NOT NULL, version INTEGER NOT NULL CHECK(version>0),
                document TEXT NOT NULL, document_hash TEXT NOT NULL,
                PRIMARY KEY(proposal_id,version), UNIQUE(proposal_id,owner_id,version),
                FOREIGN KEY(proposal_id,owner_id) REFERENCES blog_proposals(id,owner_id))');
            $this->db->exec('CREATE TABLE blog_requests (
                owner_id INTEGER NOT NULL, request_id TEXT NOT NULL, request_hash TEXT NOT NULL,
                proposal_id INTEGER NOT NULL, version INTEGER NOT NULL,
                PRIMARY KEY(owner_id,request_id),
                FOREIGN KEY(proposal_id,owner_id,version) REFERENCES blog_revisions(proposal_id,owner_id,version))');
            $this->db->exec('PRAGMA user_version=1');
        });
    }
    private function readDocument(int $owner, int $id, int $version): array {
        $row = $this->sql('SELECT document,document_hash FROM blog_revisions WHERE owner_id=? AND proposal_id=? AND version=?', [$owner,$id,$version])->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { reject('not_found'); }
        if (strlen($row['document']) > self::LIMITS['document']) { reject('corrupt_document'); }
        try { $doc = json_decode($row['document'], true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { reject('corrupt_document'); }
        if (!is_array($doc) || !is_string($doc['document_hash'] ?? null)) { reject('corrupt_document'); }
        $hash = $doc['document_hash']; unset($doc['document_hash']);
        if (!hash_equals($row['document_hash'], $hash) || !hash_equals(digest($doc), $hash)
            || ($doc['owner_id'] ?? null) !== $owner || ($doc['version'] ?? null) !== $version
            || ($doc['proposal_id'] ?? null) !== $id) { reject('corrupt_document'); }
        $doc['document_hash'] = $hash;
        return ['proposal_id' => $id, 'version' => $version, 'document' => $doc];
    }
    private function head(int $owner, int $id): int {
        $value = $this->sql('SELECT version FROM blog_proposals WHERE owner_id=? AND id=?', [$owner,$id])->fetchColumn();
        if ($value === false) { reject('not_found'); } return (int)$value;
    }
    private function replay(int $owner, string $key, string $hash): ?array {
        $row = $this->sql('SELECT request_hash,proposal_id,version FROM blog_requests WHERE owner_id=? AND request_id=?', [$owner,$key])->fetch(\PDO::FETCH_ASSOC);
        if (!$row) { return null; }
        if (!hash_equals($row['request_hash'], $hash)) { reject('request_reused'); }
        return $this->readDocument($owner, (int)$row['proposal_id'], (int)$row['version']);
    }
    private function quota(int $owner, int $bytes, bool $create, int $version): void {
        $proposals = (int)$this->sql('SELECT COUNT(*) FROM blog_proposals WHERE owner_id=?', [$owner])->fetchColumn();
        $requests = (int)$this->sql('SELECT COUNT(*) FROM blog_requests WHERE owner_id=?', [$owner])->fetchColumn();
        $usage = $this->sql('SELECT COUNT(*) AS n,COALESCE(SUM(length(CAST(document AS BLOB))),0) AS bytes FROM blog_revisions WHERE owner_id=?', [$owner])->fetch(\PDO::FETCH_ASSOC);
        if ($proposals + (int)$create > $this->limits['proposals'] || $requests + 1 > $this->limits['requests']
            || (int)$usage['n'] + 1 > $this->limits['revisions'] || $version > $this->limits['versions']
            || $bytes > $this->limits['document']
            || (int)$usage['bytes'] + $bytes + ($requests+1)*256 + ($proposals+(int)$create)*128 > $this->limits['bytes']) { reject('quota'); }
    }
    /** An update replaces the proposed patch against a fresh source, not a patch of the prior patch. */
    private function save(array $actor, array $source, array $terms, array $request, ?int $id, ?int $expected): array {
        authorize_actor($actor); $request = normalize_request($request);
        $owner = $actor['id'];
        $hash = digest(['kind' => $id === null ? 'create' : 'update', 'owner_id' => $owner,
            'proposal_id' => $id, 'expected_version' => $expected, 'request' => $request]);
        return $this->transaction(function () use ($actor,$source,$terms,$request,$id,$expected,$owner,$hash) {
            $replay = $this->replay($owner, $request['request_id'], $hash);
            if ($replay !== null) { return $replay; }
            $version = 1;
            if ($id !== null) {
                $current = $this->head($owner, $id);
                if ($current !== $expected) { reject('version_conflict'); }
                $old = $this->readDocument($owner,$id,$current)['document'];
                foreach (['source_id','locale','operation'] as $key) {
                    if ($old[$key] !== $request[$key]) { reject('identity_change'); }
                }
                $version = $current + 1;
            }
            $doc = prepare($actor, $source, $terms, $request);
            // Reserve the largest integer ID before quota admission; actual ID can only shrink it.
            $doc['proposal_id'] = $id ?? PHP_INT_MAX;
            $doc['version'] = $version; unset($doc['document_hash']); $doc['document_hash'] = digest($doc);
            $json = json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->quota($owner, strlen($json), $id === null, $version);
            if ($id === null) {
                $this->sql('INSERT INTO blog_proposals(owner_id,version) VALUES (?,1)', [$owner]);
                $id = (int)$this->db->lastInsertId();
                $doc['proposal_id'] = $id; unset($doc['document_hash']); $doc['document_hash'] = digest($doc);
                $json = json_encode($doc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $affected = $this->sql('UPDATE blog_proposals SET version=? WHERE id=? AND owner_id=? AND version=?', [$version,$id,$owner,$expected])->rowCount();
                if ($affected !== 1) { reject('version_conflict'); }
            }
            $this->sql('INSERT INTO blog_revisions(proposal_id,owner_id,version,document,document_hash) VALUES (?,?,?,?,?)', [$id,$owner,$version,$json,$doc['document_hash']]);
            $this->sql('INSERT INTO blog_requests(owner_id,request_id,request_hash,proposal_id,version) VALUES (?,?,?,?,?)', [$owner,$request['request_id'],$hash,$id,$version]);
            return $this->readDocument($owner,$id,$version);
        });
    }
    public function create(array $actor, array $source, array $terms, array $request): array {
        return $this->save($actor,$source,$terms,$request,null,null);
    }
    public function update(array $actor, int $id, int $expectedVersion, array $source, array $terms, array $request): array {
        authorize_actor($actor);
        if (!positive($id) || !positive($expectedVersion)) { reject('input'); }
        return $this->save($actor,$source,$terms,$request,$id,$expectedVersion);
    }
    public function get(array $actor, int $id, ?int $version = null): array {
        authorize_actor($actor);
        if (!positive($id) || ($version !== null && !positive($version))) { reject('input'); }
        return $this->transaction(function () use ($actor,$id,$version) {
            $head = $this->head($actor['id'],$id);
            return $this->readDocument($actor['id'],$id,$version ?? $head);
        });
    }
    /** Bounded owner inventory; no content returned. ID cursor rather than changing offsets. */
    public function listing(array $actor, int $after = 0, int $limit = 10): array {
        authorize_actor($actor);
        if ($after < 0 || $limit < 1 || $limit > 20) { reject('input'); }
        return $this->transaction(function () use ($actor,$after,$limit) {
        $query = $this->db->prepare('SELECT id,version FROM blog_proposals WHERE owner_id=:owner AND id>:after ORDER BY id LIMIT :limit');
        foreach ([':owner'=>$actor['id'],':after'=>$after,':limit'=>$limit+1] as $key=>$value) { $query->bindValue($key,$value,\PDO::PARAM_INT); }
        $query->execute(); $rows = $query->fetchAll(\PDO::FETCH_ASSOC); $more = count($rows)>$limit; $rows = array_slice($rows,0,$limit);
        return ['items'=>array_map(fn($r)=>['proposal_id'=>(int)$r['id'],'version'=>(int)$r['version']],$rows),
            'next_after'=>$more?(int)$rows[count($rows)-1]['id']:null];
        });
    }
}
