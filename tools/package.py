#!/usr/bin/env python3
"""Create an allowlisted release ZIP; never include local settings or caches."""
from pathlib import Path
import hashlib,json,re,subprocess,sys,zipfile
ROOT=Path(__file__).resolve().parents[1]
def main():
    subprocess.run([sys.executable,str(ROOT/'tools/check.py')],check=True)
    version=(ROOT/'VERSION').read_text().strip()
    assert re.fullmatch(r'\d+\.\d+\.\d+',version),'Invalid VERSION'
    release=ROOT/'releases';release.mkdir(exist_ok=True)
    target=release/f'{ROOT.name}-v{version}.zip'
    with zipfile.ZipFile(target,'x',zipfile.ZIP_DEFLATED) as archive:
        for name in json.loads((ROOT/'release-files.json').read_text()):
            path=ROOT/name
            assert path.resolve().is_relative_to(ROOT) and not path.is_symlink()
            archive.write(path,name)
    checksum=hashlib.sha256(target.read_bytes()).hexdigest()
    target.with_suffix('.zip.sha256').write_text(f'{checksum}  {target.name}\n')
    print(f'Release: {target}\nSHA-256: {checksum}')
if __name__=='__main__':main()
