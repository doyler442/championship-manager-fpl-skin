#!/usr/bin/env python3
"""Run focused local checks without downloading or publishing anything."""
from pathlib import Path
import json,os,re,subprocess,tempfile
ROOT=Path(__file__).resolve().parents[1]
def run(*args):
    subprocess.run(args,cwd=ROOT,check=True)
def main():
    php=os.environ.get('PHP_BIN','php');node=os.environ.get('NODE_BIN','node')
    for path in sorted(ROOT.glob('*.php')):run(php,'-l',str(path))
    run(php,str(ROOT/'tests/regression.php'))
    run(node,str(ROOT/'tests/ui-rules.cjs'))
    for path in ROOT.glob('*.js'):run(node,'--check',str(path))
    text=(ROOT/'index.html').read_text()
    for script in re.findall(r'<script\b[^>]*>(.*?)</script>',text,re.S|re.I):
        if script.strip():
            with tempfile.NamedTemporaryFile(mode='w',suffix='.js') as f:
                f.write(script);f.flush();run(node,'--check',f.name)
    for asset in re.findall(r'''(?:src|href)=["']([^"']+)["']''',text):
        if any(x in asset for x in ['${','://','data:','#']):continue
        asset=asset.split('?')[0]
        assert not asset.startswith('/'),f'Root-relative asset: {asset}'
        assert (ROOT/asset).is_file(),f'Missing asset: {asset}'
    for asset in re.findall(r'''["'](images/[^"']+)["']''',text):
        assert (ROOT/asset).is_file(),f'Missing background: {asset}'
    manifest=json.loads((ROOT/'release-files.json').read_text())
    for name in manifest:
        path=ROOT/name
        assert path.is_file() and not path.is_symlink(),f'Invalid release file: {name}'
        assert name not in ['config.local.json','data/fallback_snapshot.json'],f'Private file listed: {name}'
    # Optional local privacy check; private marker values stay outside the repository.
    markers=[x for x in os.environ.get('FPL_PRIVATE_MARKERS','').split('|') if x]
    for name in manifest:
        path=ROOT/name
        if path.suffix in ['.jpg','.png','.ttf']:continue
        contents=path.read_text()
        assert not any(m.casefold() in contents.casefold() for m in markers),f'Private marker in {name}'
    print('PASS: PHP, scoring/cache regression, JavaScript, asset paths and release allowlist')
if __name__=='__main__':main()
