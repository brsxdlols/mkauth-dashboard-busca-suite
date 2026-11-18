(function(){'use strict';
var root=document.querySelector('[data-client-growth]');if(!root)return;
var status=root.querySelector('[data-growth-status]'),chart=root.querySelector('[data-growth-chart]'),lastData=null,lastWidth=0;
function el(tag,text){var e=document.createElement(tag);if(text!==undefined)e.textContent=text;return e;}
function url(month,type){return '/admin/addons/rel_clientes/index.php?ano='+month.slice(0,4)+'&mes='+month.slice(5)+'&tipo='+type;}
function net(m){return Number(m.activations)+(Number(m.reactivations)||0)-Number(m.cancellations);}
function svg(tag,attrs,text){var e=document.createElementNS('http://www.w3.org/2000/svg',tag);Object.keys(attrs||{}).forEach(function(key){e.setAttribute(key,String(attrs[key]));});if(text!==undefined)e.textContent=text;return e;}
function renderChart(months){
 var width=Math.max(270,chart.clientWidth||root.clientWidth-40),height=245,top=25,plot=150,low=Math.min.apply(null,[0].concat(months.map(net))),high=Math.max.apply(null,[1].concat(months.map(function(m){return Math.max(Number(m.activations),Number(m.reactivations)||0,Number(m.cancellations),net(m));}))),span=high-low;
 lastWidth=chart.clientWidth;var y=function(value){return top+(high-value)/span*plot;},step=width/6,zero=y(0),points=[],obstacles=[];
 var drawing=svg('svg',{viewBox:'0 0 '+width+' '+height,role:'img','aria-label':'Últimos seis meses: barras de ativações, reativações e cancelamentos; linha do saldo, azul no positivo e vermelha no negativo'});
 var defs=svg('defs'),gradient=svg('linearGradient',{id:'mka-growth-balance-gradient',gradientUnits:'userSpaceOnUse',x1:0,x2:0,y1:zero-6,y2:zero+6});gradient.appendChild(svg('stop',{offset:'0%','stop-color':'#1768df'}));gradient.appendChild(svg('stop',{offset:'100%','stop-color':'#d52c51'}));defs.appendChild(gradient);drawing.appendChild(defs);
 drawing.appendChild(svg('line',{x1:0,x2:width,y1:zero,y2:zero,stroke:'#b8cce3','stroke-width':1}));
 months.forEach(function(m,index){var center=step*(index+.5),bar=Math.min(18,step*.23),gap=Math.max(2,step*.025);[['activations','instalacao','Ativações','#0a9870'],['reactivations','reativacao','Reativações','#7952c9'],['cancellations','cancelamento','Cancelamentos','#e34663']].forEach(function(item,j){var value=Number(m[item[0]])||0,x=center+(j-1)*(bar+gap)-bar/2,a=svg('a',{href:url(m.month,item[1]),'aria-label':item[2]+': '+value+' · '+m.month});a.appendChild(svg('title',{},item[2]+': '+value+' · '+m.month));a.appendChild(svg('rect',{x:x,y:value?y(value):zero-1,width:bar,height:value?Math.max(1,zero-y(value)):2,rx:3,fill:item[3]}));a.appendChild(svg('text',{x:x+bar/2,y:y(value)-6,'text-anchor':'middle',fill:'#193b60','font-size':10},value));obstacles.push({x:x+bar/2-String(value).length*3-2,y:y(value)-16,w:String(value).length*6+4,h:13});drawing.appendChild(a);});points.push([center,y(net(m))]);var date=new Date(Number(m.month.slice(0,4)),Number(m.month.slice(5))-1,1),label=date.toLocaleDateString('pt-BR',{month:'short'}).replace('.','')+'/'+m.month.slice(2,4);drawing.appendChild(svg('text',{x:center,y:height-12,'text-anchor':'middle',fill:'#536f94','font-size':10},label));});
 drawing.appendChild(svg('polyline',{points:points.map(function(p){return p.join(',');}).join(' '),fill:'none',stroke:'url(#mka-growth-balance-gradient)','stroke-width':2.8,'pointer-events':'none'}));
 months.forEach(function(m,index){
  var value=net(m),color=value<0?'#d52c51':'#1768df',text=(value>0?'+':'')+value,labelWidth=text.length*7+12,labelHeight=20,point=points[index],label=null;
  var offsets=value===0?[22,-22,36,-36,50,-50]:[-22,22,-36,36,-50,50];
  for(var k=0;k<offsets.length&&!label;k++)for(var j=0;j<3&&!label;j++){
   var cx=Math.max(labelWidth/2+2,Math.min(width-labelWidth/2-2,point[0]+[0,-13,13][j])),cy=Math.max(12,Math.min(height-39,point[1]+offsets[k])),box={x:cx-labelWidth/2,y:cy-labelHeight/2,w:labelWidth,h:labelHeight};
   if(!obstacles.some(function(o){return box.x<o.x+o.w+2&&box.x+box.w>o.x-2&&box.y<o.y+o.h+2&&box.y+box.h>o.y-2;}))label=box;
  }
  if(!label)label={x:Math.max(2,Math.min(width-labelWidth-2,point[0]-labelWidth/2)),y:Math.max(2,point[1]-40),w:labelWidth,h:labelHeight};obstacles.push(label);
  var a=svg('a',{href:url(m.month,'todos'),'aria-label':'Saldo: '+value+' · '+m.month,'data-growth-balance':value});a.appendChild(svg('title',{},'Saldo: '+value+' · '+m.month));
  a.appendChild(svg('line',{x1:point[0],y1:point[1],x2:label.x+label.w/2,y2:label.y+label.h/2,stroke:color,'stroke-width':1,opacity:.4}));
  a.appendChild(svg('circle',{cx:point[0],cy:point[1],r:4,fill:color,stroke:'#fff','stroke-width':1.5}));
  a.appendChild(svg('rect',{x:label.x,y:label.y,width:label.w,height:label.h,rx:5,fill:'#fff',stroke:color,'stroke-width':.7}));
  a.appendChild(svg('text',{x:label.x+label.w/2,y:label.y+14,'text-anchor':'middle',fill:color,'font-size':11,'font-weight':750},text));drawing.appendChild(a);
 });
 chart.replaceChildren(drawing);
}
function render(months){lastData=months;var summary=root.querySelector('[data-growth-summary]'),last=months[months.length-1],balance=net(last);summary.replaceChildren();[['Ativações neste mês',last.activations,'instalacao','growth-green'],['Reativações neste mês',last.reactivations,'reativacao','growth-purple'],['Cancelamentos neste mês',last.cancellations,'cancelamento','growth-red'],['Saldo neste mês',(balance>0?'+':'')+balance,'todos','growth-balance']].forEach(function(item){var a=el('a',item[0]);a.href=url(last.month,item[2]);a.className=item[3];a.appendChild(el('strong',item[1]));if(item[2]==='todos')a.appendChild(el('small','Ativações + reativações − cancelamentos'));summary.appendChild(a);});renderChart(months);status.textContent='Clique em uma barra ou no ponto do saldo para consultar as movimentações do mês.';}
function load(){status.textContent='Carregando movimentações…';fetch('/admin/addons/dashboard/client_growth_api.php',{credentials:'same-origin',cache:'no-store'}).then(function(r){if(!r.ok)throw Error();return r.json();}).then(function(data){if(!data.ok||!Array.isArray(data.months)||data.months.length!==6)throw Error();render(data.months);}).catch(function(){status.textContent='Não foi possível carregar. ';var retry=el('button','Tentar novamente');retry.type='button';retry.onclick=load;status.appendChild(retry);});}
if(window.ResizeObserver)new ResizeObserver(function(){if(lastData&&chart.clientWidth!==lastWidth)renderChart(lastData);}).observe(chart);
if('IntersectionObserver'in window){var observer=new IntersectionObserver(function(entries){if(entries.some(function(e){return e.isIntersecting;})){observer.disconnect();load();}},{rootMargin:'200px'});observer.observe(root);}else load();
})();
