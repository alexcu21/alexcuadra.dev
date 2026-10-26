import json
import re
import sys

requirements = {
    'EDITORIAL_BLOG_PROPOSAL_WP_RESULT=': ['concurrentWordPressProcesses', 'syntheticScopesRemoved', 'sourceFingerprintsPreservedDuringProposalOperations'],
    'EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT=': ['connectionLossVerified', 'processKillVerified', 'syntheticCapabilitiesRemoved'],
    'EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT=': ['httpTransportVerified', 'syntheticCredentialsRevoked', 'syntheticCapabilitiesRemoved'],
}
marker = sys.argv[1]
lines = [line[len(marker):] for line in sys.stdin.read().splitlines() if line.startswith(marker)]
try:
    if marker not in requirements or len(lines) != 1:
        raise ValueError('marker')
    report = json.loads(lines[0])
    if not isinstance(report, dict):
        raise ValueError('report object')
    if type(report.get('failed')) is not int or report['failed'] != 0 or not isinstance(report.get('cases'), list) or type(report.get('passed')) is not int or report['passed'] <= 0 or report['passed'] != len(report['cases']):
        raise ValueError('checks')
    if any(not isinstance(case, str) or not re.fullmatch(r'[a-z0-9_-]+', case) for case in report['cases']) or len(set(report['cases'])) != len(report['cases']):
        raise ValueError('case identifiers')
    if any(report.get(key) is not True for key in requirements[marker]):
        raise ValueError('incomplete phase')
except (ValueError, KeyError, TypeError):
    sys.exit('INCOMPLETE_OR_FAILED_PROPOSAL_REPORT')
print(marker + json.dumps(report, separators=(',', ':')))
