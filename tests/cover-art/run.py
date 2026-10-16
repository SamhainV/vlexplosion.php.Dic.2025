#!/usr/bin/env python3
"""Synthetic covers, no production credentials, no external image requests."""
import os,pathlib,subprocess,time,uuid,pwd,argparse,shutil,json,hashlib
ROOT=pathlib.Path(__file__).resolve().parents[2]
def main():
 os.umask(0o077)
 p=argparse.ArgumentParser();p.add_argument('--browser',action='store_true');p.add_argument('--reuse');a=p.parse_args()
 run=ROOT/'tests/runtime'/(a.reuse or ('db-enc-'+uuid.uuid4().hex[:8]));
 if a.reuse:assert run.is_dir() and run.parent.resolve()==(ROOT/'tests/runtime').resolve() and run.name.startswith('db-enc-') and not (run/'s.sock').exists() and (run/'SYNTHETIC-COVERS').is_file()
 else:run.mkdir(parents=True,mode=0o700)
 sock=run/'s.sock'
 env=dict(os.environ,VLEXPLOSION_TEST_SOCKET=str(sock),VLEXPLOSION_TEST_RUN=run.name,VLEX_ENC_ENABLED='1',DB_ENABLED='0',VLEX_ENC_PORT='8153',VLEX_ENC_BASE='http://127.0.0.1:8153',TMPDIR='/tmp')
 client=['mariadb','--no-defaults','--protocol=socket','--socket='+str(sock),'-uroot','--batch','--skip-column-names']
 if not a.reuse:
  (run/'SYNTHETIC-COVERS').write_text('No real collection data.\n')
  with (run/'init.log').open('x') as log:subprocess.run(['mariadb-install-db','--no-defaults','--datadir='+str(run),'--auth-root-authentication-method=normal','--skip-test-db'],stdout=log,stderr=log,check=True)
 log=(run/'process.log').open('a');server=subprocess.Popen(['mariadbd','--no-defaults','--datadir='+str(run),'--socket='+str(sock),'--pid-file='+str(run/'server.pid'),'--log-error='+str(run/'server.log'),'--tmpdir='+str(run),'--skip-networking','--skip-log-bin','--event-scheduler=OFF','--innodb-buffer-pool-size=32M','--innodb-log-file-size=32M','--user='+pwd.getpwuid(os.geteuid()).pw_name],stdout=log,stderr=log);web=None
 def sql(q):return subprocess.check_output(client+['vlexplosion_test','-e',q],text=True)
 def apply(file):
  with (ROOT/file).open() as data:subprocess.run(client+['vlexplosion_test'],stdin=data,check=True,stdout=subprocess.DEVNULL)
 try:
  for _ in range(100):
   r=subprocess.run(client+['-e','SELECT @@datadir,@@skip_networking'],capture_output=True,text=True)
   if r.returncode==0:
    dr,off=r.stdout.strip().split('\t');assert pathlib.Path(dr).resolve()==run and off=='1';break
   if server.poll() is not None:raise RuntimeError('Private server failed')
   time.sleep(.1)
  else:raise RuntimeError('Timeout')
  if (run/'SYNTHETIC-COVERS').is_file():
   assert (run/'SYNTHETIC-COVERS').is_file()
   subprocess.run(client+['-e',"DROP DATABASE IF EXISTS vlexplosion_test; DROP USER IF EXISTS 'test_runner'@'localhost';"],check=True)
   subprocess.run(client+['-e','CREATE DATABASE vlexplosion_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'],check=True)
   for file in ['database/schema-pre005.sql','migrations/005_encyclopedia_instruments_up.sql','migrations/006_encyclopedia_credit_relations_up.sql','tests/credits/seed.sql']:apply(file)
  tables=[t for t in sql('SHOW TABLES').splitlines() if not t.startswith('CAA_')]
  def fingerprints():
   data={}
   for t in tables:
    columns=[r.split('\t')[0] for r in sql('SHOW COLUMNS FROM `'+t+'`').splitlines()]
    rows=sql('SELECT * FROM `'+t+'` ORDER BY '+','.join('`'+c+'`' for c in columns))
    data[t]={'rows_sha256':hashlib.sha256(rows.encode()).hexdigest(),'schema_sha256':hashlib.sha256(sql('SHOW CREATE TABLE `'+t+'`').encode()).hexdigest()}
   return data
  before=fingerprints();apply('migrations/007_cover_art_up.sql');apply('migrations/007_cover_art_up.sql');assert before==fingerprints()
  apply('migrations/007_cover_art_down.sql');apply('migrations/007_cover_art_down.sql');apply('migrations/007_cover_art_up.sql');print('PASS 007 up/up/down/down/up')
  subprocess.run(['php','tests/cover-art/checks.php'],cwd=ROOT,env=env,check=True)
  with (ROOT/'migrations/007_cover_art_down.sql').open() as data:r=subprocess.run(client+['vlexplosion_test'],stdin=data,capture_output=True,text=True)
  assert r.returncode and '007 in use' in r.stderr;print('PASS populated downgrade refused')
  apply('migrations/008_cover_visual_source_up.sql');apply('migrations/008_cover_visual_source_up.sql')
  subprocess.run(['php','tests/cover-art/visual-source.php'],cwd=ROOT,env=env,check=True)
  subprocess.run(['php','tests/cover-art/archive-dn.php'],cwd=ROOT,env=env,check=True)
  if a.browser:
   import socket
   with socket.socket() as probe:probe.bind(('127.0.0.1',8153))
   web=subprocess.Popen(['php','-S','127.0.0.1:8153','-t','public','tests/cover-art/router.php'],cwd=ROOT,env=env,stdout=log,stderr=log);time.sleep(1)
   subprocess.run([os.environ.get('VLEX_TEST_NODE','node'),'tests/cover-art/browser.cjs'],cwd=ROOT,env=env,check=True)
  print('PASS synthetic covers; private runtime: '+str(run))
 finally:
  for proc in [web,server]:
   if proc:
    proc.terminate()
    try:proc.wait(timeout=10)
    except subprocess.TimeoutExpired:proc.kill();proc.wait()
  log.close()
if __name__=='__main__':main()
