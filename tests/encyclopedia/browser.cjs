const {chromium} = require('playwright');
const path = require('path');
const fs = require('fs');
const base = process.env.VLEX_ENC_BASE, runtime = process.env.VLEX_ENC_RUNTIME;
const live = process.env.VLEX_ENC_LIVE === '1';
const normal = process.env.VLEX_ENC_NORMAL === '1';
const temporary = path.resolve(__dirname,'../t');fs.mkdirSync(temporary,{recursive:true});
let checks = 0;
function check(ok, label) { if (!ok) throw Error(label); checks++; }
(async () => {
 const browser = await chromium.launchPersistentContext(path.join(runtime, 'enc-profile'), {
  executablePath: process.env.VLEX_TEST_BROWSER || '/usr/bin/google-chrome', headless: true,
  args: ['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking','--disable-component-update','--disk-cache-dir='+path.join(runtime,'enc-cache')],
  viewport: {width:1280,height:900}, env:{...process.env,TMPDIR:temporary,XDG_CONFIG_HOME:path.join(runtime,'xdg-config'),XDG_DATA_HOME:path.join(runtime,'xdg-data')}
 });
 try {
  if(normal) await browser.route('**/*',route=>new URL(route.request().url()).origin===new URL(base).origin?route.continue():route.abort());
  const page = await browser.newPage();
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  async function submit(button) {
   await Promise.all([page.waitForNavigation(), button.click()]);
   if(await page.getByRole('button',{name:'Reintentar consulta',exact:true}).count()) {
    throw Error('Unexpected retry in uninterrupted workflow: '+await page.locator('[role="alert"]').innerText());
   }
  }
  async function login(user) {
   await page.goto(base+'/login');await page.locator('[name="login"]').fill(normal?(user==='fiction'?process.env.VLEX_ENC_USER:process.env.VLEX_ENC_OTHER):user);
   await page.locator('[name="password"]').fill('fictional-password');
   await submit(page.locator('button[type="submit"]'));
   check(new URL(page.url()).pathname==='/vinyls','login '+user);
  }
  await login('fiction');
  check(await page.getByText('Desarrollo · colección ficticia',{exact:true}).count()===(normal?0:1),'development badge only in isolated development mode');
  check(await page.locator('a[href="/vinyls/ai"]').count()===0,'list has no unavailable AI action');
  const aiResponse=await page.goto(base+'/vinyls/ai');
  check(aiResponse.status()===200 && await page.getByRole('heading',{name:'Reconocimiento fotográfico no disponible'}).count()===1,'disabled photo route provides informative HTTP 200');
  check(await page.getByRole('link',{name:'Añadir mediante búsqueda musical',exact:true}).count()===1,'photo route offers working musical search');
  await page.goto(base+'/vinyls/create');
  check(await page.getByText('Añadir mediante búsqueda musical en MusicBrainz').count()===1,'manual entry links assisted search');
  await page.goto(base+'/encyclopedia/search');
  await page.locator('#enc-artist').fill(live?'Depeche Mode':'Fictional band');
  await page.locator('#enc-title').fill(live?'Some Great Reward':'Fictional album');
  if (!live) {
   const gate=path.resolve(runtime,'../application',path.basename(runtime),'musicbrainz/musicbrainz.lock');
   fs.mkdirSync(path.dirname(gate),{recursive:true});
   fs.writeFileSync(gate,JSON.stringify({next:Date.now()/1000+2,upstream:true}));
   const [limited]=await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}).click()]);
   check(limited.status()===429 && Number(limited.headers()['retry-after'])>0,'upstream cooldown returns HTTP 429 and Retry-After');
   check(await page.locator('#retry-title').inputValue()==='Fictional album' && await page.locator('#retry-artist').inputValue()==='Fictional band','rate error preserves editable search');
   await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'enc-rate-limit.png'),fullPage:true});
   await page.waitForTimeout(2100);
   await submit(page.getByRole('button',{name:'Reintentar consulta',exact:true}));
  } else {
   await submit(page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}));
  }
  check(await page.getByRole('heading',{name:'Álbumes candidatos'}).count()===1,'album search returns candidates');
  const groupId=live?'b3100982-db15-3a4b-a3ed-363c91cb40de':'00000000-0000-4000-8000-000000000001';
  let form=page.locator('form').filter({has:page.locator('input[name="id"][value="'+groupId+'"]')});
  await submit(form.getByRole('button'));
  if (!live) {
   const token=await page.locator('input[name="draft"]').inputValue();
   const gate=path.resolve(runtime,'../application',path.basename(runtime),'musicbrainz/musicbrainz.lock');
   fs.writeFileSync(gate,JSON.stringify({next:Date.now()/1000+1,upstream:true}));
   const [limited]=await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Mostrar ediciones',exact:true}).click()]);
   check(limited.status()===429 && await page.locator('input[name="draft"]').inputValue()===token && await page.locator('input[name="operation"]').inputValue()==='editions','rate error retains selected album and exact operation');
   check((await page.locator('[role="alert"]').innerText()).includes('1 segundo antes'),'singular wait message');
   await page.waitForTimeout(1100);
   await submit(page.getByRole('button',{name:'Reintentar consulta',exact:true}));
  } else {
   await submit(page.getByRole('button',{name:'Mostrar ediciones',exact:true}));
  }
  check(await page.getByRole('heading',{name:'Ediciones candidatas'}).count()===1,'edition choices shown');
  const releaseId=live?'3e3a9c1d-aee4-4f73-9376-fbe70e8366d8':'00000000-0000-4000-8000-000000000002';
  form=page.locator('form').filter({has:page.locator('input[name="id"][value="'+releaseId+'"]')});
  check(await form.count()===1,'reference edition listed');
  await submit(form.getByRole('button'));
  await submit(page.getByRole('button',{name:'Revisar datos',exact:true}));
  check(await page.locator('#enc-review').count()===1,'normalized review shown');
  check((await page.locator('.enc').first().innerText()).includes(live?'Something to Do':'Fictional song'),'track imported into preview');
  await page.locator('#review-title').fill(live?'My Some Great Reward':'Personal fictional correction');
  await page.locator('#review-notes').fill('PRIVATE_SEVEN <script>window.injected=1</script>');
  let payload=await page.locator('#enc-review').evaluate(f=>Object.fromEntries(new FormData(f)));
  let response=await page.request.post(base+'/encyclopedia/store',{form:payload,maxRedirects:0});
  check(response.status()===422,'unconfirmed save rejected');
  response=await page.request.post(base+'/encyclopedia/store',{form:{...payload,_csrf:'invalid',confirm:'on'},maxRedirects:0});
  check(response.status()===403,'CSRF blocks save');
  await page.locator('[name="confirm"]').check();
  payload=await page.locator('#enc-review').evaluate(f=>Object.fromEntries(new FormData(f)));
  await submit(page.getByRole('button',{name:'Guardar ejemplar',exact:true}));
  check(new URL(page.url()).pathname==='/encyclopedia/copy','save redirects to encyclopedia');
  const savedUrl=page.url();const id=new URL(savedUrl).searchParams.get('id');
  check(await page.locator('#enc-tracks tbody tr').count()===(live?9:1),'stored track count');
  check((await page.locator('#enc-credits').innerText()).includes(live?'Martin L. Gore':'Fictional composer'),'stored credits visible');
  check((await page.locator('#enc-private').innerText()).includes('PRIVATE_SEVEN'),'own private data visible');
  check(await page.evaluate(()=>window.injected)===undefined,'private text escaped, no XSS');
  check((await page.locator('h1').innerText())===(live?'Some Great Reward':'Fictional album'),'shared title preserved');
  response=await page.request.post(base+'/encyclopedia/store',{form:payload,maxRedirects:0});
  check(response.status()===302 && response.headers().location==='/encyclopedia/copy?id='+id,'duplicate submission returns same copy');
  response=await page.request.post(base+'/encyclopedia/store',{form:{...payload,title:'Different payload'},maxRedirects:0});
  check(response.status()===409,'reused confirmation cannot change payload');
  await page.getByRole('link',{name:'Consultar álbum',exact:true}).click();
  check(await page.getByRole('heading',{name:live?'Some Great Reward':'Fictional album',exact:true}).count()===1,'stored album entity view');
  await page.locator('a[href^="/encyclopedia/artist?"]').first().click();
  check(await page.getByRole('heading',{name:live?'Depeche Mode':'Fictional band',exact:true}).count()===1,'stored artist entity view');
  await page.goto(savedUrl);await page.getByRole('link',{name:'Consultar edición',exact:true}).click();
  check(await page.locator('tbody tr').count()===(live?9:1),'stored edition entity tracks');
  await page.goto(savedUrl);
  await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'encyclopedia-desktop.png'),fullPage:true});
  await page.setViewportSize({width:390,height:844});
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'mobile has no horizontal page overflow');
  await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'encyclopedia-mobile.png'),fullPage:true});
  await page.setViewportSize({width:1280,height:900});
  await submit(page.locator('form[action="/logout"] button'));await login('other');
  response=await page.goto(savedUrl);check(response.status()===404,'another user cannot open copy');
  check(!(await page.locator('body').innerText()).includes('PRIVATE_SEVEN'),'private data not leaked');
  await page.goto(base+'/vinyls/create');
  await page.locator('#title').fill('Manual encyclopedia fixture');
  await page.locator('[name="authors[]"]').fill('Manual author');
  await page.locator('#producer').fill('Manual producer');
  for(const field of ['genre_id','format_id','condition_id','record_label_id','edition_id']) { const select=page.locator('#'+field); await select.selectOption(normal?await select.locator('option').evaluateAll(options=>options.find(o=>o.value!=='').value):'1'); }
  await page.locator('#release_date').selectOption('2020');
  await submit(page.locator('#vinyl-form button[type="submit"]'));
  check(new URL(page.url()).pathname==='/vinyls','manual save retained');
  const manualId=new URL(page.url()).searchParams.get('highlight');
  await page.goto(base+'/encyclopedia/copy?id='+manualId);
  check((await page.locator('.enc').first().innerText()).includes('Borrador manual privado'),'manual copy uses encyclopedia extension');
  await page.goto(base+'/vinyls/edit?id='+manualId);await page.locator('#title').fill('Manual edited fixture');
  await submit(page.locator('#vinyl-form button[type="submit"]'));
  await page.goto(base+'/encyclopedia/copy?id='+manualId);
  check((await page.locator('#enc-private').innerText()).includes('Manual edited fixture'),'manual edit stays visible');
  if(!live){
   await page.goto(base+'/encyclopedia/search');await page.locator('#enc-catalogue').fill('TEST 19');
   await submit(page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}));
   check(await page.getByRole('heading',{name:'Ediciones candidatas'}).count()===1,'catalogue search targets releases');
   await page.goto(base+'/encyclopedia/search');await page.locator('#enc-barcode').fill('001234');
   await submit(page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}));
   check(await page.getByRole('heading',{name:'Ediciones candidatas'}).count()===1,'barcode search targets releases');
  }
  if(!live){
   await page.goto(base+'/vinyls');await submit(page.locator('form[action="/logout"] button'));await login('fiction');
   await page.goto(base+'/encyclopedia/search');await page.locator('#enc-artist').fill('Depeche Mode');await page.locator('#enc-title').fill('Enjoy the Silence / World in My Eyes');
   await submit(page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}));
   let reference=page.locator('form').filter({has:page.locator('input[name="id"][value="761a4a62-ce36-436f-8a66-5596328a1dc4"]')});
   await submit(reference.getByRole('button'));await submit(page.getByRole('button',{name:'Mostrar ediciones',exact:true}));
   check(await page.locator('.format-badge').filter({hasText:'CD'}).count()===1,'reference candidate explicitly CD');
   await page.locator('#format-preference').selectOption('vinyl');await submit(page.getByRole('button',{name:'Aplicar filtro',exact:true}));
   check(await page.getByRole('button',{name:'Elegir edición',exact:true}).count()===0,'vinyl-only excludes confirmed CD');
   const filterData=await page.locator('#format-filter').evaluate(f=>Object.fromEntries(new FormData(f)));
   const rejected=await page.request.post(base+'/encyclopedia/action',{form:{...filterData,operation:'release',id:'988a12b7-3b75-4fd5-8bb2-06b4c72e2c56'}});
   check(rejected.status()===422,'server rejects candidate hidden by selected filter');
   // An upstream gate must not prevent a local re-filter of already retrieved candidates.
   const gate=path.resolve(runtime,'../application',path.basename(runtime),'musicbrainz/musicbrainz.lock');
   fs.writeFileSync(gate,JSON.stringify({next:Date.now()/1000+60,upstream:true}));
   await page.locator('#format-preference').selectOption('all');await submit(page.getByRole('button',{name:'Aplicar filtro',exact:true}));
   check(await page.getByRole('button',{name:'Elegir edición',exact:true}).count()===1,'all supports restores CD without provider request');
   fs.writeFileSync(gate,JSON.stringify({next:0,upstream:false}));
   reference=page.locator('form').filter({has:page.locator('input[name="id"][value="988a12b7-3b75-4fd5-8bb2-06b4c72e2c56"]')});
   await submit(reference.getByRole('button'));check((await page.locator('.format-badge').innerText()).includes('no consta en vinilo'),'CD selection warns before review');
   await submit(page.getByRole('button',{name:'Revisar datos',exact:true}));
   check((await page.locator('.format-badge').innerText()).includes('CD'),'review identifies CD');
   await page.locator('[name="confirm"]').check();await submit(page.getByRole('button',{name:'Guardar ejemplar',exact:true}));
   const enjoyId=new URL(page.url()).searchParams.get('id');
   await page.goto(base+'/vinyls/show?id='+enjoyId);
   check(await page.locator('#enc-shared #enc-tracks tbody tr').count()===2,'normal collection detail includes actual two songs');
   const shared=await page.locator('#enc-shared').innerText();
   check(['CD','Mute','Reprise Records','Sire Records','9 18890-2','US','4:19','3:58','Flood','Martin L. Gore'].every(x=>shared.includes(x)),'normal detail includes formats, labels, catalogue, country, durations and credits');
   check(await page.locator('#enc-credits a').count()>0,'credit participants link to internal entities');
   await page.locator('#enc-credits a').filter({hasText:'Flood'}).first().click();
   check(await page.getByRole('link',{name:'Enjoy the Silence / World in My Eyes',exact:true}).count()>=1,'credit-only producer navigates to associated album');
   await page.goto(base+'/vinyls/edit?id='+enjoyId);
   check(await page.locator('#editor-shared #enc-tracks tbody tr').count()===2 && await page.getByRole('heading',{name:'Mi ejemplar · Datos personales editables',exact:true}).count()===1,'editor separates shared metadata from personal fields');
   check(await page.locator('#editor-personal').getAttribute('open')!==null && await page.locator('#editor-shared').getAttribute('open')===null,'personal first/open, musical collapsed');await page.locator('#editor-shared > summary').click();
   await page.locator('#title').fill('My personal Enjoy correction');await page.locator('[name="personal_country"]').fill('MY PRIVATE COUNTRY');await page.locator('[name="personal_catalogue"]').fill('MY PRIVATE CATALOGUE');await page.locator('[name="notes"]').fill('PRIVATE_NOTE <script>window.bad=1</script>');
   await submit(page.locator('#vinyl-form button[type="submit"]'));
   await page.goto(base+'/vinyls/edit?id='+enjoyId);
   check(await page.locator('#title').inputValue()==='My personal Enjoy correction'&&await page.locator('[name="personal_country"]').inputValue()==='MY PRIVATE COUNTRY'&&(await page.locator('[name="notes"]').inputValue()).includes('PRIVATE_NOTE'),'personal edits persist after reload');
   await page.locator('#editor-shared > summary').click();
   check((await page.locator('#enc-shared').innerText()).includes('Enjoy the Silence / World in My Eyes')&&!(await page.locator('#enc-shared').innerText()).includes('MY PRIVATE COUNTRY'),'shared metadata unaffected by personal corrections');
   await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'enjoy-editor-desktop.png'),fullPage:true});await page.setViewportSize({width:390,height:844});
   check(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'import editor no mobile horizontal overflow');
   await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'enjoy-editor-mobile.png'),fullPage:true});await page.setViewportSize({width:1280,height:900});
   await page.goto(base+'/vinyls/show?id='+enjoyId);await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'enjoy-detail.png'),fullPage:true});
   check(await page.evaluate(()=>window.bad)===undefined,'personal notes never execute script');
   await submit(page.locator('form[action="/logout"] button'));await login('other');
   const forbidden=await page.goto(base+'/vinyls/edit?id='+enjoyId);check(forbidden.status()===404&&!(await page.locator('body').innerText()).includes('PRIVATE_NOTE'),'other owner cannot open imported editor');
  }
  if(!live){
   await page.goto(base+'/vinyls');await submit(page.locator('form[action="/logout"] button'));await login('fiction');
   // Create one personal copy first, then associate it through the existing-record workflow.
   await page.goto(base+'/vinyls/create');await page.locator('#title').fill('Existing browser fixture');await page.locator('[name="authors[]"]').fill('Depeche Mode');
   await submit(page.locator('#vinyl-form button[type="submit"]'));const oldId=new URL(page.url()).searchParams.get('highlight');
   await page.goto(base+'/vinyls/show?id='+oldId);await page.getByRole('link',{name:'Vincular a MusicBrainz',exact:true}).click();
   check(await page.locator('#enc-title').inputValue()==='Existing browser fixture','link search starts with existing personal title');
   await page.locator('#enc-title').fill('Enjoy the Silence / World in My Eyes');await page.locator('#enc-year').fill('');await submit(page.getByRole('button',{name:'Buscar en MusicBrainz',exact:true}));
   let f=page.locator('form').filter({has:page.locator('input[name="id"][value="761a4a62-ce36-436f-8a66-5596328a1dc4"]')});await submit(f.getByRole('button'));await submit(page.getByRole('button',{name:'Mostrar ediciones',exact:true}));
   f=page.locator('form').filter({has:page.locator('input[name="id"][value="988a12b7-3b75-4fd5-8bb2-06b4c72e2c56"]')});await submit(f.getByRole('button'));await submit(page.getByRole('button',{name:'Revisar datos',exact:true}));
   check(await page.locator('#enc-link-review').count()===1&&(await page.locator('body').innerText()).includes('Existing browser fixture'),'existing association review compares retained personal fields');
   const linkData=await page.locator('#enc-link-review').evaluate(f=>Object.fromEntries(new FormData(f)));
   const unconfirmed=await page.request.post(base+'/encyclopedia/store',{form:linkData});check(unconfirmed.status()===422,'link requires explicit confirmation');
   await page.locator('#enc-link-review [name="confirm"]').check();await submit(page.getByRole('button',{name:'Confirmar vinculación',exact:true}));
   check(new URL(page.url()).searchParams.get('id')===oldId && await page.locator('#vinyl-title').innerText()==='Existing browser fixture','browser links same existing ID and preserves title');
   const retry=await page.request.post(base+'/encyclopedia/store',{form:{...linkData,confirm:'on'}});check(retry.status()===200,'association replay completes safely');
   await page.goto(base+'/encyclopedia/association?id='+oldId);const detach=page.locator('form[action="/encyclopedia/detach"]');const detachData=await detach.evaluate(f=>Object.fromEntries(new FormData(f)));
   const badCsrf=await page.request.post(base+'/encyclopedia/detach',{form:{...detachData,_csrf:'invalid',confirm:'on'}});check(badCsrf.status()===419||badCsrf.status()===403,'association CSRF blocked');
   await detach.locator('[name="confirm"]').check();await submit(page.getByRole('button',{name:'Desvincular edición',exact:true}));check(new URL(page.url()).searchParams.get('id')===oldId&&await page.getByRole('link',{name:'Vincular a MusicBrainz',exact:true}).count()===1,'detach retains same copy and makes linking available');
   await page.goto(base+'/encyclopedia/manual?id='+oldId);await page.locator('details').filter({has:page.locator('#prepare-musical')}).locator('summary').click();await page.locator('#prepare-musical [name="confirm"]').check();await submit(page.getByRole('button',{name:'Preparar borrador musical',exact:true}));
   async function musical(op,values){const form=page.locator('form[data-operation="'+op+'"]').last();await form.locator('xpath=..').locator('summary').click();for(const [name,value]of Object.entries(values)){const field=form.locator('[name="'+name+'"]');if(await field.evaluate(e=>e.tagName)==='SELECT')await field.selectOption(value);else await field.fill(value);}await form.locator('[name="confirm"]').check();await submit(form.getByRole('button'));}
   await musical('medium',{position:'1',format:'12" Vinyl',rpm:'33.333',diameter_inches:'12',color:'Black',weight_grams:'180'});
   let medium=await page.locator('form[data-operation="track"]').last().locator('[name="medium_id"] option').evaluateAll(o=>o.find(x=>x.value!=='').value);
   await musical('track',{medium_id:medium,position:'1',printed_number:'A1',side:'A',title:'Manual browser song',duration:'4:19'});
   let track=await page.locator('form[data-operation="recording"]').last().locator('[name="track_id"] option').evaluateAll(o=>o.find(x=>x.value!=='').value);
   await musical('recording',{track_id:track,title:'Manual browser recording',duration:'4:19'});
   let recording=await page.locator('form[data-operation="work"]').last().locator('[name="recording_id"] option').evaluateAll(o=>o.find(x=>x.value!=='').value);
   await musical('work',{recording_id:recording,title:'Manual browser composition'});
   await musical('credit',{target_key:'track:'+track,artist_name:'Browser fictional musician',artist_kind:'person',role:'performer',instrument:'piano'});
   await musical('label',{label_name:'Browser fictional label',catalogue_number:'BROWSER 001'});
   await musical('genre',{kind:'genre',name:'Browser electronic'});
   const invalidForm=page.locator('form[data-operation="track"]').last();const invalid=await invalidForm.evaluate(f=>Object.fromEntries(new FormData(f)));let response=await page.request.post(base+'/encyclopedia/manual/save',{form:{...invalid,confirm:'on',medium_id:medium,position:'2',title:'Keep invalid input',duration:'4:99'}});check(response.status()===422&&(await response.text()).includes('Keep invalid input'),'manual validation preserves entered form');
   const injection=await page.request.post(base+'/encyclopedia/manual/save',{form:{...invalid,operation:'credit',confirm:'on',target_key:'track;DROP TABLE:1',artist_name:'Injection',role:'producer'}});check(injection.status()===422,'manual SQL injection rejected');
   await page.locator('form[data-operation="track"]').first().locator('xpath=..').locator('summary').click();await page.locator('form[data-operation="credit"]').first().locator('xpath=..').locator('summary').click();
   await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'manual-desktop.png'),fullPage:true});await page.setViewportSize({width:390,height:844});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'manual editor no mobile overflow');await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'manual-mobile.png'),fullPage:true});await page.setViewportSize({width:1280,height:900});
   await page.goto(base+'/vinyls/show?id='+oldId);const music=await page.locator('#enc-shared').innerText();check(['Manual browser song','4:19','12" Vinyl','Browser fictional musician','piano','Browser fictional label','BROWSER 001','Browser electronic','Manual browser composition'].every(x=>music.includes(x)),'manual song credits instrument label genre and composition visible in normal detail');
   await page.locator('#enc-shared').getByRole('link',{name:'Manual browser composition',exact:true}).click();check((await page.locator('body').innerText()).includes('Manual browser recording'),'composition links to its recording');
   await page.goto(base+'/vinyls/edit?id='+oldId);check(await page.locator('#editor-personal').getAttribute('open')!==null&&await page.locator('#editor-shared').getAttribute('open')===null,'editor default disclosure states');await page.locator('#editor-shared > summary').focus();await page.keyboard.press('Enter');check(await page.locator('#editor-shared').getAttribute('open')!==null,'musical disclosure keyboard accessible');await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'legacy-linked-editor-desktop.png'),fullPage:true});await page.setViewportSize({width:390,height:844});await page.evaluate(()=>window.scrollTo(0,0));await page.screenshot({path:path.join(runtime,'legacy-linked-editor-mobile.png'),fullPage:true});await page.setViewportSize({width:1280,height:900});
   await submit(page.locator('form[action="/logout"] button'));await login('other');response=await page.goto(base+'/encyclopedia/manual?id='+oldId);check(response.status()===404,'foreign owner cannot access musical editor');response=await page.goto(base+'/encyclopedia/link?id='+oldId);check(response.status()===404,'foreign owner cannot start association');
  }
  check(errors.length===0,'no JavaScript exceptions');
  fs.writeFileSync(path.join(runtime,'browser-summary.json'),JSON.stringify({checks,live,copyId:id,manualId,groupId,releaseId},null,2));
  console.log('OK: '+checks+' NEW browser checks; '+(live?'REAL MusicBrainz':'FICTIONAL HTTP fixtures')+'; real isolated MariaDB; fictional collection.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
