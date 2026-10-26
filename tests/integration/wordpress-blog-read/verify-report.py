"""Reject missing/partial reports even if WP-CLI incorrectly exits zero."""
import json
import sys

marker = sys.argv[1]
lines = [line[len(marker):] for line in sys.stdin.read().splitlines() if line.startswith(marker)]
try:
    if len(lines) != 1:
        raise ValueError('marker')
    report = json.loads(lines[0])
    if report.get('failed') != 0 or not isinstance(report.get('cases'), list):
        raise ValueError('failure')
    if report.get('passed', 0) <= 0 or report['passed'] != len(report['cases']):
        raise ValueError('count')
    if marker == 'EDITORIAL_BLOG_HTTP_RESULT=' and any(report.get(key) is not True for key in
        ['httpTransportVerified', 'syntheticCredentialsRevoked', 'syntheticCapabilitiesRemoved']):
        raise ValueError('transport or cleanup')
except (ValueError, TypeError, KeyError):
    sys.exit('INCOMPLETE_OR_FAILED_BLOG_LAB_REPORT')
print(marker + json.dumps(report, separators=(',', ':')))
