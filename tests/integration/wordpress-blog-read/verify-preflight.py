import json
import sys
prefix='EDITORIAL_BLOG_PREFLIGHT_RESULT='
lines=[line[len(prefix):] for line in sys.stdin.read().splitlines() if line.startswith(prefix)]
try:
    if len(lines)!=1: raise ValueError()
    report=json.loads(lines[0])
    if report['phase']!=sys.argv[1] or report['ready'] is not True or report['mutationsRequested'] is not False: raise ValueError()
    if not report['checks'] or any(value is not True for value in report['checks'].values()): raise ValueError()
except (ValueError,KeyError,TypeError):
    sys.exit('FAILED_OR_INCOMPLETE_PACKAGE_PREFLIGHT')
print(prefix+json.dumps(report,separators=(',',':')))
