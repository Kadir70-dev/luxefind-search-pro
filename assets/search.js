(()=>{'use strict';
  const config=window.lfxspConfig||{};
  if(typeof config.endpoint!=='string'||!config.endpoint)return;
  const LIMIT=160;
  const el=(tag,cls,text)=>{const node=document.createElement(tag);if(cls)node.className=cls;if(text!==undefined)node.textContent=text;return node;};
  const safeURL=value=>{try{const uri=new URL(String(value||''),window.location.href);return ['https:','http:'].includes(uri.protocol)?uri.href:null;}catch{return null;}};
  function card(item,index){
    const link=safeURL(item.url),wrap=el('article','lfxsp-result');wrap.style.setProperty('--swp-delay',`${Math.min(index,11)*35}ms`);
    const inner=el('div','lfxsp-result__content');
    inner.append(el('span','lfxsp-result__tag',item.source==='site'?String(item.type||'SITE').toUpperCase():String(item.source||'WEB').toUpperCase()));
    const heading=link?el('a','lfxsp-result__title',item.title||'Untitled'):el('h3','lfxsp-result__title',item.title||'Untitled');
    if(link){heading.href=link;if(item.source!=='site'){heading.target='_blank';heading.rel='noopener noreferrer';}}
    inner.append(heading);
    if(item.description)inner.append(el('p','lfxsp-result__description',String(item.description)));
    if(item.price)inner.append(el('strong','lfxsp-result__price',item.price));
    if(item.sku)inner.append(el('span','lfxsp-result__sku',`SKU ${item.sku}`));
    if(item.in_stock===false)inner.append(el('span','lfxsp-result__stock','Out of stock'));
    const picture=safeURL(item.thumbnail);
    if(config.thumbnails&&picture){const media=el('div','lfxsp-result__media');const img=el('img','lfxsp-result__image');img.src=picture;img.alt='';img.loading='lazy';img.decoding='async';img.referrerPolicy='no-referrer';media.append(img);wrap.append(media);wrap.classList.add('lfxsp-result--image');}
    wrap.append(inner);return wrap;
  }
  function instant(result){const box=el('aside','lfxsp-instant');box.append(el('span','lfxsp-result__tag',result.title||'Instant answer'),el('strong','',String(result.value||'')));return box;}
  for(const root of document.querySelectorAll('[data-lfxsp-search]')){
    const form=root.querySelector('[data-lfxsp-form]'),input=form?.querySelector('input[name="q"]'),results=root.querySelector('[data-lfxsp-results]'),status=root.querySelector('[data-lfxsp-status]');
    if(!form||!input||!results||!status)continue;
    const tabs=[...root.querySelectorAll('[data-lfxsp-tab]')],filters=root.querySelector('[data-lfxsp-filters]'),language=root.querySelector('[data-lfxsp-language]'),submit=form.querySelector('button[type="submit"]');
    let active=tabs.find(t=>t.dataset.lfxspTab===root.dataset.mode)?.dataset.lfxspTab||tabs[0]?.dataset.lfxspTab||'site';
    let current=null,timer=null,sequence=0;
    function select(mode){active=mode;for(const tab of tabs)tab.setAttribute('aria-pressed',tab.dataset.lfxspTab===mode?'true':'false');if(filters)filters.hidden=mode!=='products';if(language)language.hidden=mode!=='translate';}
    function spinner(){const frag=document.createDocumentFragment();for(let i=0;i<3;i++){const x=el('div','lfxsp-result lfxsp-result--skeleton');x.setAttribute('aria-hidden','true');frag.append(x);}results.replaceChildren(frag);}
    async function search(preview=false){
      const q=input.value.trim(),min=Math.max(2,Number(config.min)||2);
      if(q.length<min||q.length>LIMIT){if(!preview)status.textContent=`Enter ${min}–${LIMIT} characters to search.`;return;}
      current?.abort();const controller=new AbortController();current=controller;const seq=++sequence;
      const mode=preview?'site':active;
      if(!preview){submit.disabled=true;spinner();status.textContent='Discovering results…';}else{status.textContent='Searching this site…';}
      try{
        const url=new URL(config.endpoint);url.searchParams.set('q',q);url.searchParams.set('mode',mode);
        if(mode==='products'&&filters){for(const field of filters.querySelectorAll('input,select')){if(field.value.trim())url.searchParams.set(field.name,field.value.trim());}}
        if(mode==='translate'&&language)url.searchParams.set('lang',language.querySelector('select')?.value||'en');
        const response=await fetch(url.href,{signal:controller.signal,credentials:'same-origin',headers:{Accept:'application/json'}});
        const payload=await response.json();if(!response.ok)throw new Error(String(payload.message||'Search unavailable'));
        if(seq!==sequence)return;
        const site=Array.isArray(payload.site)?payload.site:[],web=Array.isArray(payload.web)?payload.web:[],media=Array.isArray(payload.media)?payload.media:[];
        const frag=document.createDocumentFragment();
        if(payload.instant?.value)frag.append(instant(payload.instant));
        if(payload.knowledge)frag.append(card(payload.knowledge,0));
        let number=0;for(const item of [...site,...web,...media])frag.append(card(item,number++));
        if(!number&&!payload.instant&&!payload.knowledge){const empty=el('div','lfxsp-search__empty');empty.append(el('strong','','Nothing found yet.'),el('p','','Try another keyword or explore a different category.'));frag.append(empty);}
        results.replaceChildren(frag);
        const warnings=Array.isArray(payload.warnings)?payload.warnings.filter(x=>typeof x==='string'):[];
        const total=number+(payload.instant?.value?1:0)+(payload.knowledge?1:0);
        status.textContent=`${total} result${total===1?'':'s'}${preview?' · local preview':''}${warnings.length?' · '+warnings.join(' · '):''}`;
      }catch(error){if(error?.name!=='AbortError'&&seq===sequence){results.replaceChildren();status.textContent=error?.message||'Search unavailable. Please try again.';}}
      finally{if(seq===sequence){current=null;submit.disabled=false;}}
    }
    select(active);
    form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);search(false);});
    input.addEventListener('input',()=>{
      clearTimeout(timer);current?.abort();sequence++;submit.disabled=false;
      if(!config.autocomplete){return;}
      const q=input.value.trim();if(q.length<Math.max(2,Number(config.min)||2)){results.replaceChildren();status.textContent='Start typing to explore.';return;}
      timer=setTimeout(()=>search(true),520);
    });
    tabs.forEach((tab,index)=>{
      tab.addEventListener('click',()=>{clearTimeout(timer);current?.abort();select(tab.dataset.lfxspTab);if(input.value.trim())search(false);else{results.replaceChildren();status.textContent='Start typing to explore.';}});
      tab.addEventListener('keydown',event=>{if(!['ArrowLeft','ArrowRight'].includes(event.key))return;event.preventDefault();const next=tabs[(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length];next.focus();next.click();});
    });
    for(const group of [filters,language])group?.addEventListener('change',()=>{if(input.value.trim())search(false);});
  }
})();
