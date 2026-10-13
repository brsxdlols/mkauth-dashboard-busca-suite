#!/usr/bin/env python3
"""Authenticated full-page benchmark. Requires a private Netscape cookie file.

No passwords or cookies are printed. Never manufacture an administrative session.
"""
import argparse, http.cookiejar, json, os, re, statistics, sys, time, urllib.parse, urllib.request

def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--url',required=True,help='URL da Busca, ex.: https://servidor/admin/addons/busca_inteligente/')
    parser.add_argument('--cookies',required=True,help='Arquivo Netscape de cookies da sua sessao administrativa')
    parser.add_argument('--report',required=True)
    args=parser.parse_args()
    url=urllib.parse.urlsplit(args.url)
    if url.scheme not in ('http','https') or not url.hostname or url.username or url.password:
        raise ValueError('URL invalida. Nao inclua credenciais na URL.')
    if os.stat(args.cookies).st_mode & 0o077:
        raise ValueError('O arquivo de cookies deve ser privado (chmod 600).')
    jar=http.cookiejar.MozillaCookieJar(args.cookies);jar.load(ignore_discard=True)
    client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    base=args.url.rstrip('/')+'/'
    def request(path,payload=None):
        data=None if payload is None else json.dumps(payload).encode()
        req=urllib.request.Request(urllib.parse.urljoin(base,path),data=data,headers={'Content-Type':'application/json','Cache-Control':'no-cache'})
        with client.open(req,timeout=45) as response:return response.read().decode('utf-8')
    def api(action=None):
        data=json.loads(request('performance_api.php',None if action is None else {'action':action,'csrf':csrf}))
        if 'error' in data:raise ValueError(data['error'])
        return data
    setup=api();csrf=setup['csrf'];plan=setup['plan'];samples={};pending=False
    if plan.get('pending'):raise ValueError('Uma validacao esta pendente. Use a tela de diagnostico para reverter.')
    def stage(rule):
        samples[rule]={}
        for name in ('on','off'):
            rows=[]
            for n in range(3):
                query=urllib.parse.urlencode(dict(busca=name,pagina=1,num_registros=25,organizar='c.nome',_mka_perf=plan['id'],_mka_rule=rule,_mka_run=str(time.time_ns())))
                start=time.monotonic();body=request('index.php?'+query);duration=time.monotonic()-start
                marker=re.search(r'<script type="application/json" id="mka-perf-metrics">(.*?)</script>',body,re.S)
                if not marker:raise ValueError('Resposta sem metricas autenticadas; nao e um benchmark valido.')
                metrics=json.loads(marker.group(1))
                if metrics.get('failed'):raise ValueError('Consulta falhou ou excedeu o timeout.')
                rows.append({'web_seconds':duration,'metrics':metrics})
                print(f'{rule} {name}: {duration:.3f}s ({n+1}/3)');time.sleep(.5)
            samples[rule][name]=rows
    def report():
        server=api('report')
        data={'server':server['report'],'web':samples,'compatibility':plan['checks'],'environment':{'php':setup.get('php'),'database':setup.get('database'),'mk_auth':setup.get('mk_auth'),'tables':plan.get('tables'),'indexes':plan.get('indexes')}}
        os.makedirs(os.path.dirname(os.path.abspath(args.report)),exist_ok=True)
        with open(args.report,'w',encoding='utf-8') as stream:json.dump(data,stream,indent=2)
        os.chmod(args.report,0o600)
        print('Medianas das paginas completas (HTTP):')
        for rule,filters in samples.items():
            for name,rows in filters.items():print(rule,name,f"{statistics.median(r['web_seconds'] for r in rows):.3f}s")
        print('Relatorio:',args.report)
        return bool(server.get('recommended'))
    try:
        stage('baseline')
        if plan['compatible'] and not plan['already_direct']:stage('candidate')
        recommended=report()
        if plan['already_direct']:print('Ja otimizado. Nenhuma alteracao necessaria.');return
        if not plan['compatible']:print('Compatibilidade nao comprovada. Aplicacao bloqueada.');return
        if not recommended:print('Nao foi comprovada melhora suficiente. Nenhuma alteracao recomendada.');return
        if input('Autoriza aplicar a otimizacao proposta e validar antes/depois? [s/N] ').strip().lower()!='s':print('Nenhuma alteracao aplicada.');return
        api('apply');pending=True;stage('applied')
        for name in ('on','off'):
            if statistics.median(r['web_seconds'] for r in samples['applied'][name])>statistics.median(r['web_seconds'] for r in samples['baseline'][name])*1.10:
                raise ValueError('Tempo HTTP apresentou regressao; reverter a politica.')
        api('confirm');pending=False;report();print('Otimizacao confirmada apos validacao.')
    finally:
        if pending:
            try:api('rollback');print('Politica anterior restaurada.')
            except Exception:print('Nao foi possivel confirmar rollback. A politica provisoria expira automaticamente em ate cinco minutos.')

if __name__=='__main__':
    try:main()
    except Exception as error:print('Diagnostico interrompido:',str(error),file=sys.stderr);sys.exit(1)
