#!/usr/bin/env python3
"""Maintain only the suite report shortcuts; preserve custom menus."""
import re,sys
from pathlib import Path
p=Path(sys.argv[1])/'addons/addon.js'
if p.is_symlink():raise ValueError('addon.js is a symlink; preserved')
s=p.read_bytes() if p.exists() else b''
s=re.sub(rb'/\* MKA-SUITE-REPORT-MENUS-START \*/.*?/\* MKA-SUITE-REPORT-MENUS-END \*/\r?\n?',b'',s,flags=re.S)
block=b'/* MKA-SUITE-REPORT-MENUS-START */\n'
icons={'Ativa&ccedil;&otilde;es e Cancelamentos':'exchange','Faturamento detalhado':'bar-chart','Busca inteligente':'search','Controle de Planos':'wifi','Score de clientes':'star','Clientes bloqueados':'lock','Relat&oacute;rio de desativados':'user-times'}
for menu,url,label in [('clientes','/admin/addons/rel_clientes/','Ativa&ccedil;&otilde;es e Cancelamentos'),('financeiro','/admin/addons/graf_faturamento/graf_det.php','Faturamento detalhado'),('clientes','/admin/addons/busca_inteligente/','Busca inteligente'),('provedor','/admin/addons/rel_plano/','Controle de Planos'),('clientes','/admin/addons/busca_inteligente/score.php','Score de clientes'),('clientes','/admin/addons/rel_bloqueados/','Clientes bloqueados'),('clientes','/admin/addons/rel_desativados/','Relat&oacute;rio de desativados')]:
    if not re.search(rb'add_menu\.'+menu.encode()+rb'\s*\([^;]*'+re.escape(url.encode()),s):
        block+=("add_menu."+menu+"(JSON.stringify({plink:window.location.origin+'"+url+"',ptext:'"+'<i class="fa fa-fw fa-'+icons[label]+'" aria-hidden="true"></i>&nbsp; '+label+"'}));\n").encode()
block+=br''';(function(){
var icons={'Ativa\u00e7\u00f5es e Cancelamentos':'exchange','Faturamento detalhado':'bar-chart','Busca inteligente':'search','Controle de Planos':'wifi','Score de clientes':'star','Clientes bloqueados':'lock','Relat\u00f3rio de desativados':'user-times','Clientes offline':'plug','LOG RADIUS':'list-alt','Radius Logs':'list-alt','Painel IPv4/IPv6':'globe','NFCOM / Gerador NF DICI':'file-text-o','DICI / ANATEL':'bar-chart','Relatorio de Trafego':'line-chart','Relat\u00f3rio de Tr\u00e1fego':'line-chart'};
function decorate(){document.querySelectorAll('.navbar-dropdown a').forEach(function(a){var label=a.textContent.replace(/\s+/g,' ').trim(),icon=icons[label];if(/^(?:[^A-Za-z0-9]*\s*)?Caixa$/i.test(label)&&/\/addons\/graf_faturamento\/graf_dia\.php(?:[?#]|$)/.test(a.getAttribute('href')||'')){a.setAttribute('href','/admin/addons/rel_caixa/');}if(!icon||a.querySelector('i,svg'))return;var i=document.createElement('i');i.className='fa fa-fw fa-'+icon;i.setAttribute('aria-hidden','true');i.style.marginRight='6px';a.insertBefore(i,a.firstChild);});}
function start(){decorate();var root=document.querySelector('.navbar-start');if(!root||!window.MutationObserver)return;var observer=new MutationObserver(decorate);observer.observe(root,{childList:true,subtree:true});setTimeout(function(){observer.disconnect();},30000);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
'''
block+=b'/* MKA-SUITE-REPORT-MENUS-END */\n'
p.parent.mkdir(parents=True,exist_ok=True)
p.write_bytes(s+(b'\n' if s and not s.endswith(b'\n') else b'')+block)
