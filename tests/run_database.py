#!/usr/bin/env python3
"""Fresh MariaDB process; no defaults, no TCP, no existing database or credentials."""
import os,pathlib,subprocess,time,uuid,pwd,json
from PIL import Image
root=pathlib.Path(__file__).resolve().parent.parent
runtime=root/'tests'/'runtime'/('db-'+uuid.uuid4().hex[:8]);runtime.mkdir(parents=True,mode=0o700)
socket=str(runtime/'s.sock')
for fmt,ext in [('JPEG','jpg'),('PNG','png'),('WEBP','webp')]:
 Image.new('RGB',(16,16),(25,90,50)).save(runtime/('fixture.'+ext),format=fmt)
if len(socket.encode())>100: raise RuntimeError('Test socket path too long')
user=pwd.getpwuid(os.geteuid()).pw_name
with (runtime/'initialize.log').open('w') as log:
 subprocess.run(['mariadb-install-db','--no-defaults','--datadir='+str(runtime),'--auth-root-authentication-method=normal','--skip-test-db','--tmpdir='+str(runtime)],cwd=root,stdout=log,stderr=log,check=True)
args=['mariadbd','--no-defaults','--datadir='+str(runtime),'--socket='+socket,'--pid-file='+str(runtime/'server.pid'),'--log-error='+str(runtime/'server.log'),'--tmpdir='+str(runtime),'--skip-networking','--skip-log-bin','--innodb-buffer-pool-size=32M','--user='+user]
with (runtime/'process.log').open('w') as log:
 server=subprocess.Popen(args,cwd=root,stdout=log,stderr=log)
 try:
  for _ in range(100):
   if server.poll() is not None: raise RuntimeError('Isolated MariaDB could not start; see test logs')
   if pathlib.Path(socket).exists():
    probe=subprocess.run(['mariadb','--no-defaults','--protocol=socket','--socket='+socket,'-uroot','-e','SELECT 1'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    if probe.returncode==0:break
   time.sleep(.1)
  else:raise RuntimeError('Isolated server timeout')
  with (root/'tests/schema.sql').open() as fixture:
   subprocess.run(['mariadb','--no-defaults','--protocol=socket','--socket='+socket,'-uroot'],stdin=fixture,stdout=subprocess.DEVNULL,check=True)
  env=os.environ.copy();env['VLEXPLOSION_TEST_SOCKET']=socket;env['VLEXPLOSION_TEST_RUN']=runtime.name;env['PYTHONDONTWRITEBYTECODE']='1'
  result=subprocess.run(['php','tests/database.php'],cwd=root,env=env,check=False)
  if result.returncode == 0:
   result=subprocess.run(['python3','tests/http_tests.py'],cwd=root,env=env,check=False)
  raise SystemExit(result.returncode)
 finally:
  server.terminate()
  try:server.wait(timeout=20)
  except subprocess.TimeoutExpired:server.kill();server.wait()
