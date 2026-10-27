import json
import sys
phase, expected, status = sys.argv[1:]
marker='BLOG_PROPOSAL_PREFLIGHT_RESULT='
lines=[line[len(marker):] for line in sys.stdin.read().splitlines() if line.startswith(marker)]
try:
    if len(lines)!=1: raise ValueError('marker')
    report=json.loads(lines[0])
    if report['phase']!=phase or report['mutationsRequested'] is not False: raise ValueError('phase')
    if expected=='ready':
        if status!='0' or report['ready'] is not True or any(value is not True for value in report['checks'].values()): raise ValueError('not ready')
    elif expected=='blocked':
        if status=='0' or report['ready'] is not False or report['checks']['expectedSchema'] is not False: raise ValueError('not blocked')
    else: raise ValueError('expectation')
except (ValueError,KeyError,TypeError):sys.exit('INCOMPLETE_RELEASE_PREFLIGHT')
print('RELEASE_PREFLIGHT_VERIFIED='+phase+':'+expected)
