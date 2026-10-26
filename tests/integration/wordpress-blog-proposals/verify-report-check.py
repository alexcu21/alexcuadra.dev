"""Exercise the acceptance gate without repeating WordPress integration."""
import copy
import json
from pathlib import Path
import subprocess
import sys

verifier = str(Path(__file__).with_name('verify-report.py'))
wp = 'EDITORIAL_BLOG_PROPOSAL_WP_RESULT='
fault = 'EDITORIAL_BLOG_PROPOSAL_FAULT_RESULT='
http = 'EDITORIAL_BLOG_PROPOSAL_HTTP_RESULT='
reports = {
    wp: dict(passed=1, failed=0, cases=['synthetic_case'], concurrentWordPressProcesses=True, syntheticScopesRemoved=True, sourceFingerprintsPreservedDuringProposalOperations=True),
    fault: dict(passed=1, failed=0, cases=['synthetic_case'], connectionLossVerified=True, processKillVerified=True, syntheticCapabilitiesRemoved=True),
    http: dict(passed=1, failed=0, cases=['synthetic_case'], httpTransportVerified=True, syntheticCredentialsRevoked=True, syntheticCapabilitiesRemoved=True),
}
cases = []
def verify(name, marker, text, expected):
    result = subprocess.run([sys.executable, verifier, marker], input=text, capture_output=True, text=True)
    if (result.returncode == 0) != expected:
        raise RuntimeError('VERIFIER_FAILED:' + name)
    cases.append(name)
for marker, report in reports.items():
    verify('valid_' + marker.split('_')[-2].lower(), marker, marker + json.dumps(report), True)
base = reports[http]
for name, patch in [
    ('declared_failure', {'failed': 1}), ('boolean_failure_count', {'failed': False}),
    ('count_mismatch', {'passed': 2}), ('boolean_passed_count', {'passed': True}),
    ('empty_cases', {'passed': 0, 'cases': []}), ('cases_not_array', {'cases': {}}),
    ('duplicate_cases', {'passed': 2, 'cases': ['synthetic_case', 'synthetic_case']}),
    ('unsafe_case_identifier', {'cases': ['raw error or private value']}),
    ('transport_not_verified', {'httpTransportVerified': False}),
    ('cleanup_not_verified', {'syntheticCredentialsRevoked': False}),
]:
    report = dict(base, **patch)
    verify(name, http, http + json.dumps(report), False)
report = copy.deepcopy(base)
del report['syntheticCapabilitiesRemoved']
verify('missing_cleanup', http, http + json.dumps(report), False)
verify('missing_marker', http, json.dumps(base), False)
verify('duplicate_marker', http, http + json.dumps(base) + '\n' + http + json.dumps(base), False)
verify('invalid_json', http, http + '{', False)
verify('report_not_object', http, http + '[]', False)
print(json.dumps(dict(passed=len(cases), failed=0, cases=cases), indent=2))
