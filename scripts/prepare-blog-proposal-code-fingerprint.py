from pathlib import Path
import hashlib
import json

root=Path(__file__).resolve().parents[1]
inventory=json.loads((root/'docs/qa/blog-proposal-site-code-inventory-2026-10-26.json').read_text())
live=json.loads((root/'docs/qa/blog-proposal-destination-before-2026-10-26.json').read_text())['result']['profile']
expected={'plugins':{name:{'version':p['availableVersion'],'mainSha256':p['mainSha256']}for name,p in inventory['plugins'].items()},
    'muPluginSha256':live['muPluginSha256'],'theme':live['theme'],'wpdbSourceSha256':live['wpdbSourceSha256'],
    'fixtureMuSha256':hashlib.sha256((root/'tests/integration/wordpress-blog-proposals-site-profile/guard.php').read_bytes()).hexdigest()}
template=(root/'wordpress/operations/blog-proposal-code-fingerprint.template.php').read_text()
result=template.replace('__EXPECTED_PROFILE_JSON__',json.dumps(expected,separators=(',',':')))
(root/'wordpress/operations/blog-proposal-code-fingerprint.php').write_text(result)
print('Prepared standalone code fingerprint for 16 plugin main files; no deployment')
