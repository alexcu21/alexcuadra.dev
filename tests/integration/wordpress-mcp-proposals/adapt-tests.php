<?php
// Test adapter only; never transforms the packaged candidate.
if (getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1') { throw new RuntimeException('SYNTHETIC_ACCEPTANCE_REQUIRED'); }
foreach (['check', 'history', 'quotas'] as $name) {
    $source = file_get_contents('/base-tests/' . $name . '.php');
    $source = str_replace('AlexCuadra\\EditorialChanges', 'AlexCuadra\\EditorialProposals', $source);
    $source = str_replace("home_url() !== 'http://wordpress'", "home_url() !== 'https://content.alexcuadra.dev' || getenv('AC_PROPOSALS_ACCEPTANCE') !== 'synthetic-v1'", $source);
    $source = str_replace('ac_change_requests', 'ac_proposal_requests', $source);
    if (str_contains($source, 'EditorialChanges') || str_contains($source, 'http://wordpress') || str_contains($source, 'ac_change_requests')) { throw new RuntimeException('ADAPTER_INCOMPLETE'); }
    file_put_contents('/tmp/proposals-' . $name . '.php', $source);
}
