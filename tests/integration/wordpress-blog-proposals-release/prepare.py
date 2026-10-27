from pathlib import Path
import json

root=Path(__file__).resolve().parents[3]
source=root/'tests/integration/wordpress-blog-proposals'
target=root/'node_modules/.cache/editorial-blog-proposals-release'
target.mkdir(parents=True,exist_ok=True)
names=['check.php','worker.php','failure-worker.php','failures.php','http-client.php','http.php']
for name in names:
    text=(source/name).read_text().replace('AlexCuadra\\EditorialBlogProposalLab','AlexCuadra\\EditorialBlogProposals')
    text=text.replace('ac_blog_lab_','ac_blog_proposal_').replace('/blog-proposals/','/var/www/html/wp-content/plugins/alexcuadra-editorial-blog-proposals/')
    text=text.replace('/proposal-lab/','/package-lab/').replace('alexcuadra-lab/','alexcuadra-blog/').replace('wordpress_lab_guard','wordpress_guard')
    (target/name).write_text(text)
print(json.dumps({'adaptedTests':names,'usesInstalledPackage':True}))
