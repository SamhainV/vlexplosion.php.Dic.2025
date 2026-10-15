"""Resume only browser checks on an existing fixture-only socket database."""
import argparse,pathlib,os,re,subprocess,pwd,time,socket,shutil
ROOT=pathlib.Path(__file__).resolve().parents[2]
p=argparse.ArgumentParser();p.add_argument('--capture-only',action='store_true');p.add_argument('--runtime',required=True,type=pathlib.Path);p.add_argument('--port',type=int,default=8144);a=p.parse_args();r=a.runtime.resolve()
assert r.parent==ROOT/'tests/runtime' and re.fullmatch(r'db-enc-[a-f0-9]{8}',r.name)
assert (r/'progress-states.json').is_file();sock=r/'s.sock'
env=dict(os.environ,VLEXPLOSION_TEST_SOCKET=str(sock),VLEXPLOSION_TEST_RUN=r.name,VLEX_ENC_ENABLED='1',VLEX_ENC_FIXTURE='1',VLEX_CREDIT_FIXTURE='1',VLEX_ENC_PORT=str(a.port),VLEX_ENC_BASE='http://127.0.0.1:'+str(a.port),VLEX_ENC_RUNTIME=str(r),DB_ENABLED='0',VLEX_AI_ENABLED='0')
with socket.socket() as probe:probe.bind(('127.0.0.1',a.port))
client=['mariadb','--no-defaults','--protocol=socket','--socket='+str(sock),'-uroot','--batch','--skip-column-names']
server=web=None
try:
 with (r/'resume-process.log').open('a') as log:
  server=subprocess.Popen(['mariadbd','--no-defaults','--datadir='+str(r),'--socket='+str(sock),'--pid-file='+str(r/'server.pid'),'--log-error='+str(r/'server.log'),'--tmpdir='+str(r),'--skip-networking','--skip-log-bin','--event-scheduler=OFF','--innodb-buffer-pool-size=32M','--innodb-log-file-size=32M','--user='+pwd.getpwuid(os.geteuid()).pw_name],stdout=log,stderr=log)
 for _ in range(100):
  test=subprocess.run(client+['-e','SELECT @@datadir,@@skip_networking'],capture_output=True,text=True)
  if test.returncode==0:
   dr,off=test.stdout.strip().split('\t');assert pathlib.Path(dr).resolve()==r and off=='1';break
  if server.poll() is not None:raise RuntimeError('Private DB stopped')
  time.sleep(.1)
 else:raise RuntimeError('Private DB timeout')
 import json
 states=json.loads((r/'progress-states.json').read_text())
 k=states['paused']['job'];assert re.fullmatch('[a-f0-9]{64}',k)
 subprocess.run(client+['vlexplosion_test','-e',"UPDATE ENC_CREDIT_JOBS SET state_json=JSON_SET(state_json,'$.retry_at',UNIX_TIMESTAMP()+60) WHERE id='"+k+"' AND status='collecting'"],check=True)
 k=states['no-new']['job'];assert re.fullmatch('[a-f0-9]{64}',k)
 cancelled=subprocess.check_output(client+['vlexplosion_test','-e',"SELECT id FROM ENC_CREDIT_JOBS WHERE owner_id=7 AND status='cancelled' ORDER BY created_at DESC LIMIT 1"],text=True).strip();assert re.fullmatch('[a-f0-9]{64}',cancelled)
 (r/'capture-cancelled.json').write_text(json.dumps({'job':cancelled}))
 print('Synthetic no-new summary:',subprocess.check_output(client+['vlexplosion_test','-e',"SELECT JSON_EXTRACT(state_json,'$.summary') FROM ENC_CREDIT_JOBS WHERE id='"+k+"'"],text=True).strip(),flush=True)
 with (r/'web.log').open('a') as log:web=subprocess.Popen(['php','-d','allow_url_fopen=0','-S',env['VLEX_ENC_BASE'][7:],'-t',str(ROOT/'public'),str(ROOT/'tools/encyclopedia_router.php')],cwd=ROOT,env=env,stdout=log,stderr=log)
 for _ in range(100):
  try:
   with socket.create_connection(('127.0.0.1',a.port),timeout=.1):break
  except OSError:time.sleep(.1)
 else:raise RuntimeError('Web timeout')
 subprocess.run([os.environ.get('VLEX_TEST_NODE') or shutil.which('node') or 'node','tests/credits/progress-captures.cjs' if a.capture_only else 'tests/credits/progress-browser.cjs'],cwd=ROOT,env=env,check=True)
finally:
 for child in [web,server]:
  if child:child.terminate();child.wait(timeout=15)
