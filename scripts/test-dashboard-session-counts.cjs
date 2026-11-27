const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');
class Element{
 constructor(){this.children=[];this.dataset={};this.events={};this.classList={add(){}};}
 append(...nodes){for(const n of nodes){n.parentElement=this;this.children.push(n)}}
 replaceChildren(...nodes){this.children=[];this.append(...nodes)}
 setAttribute(key,value){this[key]=value}
 addEventListener(name,handler){this.events[name]=handler}
 find(id){if(this.id===id)return this;for(const n of this.children){const found=n.find(id);if(found)return found}return null}
 querySelector(selector){return this.find(selector.slice(1))}
}
const root=new Element(),opener=new Element(),old=new Element(),head=new Element();root.id='systopo';opener.id='run_open_sessoes';old.id='UUE_SESSOES';opener.append(old);root.append(opener);
let calls=0,focus,opened=[],intervals=0;
const context={document:{readyState:'complete',hidden:false,head,body:root,getElementById:id=>root.find(id)||head.find(id),createElement:()=>new Element()},window:{open:url=>opened.push(url),addEventListener(name,fn){if(name==='focus')focus=fn}},setInterval(fn,time){assert.equal(time,30000);intervals++},setTimeout(){return 0},clearTimeout(){},AbortController,fetch:async()=>{calls++;return {ok:true,json:async()=>({users:3,sessions:5,my_sessions:1,session_url:'/admin/sessoes.php'})}}};
const source=fs.readFileSync(path.join(__dirname,'../addons/shared/admin_header.js'),'utf8');
(async()=>{
 vm.runInNewContext(source,context);await new Promise(setImmediate);
 assert.equal(root.find('mkaDashboardWebSessions').textContent,'05');assert.equal(root.find('mkaDashboardWebSessionsTotal').textContent,'01');assert.equal(root.find('UUE_SESSOES'),null);
 vm.runInNewContext(source,context);assert.equal(calls,1);assert.equal(intervals,1);
 for(const view of ['users','mine'])opener.events.click({target:{closest:()=>({dataset:{mkaSessionView:view}})},preventDefault(){},stopImmediatePropagation(){}});
 assert(opened[0].startsWith('/admin/sessoes.php?scope=all'));assert(opened[1].startsWith('/admin/sessoes.php?scope=mine'));
 await focus();assert.equal(calls,2);
 console.log('PASS shared counters, own-user sessions, native SSE isolation, independent routes, refresh and duplicate-loader guard');
})().catch(error=>{console.error(error);process.exitCode=1;});
