(function(){
 'use strict';
 if(window.mkaAdminAssetPaths||typeof window.fetch!=='function')return;
 window.mkaAdminAssetPaths=true;
 const original=window.fetch;
 window.fetch=function(input,options){
  if(typeof input==='string'){
   const url=new URL(input,document.baseURI);
   if(url.origin===location.origin){
    if(url.pathname.endsWith('/addons/geocodificacao/geocode.php'))url.pathname='/admin/addons/geocodificacao/geocode.php';
    else if(url.pathname.endsWith('/conf_mapas.hhvm'))url.pathname='/admin/conf_mapas.hhvm';
    if(url.href!==new URL(input,document.baseURI).href)input=url.href;
   }
  }
  return original.call(this,input,options);
 };
}());
