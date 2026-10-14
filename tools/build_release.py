"""Reproducible RC.3 packages. Does not certify real WordPress or live APIs."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
from hashlib import sha256
from datetime import datetime,timezone
import json, subprocess
ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT.parent
V='1.0.0-rc.3'
PLUGIN=OUT/f'luxefind-search-pro-plugin-{V}.zip'
BUYER=OUT/f'luxefind-search-pro-codecanyon-buyer-package-{V}.zip'
SOURCE=OUT/f'luxefind-search-pro-developer-source-{V}.zip'
DEMO=OUT/f'luxefind-search-pro-simulated-demo-{V}.zip'
MANIFEST=OUT/f'luxefind-release-manifest-{V}.json'
plugins=[ROOT/'luxefind-search-pro.php',ROOT/'readme.txt',ROOT/'uninstall.php',ROOT/'LICENSE']+sorted((ROOT/'includes').glob('*.php'))+sorted((ROOT/'assets').glob('*.js'))+sorted((ROOT/'assets').glob('*.css'))
def add(z,file,name):
 assert file.is_file(),file
 z.write(file,name)
with ZipFile(PLUGIN,'w',ZIP_DEFLATED,compresslevel=9) as z:
 for f in plugins:add(z,f,'luxefind-search-pro/'+f.relative_to(ROOT).as_posix())
with ZipFile(BUYER,'w',ZIP_DEFLATED,compresslevel=9) as z:
 z.writestr('README-FIRST.txt',f'''LUXEFIND SEARCH PRO — {V}\n\nENGINEERING RELEASE CANDIDATE; NOT yet CodeCanyon-approved or production-certified.\n1) Upload only plugin/luxefind-search-pro.zip in WordPress Admin > Plugins.\n2) Read documentation/index.html.\n3) Insert [luxefind_search] in a page or the Gutenberg search block.\n4) Provider keys and subscriptions are supplied by the site operator, never bundled.\n5) Optional previews and demo in this archive are simulated, not live WordPress tests.\n\nIMPORTANT: As of 2026-10-14, real WP staging, paid provider APIs, security/load testing, brand clearance and marketplace review remain unverified. Consult documentation/STAGING-AND-RELEASE-QA.md.\n\nThis is an independent WordPress-native implementation; no original Seurch AGPL source copied or bundled.\n''')
 add(z,PLUGIN,'plugin/luxefind-search-pro.zip')
 for f in sorted((ROOT/'docs').glob('*')):
  if f.is_file():add(z,f,'documentation/'+f.name)
 add(z,ROOT/'LICENSE','licenses/GPL-3.txt')
 for f in sorted((ROOT/'marketplace').glob('*.png')):add(z,f,'marketing/'+f.name)
 for f in sorted((ROOT/'tests').glob('ui-*.png')):add(z,f,'qa/mock-'+f.name)
 add(z,ROOT/'tests/UI-RESULTS.md','qa/UI-RESULTS.md')
 add(z,ROOT/'tests/acceptance.php','qa/acceptance.php')
 add(z,ROOT/'tests/fuzz.php','qa/fuzz.php')
 add(z,ROOT/'.github/workflows/ci.yml','qa/source-ci.yml')
with ZipFile(DEMO,'w',ZIP_DEFLATED,compresslevel=9) as z:
 for f in sorted((ROOT/'demo').rglob('*')):
  if f.is_file():add(z,f,f.relative_to(ROOT/'demo').as_posix())
with ZipFile(SOURCE,'w',ZIP_DEFLATED,compresslevel=9) as z:
 for f in sorted(ROOT.rglob('*')):
  if f.is_file() and '.git' not in f.parts:add(z,f,'luxefind-search-pro/'+f.relative_to(ROOT).as_posix())
result={'version':V,'created_at_utc':datetime.now(timezone.utc).isoformat(),
 'source_origin':'independent WordPress-native implementation inspired by metasearch concepts; not a Seurch code port',
 'tests':'PHP 8.4 syntax, JS syntax, mocked acceptance and headless Chromium mock UI; NOT WordPress live integration.',
 'unverified':['real WordPress + theme compatibility','live Brave/Mojeek/other provider quota tests','Plugin Check + PHPCS','production penetration/security/load tests','public hosted real WordPress demo','Envato acceptance','brand clearance'],
 'artifacts':{}}
for label,file in [('plugin',PLUGIN),('buyer',BUYER),('source',SOURCE),('simulated_demo',DEMO)]:
 with ZipFile(file) as z:
  assert z.testzip() is None,(label,'corrupt')
  names=z.namelist(); assert len(names)==len(set(names)),label
  assert all(not n.startswith('/') and '..' not in Path(n).parts for n in names),label
  if label=='plugin':
   assert 'luxefind-search-pro/luxefind-search-pro.php' in names
   assert 'luxefind-search-pro/includes/class-swp-instant.php' in names
   assert not any('/tests/' in n for n in names)
   assert not any('/docs/' in n for n in names)
  if label=='buyer':assert 'documentation/index.html' in names and 'plugin/luxefind-search-pro.zip' in names
 result['artifacts'][label]={'filename':file.name,'bytes':file.stat().st_size,'sha256':sha256(file.read_bytes()).hexdigest(),'zip_entries':len(names)}
MANIFEST.write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result['artifacts'],indent=2))
print('PASS: plugin, buyer, developer source, static demo ZIPs verified; hashes written to manifest')
