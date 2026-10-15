#!/usr/bin/env python3
"""New socket-only MariaDB, current 31-table schema, 005 + new 006. No production configuration."""
import os,pathlib,subprocess,time,uuid,pwd,argparse,socket,signal,json,shutil,hashlib
ROOT=pathlib.Path(__file__).resolve().parents[2]
def main():
 p=argparse.ArgumentParser();p.add_argument('--presentation',action='store_true');p.add_argument('--scope-only',action='store_true');p.add_argument('--progress-only',action='store_true');p.add_argument('--manual-projection-only',action='store_true');p.add_argument('--regression-only',action='store_true');p.add_argument('--serve',action='store_true');p.add_argument('--browser',action='store_true');p.add_argument('--port',type=int,default=8141);a=p.parse_args()
 if a.presentation:a.scope_only=True
 refs=ROOT/'tests/credits/reference'
 for line in (refs/'SHA256SUMS').read_text().splitlines():
  expected,name=line.split('  ',1)
  if pathlib.Path(name).name!=name or hashlib.sha256((refs/name).read_bytes()).hexdigest()!=expected:raise RuntimeError('Public reference SHA-256 mismatch')
 with socket.socket() as probe:probe.bind(('127.0.0.1',a.port))
 run=ROOT/'tests/runtime'/('db-enc-'+uuid.uuid4().hex[:8]);run.mkdir(parents=True,mode=0o700);sock=run/'s.sock';assert len(str(sock).encode())<=100
 env={'PATH':os.environ.get('PATH','/usr/bin:/bin:/usr/sbin:/sbin'),'LANG':'C.UTF-8','TMPDIR':str(run),'VLEXPLOSION_TEST_SOCKET':str(sock),'VLEXPLOSION_TEST_RUN':run.name,'VLEX_ENC_ENABLED':'1','VLEX_ENC_FIXTURE':'1','VLEX_ENC_PORT':str(a.port),'VLEX_ENC_BASE':'http://127.0.0.1:'+str(a.port),'VLEX_ENC_RUNTIME':str(run),'DB_ENABLED':'0','VLEX_AI_ENABLED':'0'}
 env['VLEX_CREDIT_FIXTURE']='1'
 for k in ['NODE_PATH','VLEX_TEST_BROWSER','VLEX_TEST_NODE']:
  if k in os.environ:env[k]=os.environ[k]
 client=['mariadb','--no-defaults','--protocol=socket','--socket='+str(sock),'-uroot','--batch','--skip-column-names']
 with (run/'init.log').open('x') as log:subprocess.run(['mariadb-install-db','--no-defaults','--datadir='+str(run),'--auth-root-authentication-method=normal','--skip-test-db'],stdout=log,stderr=log,env=env,check=True)
 with (run/'process.log').open('x') as log:
  server=subprocess.Popen(['mariadbd','--no-defaults','--datadir='+str(run),'--socket='+str(sock),'--pid-file='+str(run/'server.pid'),'--log-error='+str(run/'server.log'),'--tmpdir='+str(run),'--skip-networking','--skip-log-bin','--innodb-buffer-pool-size=32M','--innodb-log-file-size=32M','--user='+pwd.getpwuid(os.geteuid()).pw_name],env=env,stdout=log,stderr=log);web=None
  try:
   for _ in range(100):
    check=subprocess.run(client+['-e','SELECT @@datadir,@@socket,@@skip_networking'],capture_output=True,text=True,env=env)
    if check.returncode==0:
     dr,so,off=check.stdout.strip().split('\t');assert pathlib.Path(dr).resolve()==run and pathlib.Path(so).resolve()==sock and off=='1';break
    if server.poll() is not None:raise RuntimeError('Isolated MariaDB failed')
    time.sleep(.1)
   else:raise RuntimeError('Timeout')
   subprocess.run(client+['-e','CREATE DATABASE vlexplosion_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'],env=env,check=True)
   for f in ['database/schema-pre005.sql','migrations/005_encyclopedia_instruments_up.sql','tests/credits/seed.sql']:
    with (ROOT/f).open() as data:subprocess.run(client+['vlexplosion_test'],env=env,stdin=data,check=True)
   if not (a.regression_only or a.manual_projection_only or a.progress_only or a.scope_only):
    for name,location in [('MusicBrainzNormalizer','app/Services/Metadata/'),('EncyclopediaCollection','app/Services/')]:
     content=subprocess.check_output(['git','show','b0d935ed5fad6966bcff6c005d6b8af9d8ae59b7:'+location+name+'.php'],cwd=ROOT)
     (run/('stable-'+name+'.php')).write_bytes(content)
    subprocess.run(['php','tests/credits/legacy-before.php'],env=env,cwd=ROOT,check=True)
   with (ROOT/'migrations/006_encyclopedia_credit_relations_up.sql').open() as data:subprocess.run(client+['vlexplosion_test'],env=env,stdin=data,check=True)
   if not (a.regression_only or a.manual_projection_only or a.progress_only or a.scope_only):
    subprocess.run(['php','tests/credits/verify-upgrade.php'],env=env,cwd=ROOT,check=True)
    subprocess.run(client+['-e','CREATE DATABASE vlexplosion_schema_roundtrip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'],env=env,check=True)
    for f in ['database/schema-pre005.sql','migrations/005_encyclopedia_instruments_up.sql','migrations/006_encyclopedia_credit_relations_up.sql','migrations/006_encyclopedia_credit_relations_down.sql','migrations/006_encyclopedia_credit_relations_up.sql']:
     with (ROOT/f).open() as data:subprocess.run(client+['vlexplosion_schema_roundtrip'],env=env,stdin=data,check=True)
    print('PASS empty-schema 006 up/down/up roundtrip; original 005 preserved.',flush=True)
    subprocess.run(client+['vlexplosion_schema_roundtrip','-e',"INSERT INTO ENC_ALBUMS(title) VALUES('Synthetic downgrade guard'); INSERT INTO ENC_CREDITS(album_id,organisation,role,relation_json) VALUES(LAST_INSERT_ID(),'Synthetic company','unknown','{}')"],env=env,check=True)
    with (ROOT/'migrations/006_encyclopedia_credit_relations_down.sql').open() as data:
     refused=subprocess.run(client+['vlexplosion_schema_roundtrip'],env=env,stdin=data,capture_output=True,text=True)
    assert refused.returncode!=0 and '006 is in use' in refused.stderr
    preserved=subprocess.check_output(client+['vlexplosion_schema_roundtrip','-e',"SELECT COUNT(*) FROM ENC_CREDITS WHERE relation_json='{}'"],env=env,text=True).strip()
    assert preserved=='1'
    print('PASS populated-schema 006 downgrade refused; rich data unchanged.',flush=True)
   print('ISOLATED '+str(run)+' TCP OFF; 005 unchanged; 006 applied only here.',flush=True)
   (ROOT/'docs/credits/runtime.json').write_text(json.dumps({'runtime':str(run),'url':env['VLEX_ENC_BASE'],'fictional':True},indent=2)+'\n')
   tests=['tests/credits/scope.php','tests/credits/prepare-presentation.php','tests/credits/presentation.php'] if a.presentation else ['tests/credits/scope.php'] if a.scope_only else ['tests/credits/progress.php'] if a.progress_only else ['tests/credits/manual-projections.php'] if a.manual_projection_only else ['tests/encyclopedia/collection.php','tests/encyclopedia/workflows.php'] if a.regression_only else ['tests/credits/checks.php','tests/encyclopedia/metadata.php','tests/encyclopedia/formats.php','tests/security.php','tests/covers.php']
   for test in tests:
    command=['php',test]
    result=subprocess.run(command,env=env,cwd=ROOT,capture_output=True,text=True)
    print(result.stdout+result.stderr,flush=True)
    with (run/'results.txt').open('a') as out:out.write(test+'\n'+result.stdout+result.stderr+'exit='+str(result.returncode)+'\n')
    if result.returncode:raise RuntimeError('Failed '+test)
   subprocess.run(['php','tests/encyclopedia/seed.php'],env=env,cwd=ROOT,check=True)
   if a.serve or a.browser:
    with (run/'web.log').open('x') as web_log:
     web=subprocess.Popen(['php','-S','127.0.0.1:'+str(a.port),'-t',str(ROOT/'public'),str(ROOT/('tools/presentation_router.php' if a.presentation else 'tools/encyclopedia_router.php'))],env=env,cwd=ROOT,stdout=web_log,stderr=web_log)
     for _ in range(100):
      try:
       with socket.create_connection(('127.0.0.1',a.port),timeout=.1):break
      except OSError:time.sleep(.1)
     else:raise RuntimeError('PHP did not start')
     print('READY '+env['VLEX_ENC_BASE']+' fiction / fictional-password',flush=True)
     if a.browser:subprocess.run([os.environ.get('VLEX_TEST_NODE') or shutil.which('node') or 'node','tests/credits/presentation-browser.cjs' if a.presentation else 'tests/credits/scope-browser.cjs' if a.scope_only else 'tests/credits/progress-browser.cjs' if a.progress_only else 'tests/credits/browser.cjs'],cwd=ROOT,env=env,check=True)
     if a.serve:web.wait()
  finally:
   for child in [web,server]:
    if child:
     child.terminate()
     try:child.wait(timeout=10)
     except subprocess.TimeoutExpired:child.kill();child.wait()
if __name__=='__main__':
 signal.signal(signal.SIGTERM,lambda *_:(_ for _ in ()).throw(KeyboardInterrupt()))
 try:main()
 except KeyboardInterrupt:print('Only credit test child processes stopped; data retained.')
