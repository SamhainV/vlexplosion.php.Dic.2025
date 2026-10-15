const {chromium}=require('playwright');
const fs=require('fs'),path=require('path');
const base=process.env.VLEX_ENC_BASE,runtime=process.env.VLEX_ENC_RUNTIME;
const report=JSON.parse(fs.readFileSync('docs/credits/checks.json','utf8'));
let checks=0;function ok(value,label){if(!value)throw Error(label);checks++;}
(async()=>{
 const temporary=path.resolve(__dirname,'../t');fs.mkdirSync(temporary,{recursive:true});
 const context=await chromium.launchPersistentContext(path.join(runtime,'credit-browser'),{
  executablePath:process.env.VLEX_TEST_BROWSER,headless:true,viewport:{width:1280,height:900},
  args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking','--disable-component-update'],
  env:{...process.env,TMPDIR:temporary,XDG_CONFIG_HOME:path.join(runtime,'chrome-config')}
 });
 try{
  await context.route('**/*',r=>new URL(r.request().url()).origin===new URL(base).origin?r.continue():r.abort());
  const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
  const submit=async button=>{await Promise.all([page.waitForNavigation(),button.click()]);};
  async function login(user){await page.goto(base+'/login');await page.locator('[name=login]').fill(user);await page.locator('[name=password]').fill('fictional-password');await submit(page.locator('button[type=submit]'));ok(new URL(page.url()).pathname==='/vinyls','login '+user);}
  const form=()=>page.locator('#credit-update-form');
  async function action(){await submit(form().getByRole('button'));}
  await login('fiction');
  await page.goto(base+'/vinyls/show?id='+report.fictional_copy);
  ok(await page.getByRole('link',{name:'Actualizar créditos de esta edición',exact:true}).count()===1,'explicit refresh on existing linked copy');
  await page.getByRole('link',{name:'Actualizar créditos de esta edición',exact:true}).click();
  let payload=await form().evaluate(f=>Object.fromEntries(new FormData(f)));
  let r=await page.request.post(base+'/encyclopedia/credits/action',{form:{...payload,operation:'unknown'}});ok(r.status()===422,'invalid operation refused');
  r=await page.request.post(base+'/encyclopedia/credits/action',{form:{...payload,confirm:'on',_csrf:'bad'}});ok(r.status()===403,'refresh CSRF enforced');
  await action();let jobUrl=page.url();ok(new URL(jobUrl).searchParams.get('job').length===64,'durable owned job');
  for(let i=0;i<5&&await form().locator('[name=operation][value=step]').count();i++)await action();
  ok(await form().locator('[name=operation][value=apply]').count()===1,'bounded collection reaches review');
  ok((await page.locator('body').textContent()).includes('contexto: 2'),'review separates context without presenting it as personnel');
  await page.reload();ok(await form().locator('[name=operation][value=apply]').count()===1,'review survives reload');
  await action();ok((await page.locator('#credit-update').textContent()).includes('Créditos manuales conservados'),'applied update reports protected manual correction');
  const completed=page.url();await page.goto(base+'/vinyls/show?id='+report.fictional_copy);
  let text=await page.locator('#enc-credits').textContent();
  ok(text.includes('Later manual correction')&&text.includes('Later instrument'),'manual credit remains after browser update');
  ok(text.includes('Custom instrument credit')&&text.includes('Printed album alias'),'raw instrument/source aliases retained in local view');
  ok(await page.locator('#enc-credits > .credit-section').count()===9,'nine role groups');
  ok(await page.getByRole('heading',{name:'CRÉDITOS Y PERSONAL',exact:true}).count()===1,'credits and personnel section');
  await page.locator('a[href^="#credits-track-"]').first().click();
  await page.waitForFunction(()=>document.querySelector(location.hash)?.open===true);
  ok(await page.locator('details[id^=credits-track-]').first().getAttribute('open')!==null,'track credit anchor opens disclosure');
  await page.locator('.credit-section').filter({hasText:'Fictional rights company'}).first().locator('summary').first().click();
  ok((await page.locator('#enc-credits').textContent()).includes('Fictional studio'),'place metadata available locally');
  let artist=page.locator('#enc-credits a[href^="/encyclopedia/artist"]').first();await artist.click();
  ok(await page.locator('a[href^="/encyclopedia/album"]').count()>0,'participant navigates to locally related albums');
  await page.goto(base+'/vinyls/show?id='+report.demo_copy);
  const callLog=path.join(runtime,'credit-transport-calls'),beforeGet=fs.readFileSync(callLog,'utf8');await page.reload();ok(fs.readFileSync(callLog,'utf8')===beforeGet,'opening and reloading saved public credits performs no provider lookups');
  text=await page.locator('#enc-credits').textContent();
  ok(['Martin L. Gore','Anton Corbijn','Flood','Nimbus'].every(n=>text.includes(n)),'authentic edition exposes composers artwork producers and manufacturing');
  ok(await page.locator('#enc-tracks tbody tr').count()===9,'authentic nine tracks preserved');
  await page.screenshot({path:path.join(runtime,'credits-desktop.png'),fullPage:true});
  await page.setViewportSize({width:390,height:844});
  ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'no mobile horizontal overflow');
  await page.screenshot({path:path.join(runtime,'credits-mobile.png'),fullPage:true});
  await page.setViewportSize({width:1280,height:900});
  // An unlinked traditional record still supports its ordinary CRUD and private draft.
  await page.goto(base+'/vinyls/create');await page.locator('#title').fill('Credit browser CRUD');await page.locator('[name="authors[]"]').fill('Fictional personal artist');await submit(page.locator('#vinyl-form button[type=submit]'));
  const id=new URL(page.url()).searchParams.get('highlight');ok(Number(id)>0,'ordinary CRUD create');
  await page.goto(base+'/vinyls/edit?id='+id);await page.locator('#title').fill('Credit browser edited');await submit(page.locator('#vinyl-form button[type=submit]'));
  await page.goto(base+'/vinyls/show?id='+id);ok((await page.locator('#vinyl-title').textContent()).includes('Credit browser edited'),'ordinary CRUD edit');
  ok(await page.getByRole('link',{name:'Actualizar créditos de esta edición',exact:true}).count()===0,'private draft cannot request MusicBrainz credit refresh');
  await page.goto(base+'/vinyls?q=Credit');ok((await page.locator('body').textContent()).includes('Credit browser edited'),'collection search works');
  const sorts=await page.locator('select[name=sort] option').evaluateAll(o=>o.map(x=>x.value));ok(sorts.length===12,'all twelve sort orders retained');
  for(const sort of sorts){const response=await page.goto(base+'/vinyls?sort='+encodeURIComponent(sort));ok(response.status()===200,'sort HTTP '+sort);}
  await page.goto(base+'/vinyls?q=Credit');const del=page.locator('.delete-vinyl-form').filter({has:page.locator('input[name=id][value="'+id+'"]')});await del.locator('button').click();ok(await page.locator('#delete-record-title').isVisible(),'existing delete popup');await page.locator('#delete-cancel').click();ok(await del.count()===1,'cancel retains copy');
  await del.locator('button').click();await Promise.all([page.waitForResponse(r=>r.url().endsWith('/vinyls/delete')&&r.request().method()==='POST'),page.locator('#delete-confirm').click()]);await page.waitForFunction(()=>document.body.textContent.includes('Vinilo eliminado correctamente.'));r=await page.goto(base+'/vinyls/show?id='+id);ok(r.status()===404,'ordinary CRUD delete isolated fictional record');
  await page.goto(base+'/vinyls');await submit(page.locator('form[action="/logout"] button'));await login('other');
  r=await page.goto(completed);ok(r.status()===404,'other owner cannot inspect progress or raw queue');
  r=await page.goto(base+'/encyclopedia/credits?id='+report.fictional_copy);ok(r.status()===404,'other owner cannot refresh copy');
  ok(errors.length===0,'no browser JavaScript exceptions');
  fs.writeFileSync(path.join(runtime,'credit-browser-summary.json'),JSON.stringify({checks,network:'blocked except loopback',demo:report.demo_copy},null,2));
  console.log('PASS '+checks+' credits browser/HTTP/CRUD checks; external network blocked.');
 }finally{await context.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
