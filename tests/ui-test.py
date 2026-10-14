"""Chromium smoke test of static shortcode preview with mocked REST, not real WP E2E."""
import http.server, socketserver, threading, json, pathlib
from playwright.sync_api import sync_playwright
root=pathlib.Path(__file__).resolve().parents[1]
class Handler(http.server.SimpleHTTPRequestHandler):
 def __init__(self,*args,**kwargs): super().__init__(*args,directory=str(root),**kwargs)
 def log_message(self,*args): pass
class HTTPServer(socketserver.ThreadingMixIn,http.server.HTTPServer): daemon_threads=True;allow_reuse_address=True
server=HTTPServer(('127.0.0.1',8765),Handler)
thread=threading.Thread(target=server.serve_forever,daemon=True);thread.start()
records=[]
with sync_playwright() as p:
 browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
 def response(route):
  from urllib.parse import urlparse,parse_qs
  query=parse_qs(urlparse(route.request.url).query)
  mode=query.get('mode',['all'])[0]
  records.append(mode)
  if mode=='site': site=[{'title':'Luxury Interiors Guide','url':'https://example.com/interior','description':'Discover beautiful architectural spaces.','source':'site','type':'post','thumbnail':'https://picsum.photos/id/1025/300/200'}]
  elif mode in ('all','products'): site=[{'title':'Sculptural Residence','url':'https://example.com/residence','description':'An elegant contemporary residence with panoramic views and refined interiors.','source':'site','type':'product','price':'$ 1,490,000','sku':'LV-102','thumbnail':'https://picsum.photos/id/1040/300/200'}, {'title':'The Art of Timeless Living','url':'https://example.com/art','description':'Architecture, space, craftsmanship and style.','source':'site','type':'post'}]
  else: site=[]
  web=[{'title':('<img src=x onerror=window.xss=1>' if mode=='web' else 'The New Era of Bespoke Living'),'url':'https://example.com/editorial','description':'A curated look at extraordinary spaces around the world.','source':'brave','type':'web'}] if mode in ('all','web') else []
  if mode=='web': web.append({'title':'unsafe fake link','url':'javascript:alert(1)','description':'Never clickable as link','source':'brave','type':'web'})
  media=[{'title':'Dubai skyline','url':'https://example.com/image','description':'Dubai architecture','source':'brave','type':mode}] if mode in ('images','news','videos','maps') else []
  payload={'query':query.get('q',[''])[0],'mode':mode,'site':site,'web':web,'media':media,'warnings':[],'knowledge':None,'instant':None}
  route.fulfill(status=200,content_type='application/json',headers={'access-control-allow-origin':'*'},body=json.dumps(payload))
 for width,height,basename in [(1440,900,'ui-desktop.png'),(390,844,'ui-mobile.png')]:
  page=browser.new_page(viewport={'width':width,'height':height},device_scale_factor=1)
  errors=[];page.on('pageerror',lambda e: errors.append(str(e)))
  page.route('**/wp-json/luxefind/v1/search?**',response)
  html=(root/'tests/preview.html').read_text()
  html=html.replace('<link rel="stylesheet" href="../assets/search.css">','<style>'+(root/'assets/search.css').read_text()+'</style>')
  html=html.replace('<script src="../assets/search.js"></script>','<script>'+(root/'assets/search.js').read_text()+'</script>')
  html=html.replace('http://127.0.0.1:8765/wp-json','https://site.example.test/wp-json')
  page.set_content(html,wait_until='load')
  assert page.locator('[data-lfxsp-search]').count()==1
  assert page.locator('[data-lfxsp-tab]').count()>=7
  page.locator('input[name="q"]').first.fill('Dubai luxury')
  page.locator('button[type="submit"]').first.click()
  page.wait_for_function("document.querySelector('[data-lfxsp-status]').textContent.includes('3 results')", timeout=5000)
  assert page.locator('.lfxsp-result').count()>=2
  page.locator('[data-lfxsp-tab="products"]').click()
  assert page.locator('[data-lfxsp-filters]').is_visible()
  page.locator('[name="min_price"]').fill('100')
  page.locator('[name="min_price"]').press('Tab')
  page.locator('.lfxsp-result').first.wait_for(timeout=3000)
  assert not errors,errors
  assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'horizontal page overflow'
  page.locator('[data-lfxsp-tab="web"]').click()
  page.locator('.lfxsp-result').first.wait_for(timeout=3000)
  assert not page.locator('.lfxsp-result img[onerror]').count(), 'untrusted title inserted as HTML'
  assert not page.evaluate('window.xss'), 'script executed'
  assert page.locator('.lfxsp-result__title[href^="javascript:"]').count()==0, 'unsafe URL was rendered as link'
  page.locator('[data-lfxsp-tab="images"]').click()
  page.locator('.lfxsp-result').first.wait_for(timeout=3000)
  assert 'images' in records
  page.locator('[data-lfxsp-tab="all"]').click()
  page.locator('.lfxsp-result').first.wait_for(timeout=3000)
  page.wait_for_timeout(550)
  page.screenshot(path=str(root/'tests'/basename),full_page=True)
  print('PASS:',basename,'responsive UI, category tabs, product filters, rendering, no JS exceptions, no horizontal overflow')
  page.close()
 browser.close()
server.shutdown()
print('Mock REST categories called:',set(records))
