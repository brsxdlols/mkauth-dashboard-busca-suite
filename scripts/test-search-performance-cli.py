#!/usr/bin/env python3
"""Local HTTP fixture only: test the CLI approval/validation/rollback workflow."""
import http.server, json, os, pathlib, subprocess, sys, tempfile, threading, time, urllib.parse

def scenario(answer,fail_applied=False):
    actions=[]
    class Handler(http.server.BaseHTTPRequestHandler):
        def log_message(self,*args):pass
        def do_GET(self):
            if self.headers.get('Cookie')!='fixture=test':self.send_error(403);return
            if self.path.startswith('/performance_api.php'):
                data={'csrf':'fixture-token','plan':{'id':'fixture','compatible':True,'already_direct':False,'checks':{'fixture':True}}}
                body=json.dumps(data).encode()
            else:
                query=urllib.parse.parse_qs(urllib.parse.urlsplit(self.path).query)
                rule=query['_mka_rule'][0]
                if rule=='applied' and fail_applied:self.send_error(500);return
                time.sleep(.03 if rule=='baseline' else .005)
                metric={'failed':False,'rule':rule,'metrics':[]}
                body=('<html><script type="application/json" id="mka-perf-metrics">'+json.dumps(metric)+'</script></html>').encode()
            self.send_response(200);self.end_headers();self.wfile.write(body)
        def do_POST(self):
            data=json.loads(self.rfile.read(int(self.headers['Content-Length'])));assert data['csrf']=='fixture-token'
            actions.append(data['action']);body=json.dumps({'ok':True,'report':{},'recommended':True}).encode()
            self.send_response(200);self.end_headers();self.wfile.write(body)
    server=http.server.ThreadingHTTPServer(('127.0.0.1',0),Handler)
    threading.Thread(target=server.serve_forever,daemon=True).start()
    try:
        with tempfile.TemporaryDirectory() as folder:
            cookies=pathlib.Path(folder)/'cookies.txt'
            cookies.write_text('# Netscape HTTP Cookie File\n127.0.0.1\tFALSE\t/\tFALSE\t'+str(int(time.time())+600)+'\tfixture\ttest\n')
            os.chmod(cookies,0o600)
            result=subprocess.run([sys.executable,str(pathlib.Path(__file__).with_name('search-performance.py')),'--url',f'http://127.0.0.1:{server.server_port}/','--cookies',str(cookies),'--report',str(pathlib.Path(folder)/'report.json')],input=answer+'\n',text=True,capture_output=True,timeout=60)
            if fail_applied:assert result.returncode!=0 and 'rollback' in actions and 'confirm' not in actions,(actions,result.stderr)
            elif answer=='s':assert result.returncode==0 and 'apply' in actions and 'confirm' in actions,(actions,result.stderr)
            else:assert result.returncode==0 and 'apply' not in actions,(actions,result.stderr)
            assert 'fixture=test' not in result.stdout and 'fixture-token' not in result.stdout
            print('PASS', 'rollback after failed HTTP' if fail_applied else ('approved apply and verification' if answer=='s' else 'declined optimization'))
    finally:server.shutdown();server.server_close()

if __name__=='__main__':scenario('n');scenario('s');scenario('s',True)
