from pathlib import Path
import shutil,re
ROOT=Path(__file__).resolve().parents[1]
DEMO=ROOT/'demo'; (DEMO/'assets').mkdir(parents=True,exist_ok=True)
for name in ['search.css','search.js']:
 shutil.copy2(ROOT/'assets'/name,DEMO/'assets'/name)
source=(ROOT/'tests'/'preview.html').read_text()
source=source.replace('../assets/search.css','assets/search.css').replace('../assets/search.js','assets/search.js')
source=source.replace('http://127.0.0.1:8765/wp-json/luxefind/v1/search','https://demo.luxefind.invalid/wp-json/luxefind/v1/search')
source=source.replace('<title>LuxeFind Search Pro — Mock Design Preview</title>', '<title>LuxeFind Search Pro — Simulated Interactive UI Preview</title><meta name="robots" content="noindex,nofollow">')
source=source.replace('DESIGN PREVIEW — MOCK DATA, NOT LIVE WORDPRESS','INTERACTIVE DEMO · SIMULATED SEARCH RESULTS · NO EXTERNAL API')
script=r'''<script>
/* This file is an intentionally simulated frontend-only demo. It does not call providers. */
(function(){
 const originalFetch=window.fetch;
 window.fetch=async function(url,options){
  const u=new URL(String(url),location.href);
  if(u.pathname==='/wp-json/luxefind/v1/search'){
   const q=u.searchParams.get('q')||'',mode=u.searchParams.get('mode')||'all';
   const site=(mode==='site'||mode==='all'||mode==='products')?[{title:'Design System Journal',url:'https://example.org/journal',source:'site',type:'post',description:'Sample local result from the simulated demo. Real site content depends on WordPress.'}]:[];
   const web=(mode==='web'||mode==='all')?[{title:'Example Search Result',url:'https://example.org/',source:'brave',type:'web',description:'Simulated external result. Configure your own API key for live results.'}]:[];
   const media=['images','news','videos'].includes(mode)?[{title:'Example '+mode+' entry',url:'https://example.org/',source:'brave',type:mode,description:'Placeholder example only — not queried from an API.'}]:[];
   const instant=mode==='instant'?{type:'preview',title:'Instant-answer preview',value:q.trim()==='2+3'?'5':'Try 2+3, 25 km to miles or uuid in the installed plugin.'}:null;
   const warnings=['SIMULATED RESULTS — THIS IS A FRONTEND DESIGN PREVIEW ONLY'];
   return new Response(JSON.stringify({query:q,mode,site,web,media,warnings,knowledge:null,instant}),{status:200,headers:{'Content-Type':'application/json'}});
  }
  return originalFetch(url,options);
 };
})();
</script>'''
source=source.replace('<script src="assets/search.js"></script>',script+'<script src="assets/search.js"></script>')
(DEMO/'index.html').write_text(source)
(DEMO/'README.md').write_text('# Offline UI demonstration\n\nThis is a **simulated design demo**. It uses the actual plugin HTML/CSS/JS but intercepts its search endpoint with clearly labeled sample data. No WordPress backend or external API is active. Do **not** use as evidence of live search functionality or market it as a full live demo.\n\nPublish these files to any static host (only if you want a design preview); publicly label the results as simulated and provide separate actual WordPress demo before CodeCanyon release.\n')
print('created',DEMO/'index.html','size',(DEMO/'index.html').stat().st_size)
