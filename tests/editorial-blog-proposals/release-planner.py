"""Run the unchanged planner cases against the derived package module."""
from pathlib import Path
import subprocess
import tempfile

root=Path(__file__).resolve().parents[2]
original=(root/'tests/editorial-blog-proposals/check.php').read_text()
old="require __DIR__ . '/../../wordpress/experimental/editorial-blog-proposals/planner.php';"
target=str(root/'wordpress/packages/alexcuadra-editorial-blog-proposals/planner.php')
assert original.count(old)==1
text=original.replace(old,"require '"+target.replace("'","\\'")+"';").replace('AlexCuadra\\EditorialBlogProposalLab','AlexCuadra\\EditorialBlogProposals')
with tempfile.TemporaryDirectory(prefix='blog-package-planner-') as folder:
    script=Path(folder)/'check.php';script.write_text(text)
    result=subprocess.run(['/opt/homebrew/bin/php',str(script)],check=True,text=True,capture_output=True)
    print(result.stdout,end='')
