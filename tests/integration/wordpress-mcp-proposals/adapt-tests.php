<?php
// Test adapter only; never transforms the packaged candidate.
if (getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1') { throw new RuntimeException('SYNTHETIC_ACCEPTANCE_REQUIRED'); }
$transport = getenv('AC_PROPOSALS_TRANSPORT') === 'synthetic-http-v1';
foreach ($transport ? ['http', 'stack', 'http-barrier'] : ['check', 'history', 'quotas'] as $name) {
    $source = file_get_contents('/base-tests/' . $name . '.php');
    $source = str_replace('AlexCuadra\\EditorialChanges', 'AlexCuadra\\EditorialProposals', $source);
    $source = str_replace("home_url() !== 'http://wordpress'", "home_url() !== 'https://content.alexcuadra.dev' || getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1'", $source);
    $source = str_replace('ac_change_', 'ac_proposal_', $source);
    if ($transport) {
        if (getenv('AC_PROPOSALS_PROFILE') === 'backup-2026-10-22') {
            $source = str_replace("'wp-graphql'=>'2.24.2'", "'wp-graphql'=>'2.25.0'", $source);
            $source = str_replace("'wp-graphql-polylang'=>'0.7.1'", "'wp-graphql-polylang-0.7.1'=>'0.7.1'", $source);
        }
        $source = str_replace('alexcuadra-editorial-changes/alexcuadra-editorial-changes.php', 'alexcuadra-editorial-proposals/alexcuadra-editorial-proposals.php', $source);
        $source = str_replace("'polylang'=>'3.9.0'", "'polylang'=>'3.9.1'", $source);
        $source = str_replace('acProjectChange|projectChange|ac_change|editorialChange', 'acPrivateProposal|privateProposal|ac_private_proposal|acProjectChange|projectChange|ac_change|editorialChange', $source);
        $source = str_replace('editorial_change_payload', 'private_proposal_payload', $source);
        $source = str_replace('acProjectChanges{', 'acPrivateProposals{', $source);
    }
    if (str_contains($source, 'EditorialChanges') || str_contains($source, "home_url() !== 'http://wordpress'") || str_contains($source, 'ac_change_requests')) { throw new RuntimeException('ADAPTER_INCOMPLETE'); }
    file_put_contents('/tmp/proposals-' . $name . '.php', $source);
}
