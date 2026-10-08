#!/usr/bin/env python3
import os,pathlib,subprocess,socket,time,urllib.request,urllib.error,urllib.parse,http.cookiejar,re,uuid,base64,json
root=pathlib.Path(__file__).resolve().parent.parent
runtime=pathlib.Path(os.environ['VLEXPLOSION_TEST_SOCKET']).parent
key=uuid.uuid4().hex;env=os.environ.copy();env['VLEXPLOSION_TEST_KEY']=key
with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
base='http://127.0.0.1:'+str(port)
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args):return None
jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
count=0
def check(ok,label):
 global count
 if not ok:raise RuntimeError('FAIL: '+label)
 count+=1
def request(path,data=None,multipart=False):
 headers={'X-Test-Key':key}
 if data is not None:
  if multipart:
   boundary='fixture'+uuid.uuid4().hex;body=b''
   for k,v in data.items():
    values=v if isinstance(v,list) else [v]
    for val in values:body+=('--'+boundary+'\r\nContent-Disposition: form-data; name="'+k+'"\r\n\r\n'+str(val)+'\r\n').encode()
   png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg==')
   body+=('--'+boundary+'\r\nContent-Disposition: form-data; name="cover"; filename="fiction.png"\r\nContent-Type: image/png\r\n\r\n').encode()+png+('\r\n--'+boundary+'--\r\n').encode();headers['Content-Type']='multipart/form-data; boundary='+boundary
  else:body=urllib.parse.urlencode(data,doseq=True).encode();headers['Content-Type']='application/x-www-form-urlencoded'
 else:body=None
 try:
  response=opener.open(urllib.request.Request(base+path,body,headers),timeout=10)
 except urllib.error.HTTPError as e:response=e
 return response.status,response.read().decode('utf-8'),response.headers
def token(html):
 m=re.search(r'name="_csrf" value="([a-f0-9]{64})"',html)
 if not m:raise RuntimeError('No CSRF field')
 return m.group(1)
def state():return json.loads(request('/__test/state')[1])
with (runtime/'http.log').open('w') as log:
 server=subprocess.Popen(['php','-d','upload_tmp_dir='+str(runtime),'-S','127.0.0.1:'+str(port),'-t','public','tests/http_router.php'],cwd=root,env=env,stdout=log,stderr=log)
 try:
  for i in range(50):
   try:status,html,headers=request('/login');break
   except urllib.error.URLError:time.sleep(.1)
  else:raise RuntimeError('HTTP test server failed')
  check(status==200,'login GET');csrf=token(html)
  check('HttpOnly' in headers.get('Set-Cookie','') and 'SameSite=Lax' in headers.get('Set-Cookie',''),'HTTP cookie attributes')
  old=state()['session'];check(request('/login',{'login':'fiction','password':'fictional-password'})[0]==403,'HTTP missing CSRF')
  status,_,headers=request('/login',{'_csrf':csrf,'login':'fiction','password':'fictional-password'});check(status==302 and headers['Location']=='/vinyls','login redirect')
  check(state()['user']==7 and state()['session']!=old,'HTTP authenticated session renewal')
  status,html,_=request('/vinyls?sort=title_asc&q=Fiction&fav=1');check(status==200,'filtered collection HTTP');csrf=token(html)
  check(request('/vinyls/delete',{'id':'1'})[0]==403,'HTTP destructive CSRF rejection')
  check(request('/vinyls/update',{'id':'1'})[0]==403,'HTTP edit CSRF rejection')
  fields={'_csrf':csrf,'title':'HTTP create fiction','authors[]':['HTTP Author','HTTP Other'],'producer':'HTTP Producer','genre_id':'1','format_id':'1','condition_id':'1','record_label_id':'1','edition_id':'1','release_date':'2020','return_sort':'title_asc','return_q':'HTTP','return_fav':'0','return_desired':'0'}
  status,html,_=request('/vinyls/store',{**fields,'title[]':['bad'],'title':[]});check(status==422,'HTTP array title')
  before=state()['files'];status,body,_=request('/vinyls/store',{**fields,'title':'HTTP rollback upload'},True)
  check(status==500 and 'SQL' not in body and 'Stack trace' not in body,'HTTP SQL failure generic');check(state()['files']==before,'HTTP failed insert cleans uploaded file')
  status,_,headers=request('/vinyls/store',fields,True);check(status==302,'HTTP multipart create')
  location=headers['Location'];params=urllib.parse.parse_qs(urllib.parse.urlsplit(location).query);vid=params['highlight'][0]
  check(params.get('q')==['HTTP'] and params.get('sort')==['title_asc'],'preserve search context on create')
  check(state()['files']==before+1,'successful upload persisted')
  status,html,_=request('/vinyls/show?id='+vid);check(status==200 and 'HTTP Author' in html and 'HTTP Other' in html,'HTTP full multi-author detail')
  status,html,_=request('/vinyls/edit?id='+vid+'&return_sort=title_asc&q=HTTP');check(status==200 and 'vinyls/update' in html,'HTTP edit form');csrf=token(html)
  status,invalid,_=request('/vinyls/update',{**fields,'_csrf':csrf,'id':vid,'title':''})
  check(status==422 and 'HTTP Author' in invalid and 'id=\"current-cover\"' in invalid,'invalid edit retains authors and current cover')
  check('HTTP create fiction' in request('/vinyls/show?id='+vid)[1],'invalid edit leaves stored values intact')
  check(request('/vinyls/delete?id='+vid)[0] in (404,405),'GET cannot delete')
  check(request('/vinyls/show?id='+vid)[0]==200,'GET deletion attempt preserves record')
  edited={**fields,'_csrf':csrf,'id':vid,'title':'HTTP edited fiction'}
  status,_,_=request('/vinyls/update',edited);check(status==302,'HTTP update without new cover');check(state()['files']==before+1,'cover retained on edit')
  check('HTTP edited fiction' in request('/vinyls/show?id='+vid)[1],'edited title visible')
  status,_,_=request('/vinyls/update',edited,True);check(status==302 and state()['files']==before+1,'replace cover removes old unshared image')
  status,html,_=request('/vinyls');csrf=token(html)
  status,_,_=request('/vinyls/delete',{'_csrf':csrf,'id':vid,'return_sort':'newest'});check(status==302,'HTTP delete');check(state()['files']==before,'delete cleans owned cover')
  status,html,_=request('/vinyls');check('Vinilo eliminado correctamente.' in html,'truthful delete flash');check('Vinilo eliminado correctamente.' not in request('/vinyls?deleted=1')[1],'query cannot forge success')
  status,_,_=request('/vinyls/delete',{'_csrf':csrf,'id':vid});check(status==302,'delete absent redirect');check('No se ha eliminado ningún vinilo.' in request('/vinyls')[1],'absent delete message')
  status,html,_=request('/vinyls');csrf=token(html);sid=state()['session'];check(request('/logout',{'_csrf':csrf})[0]==302,'HTTP logout');check(state()['user'] is None and state()['session']!=sid,'HTTP logout invalidates')
  status,html,_=request('/login');csrf=token(html)
  check(request('/login',{'_csrf':csrf,'login':'other','password':'fictional-password'})[0]==302,'other fictional login')
  check(request('/vinyls/show?id=1')[0]==404,'HTTP other user detail denied')
  check(request('/vinyls/edit?id=1')[0]==404,'HTTP other user edit denied')
  status,html,_=request('/vinyls');csrf=token(html)
  check(request('/vinyls/update',{**fields,'_csrf':csrf,'id':'1'})[0]==404,'HTTP other user update denied')
  request('/logout',{'_csrf':csrf})
  status,html,_=request('/login');csrf=token(html);check(request('/login',{'_csrf':csrf,'login':'fiction','password':'fictional-password'})[0]==302,'login again')
  status,html,_=request('/vinyls');csrf=token(html);request('/__test/expire',{'_csrf':csrf});status,_,headers=request('/vinyls');check(status==302 and headers['Location']=='/login','HTTP idle expiry')
  status,html,_=request('/login');csrf=token(html)
  for i in range(5):check(request('/login',{'_csrf':csrf,'login':'HTTP-no-such-user','password':'fiction'})[0]==200,'HTTP failed login allowed')
  status,_,headers=request('/login',{'_csrf':csrf,'login':'HTTP-no-such-user','password':'fiction'});check(status==429 and headers.get('Retry-After')=='900','HTTP login throttle')
  temp=root/'tests/t';temp.mkdir(parents=True,exist_ok=True,mode=0o700)
  browser_env=env.copy();browser_env.update(VLEX_CRUD_RUNTIME=str(runtime),VLEX_CRUD_BASE=base,VLEX_CRUD_TEMP=str(temp),TMPDIR=str(temp))
  subprocess.run(['/home/amr/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node','tests/crud-browser.cjs'],cwd=root,env=browser_env,check=True)
  print('OK:',count,'HTTP checks; real multipart uploads; isolated test server and database.')
 finally:
  server.terminate()
  try:server.wait(timeout=10)
  except subprocess.TimeoutExpired:server.kill();server.wait()
