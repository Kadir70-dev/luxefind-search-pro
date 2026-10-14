(function(blocks,element,components){'use strict';
  if(!blocks||!element||!components)return;
  var e=element.createElement;
  blocks.registerBlockType('luxefind/search',{
    title:'LuxeFind Premium Search',icon:'search',category:'widgets',
    attributes:{mode:{type:'string',default:'all'},placeholder:{type:'string',default:''}},
    edit:function(props){return e('div',{style:{border:'1px solid #b9a172',padding:'20px',borderRadius:'8px',background:'#20242a',color:'#fff'}},
      e('strong',null,'✦ LuxeFind Search Pro'),
      e('p',null,'Search appears as an interactive experience on the live page.'),
      e(components.SelectControl,{label:'Default category',value:props.attributes.mode,options:[
        {label:'All',value:'all'},{label:'Site',value:'site'},{label:'Products',value:'products'},
        {label:'Web',value:'web'},{label:'Images',value:'images'},{label:'News',value:'news'},
        {label:'Videos',value:'videos'},{label:'Maps',value:'maps'}],onChange:function(value){props.setAttributes({mode:value});}}),
      e(components.TextControl,{label:'Placeholder (optional)',value:props.attributes.placeholder,onChange:function(value){props.setAttributes({placeholder:value});}})
    );},save:function(){return null;}
  });
})(window.wp&&window.wp.blocks,window.wp&&window.wp.element,window.wp&&window.wp.components);
