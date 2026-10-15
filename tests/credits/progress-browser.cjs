const {chromium}=require('playwright');
const fs=require('fs'),path=require('path');
const base=process.env.VLEX_ENC_BASE,runtime=process.env.VLEX_ENC_RUNTIME;
const states=JSON.parse(fs.readFileSync(path.join(runtime,'progress-states.json'),'utf8'));
let checks=0;function ok(v,label){if(!v)throw Error(label);checks++;}
(async()=>{
 const short=fs.mkdtempSync('/tmp/vlex-progress-');
 const context=await chromium.launchPersistentContext(path.join(short,'profile'),{headless:true,executablePath:process.env.VLEX_TEST_BROWSER,viewport:{width:1280,height:960},args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking','--disable-component-update'],env:{...process.env,TMPDIR:short,XDG_CONFIG_HOME:path.join(short,'config')}});
 try{
  await context.route('**/*',r=>new URL(r.request().url()).origin===new URL(base).origin?r.continue():r.abort());
  const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  const submit=async button=>{await Promise.all([page.waitForNavigation(),button.click()]);};
  const open=async key=>{const s=states[key];return page.goto(base+'/encyclopedia/credits?'+(s.job?'job='+s.job:'id='+s.copy));};
  const phase=()=>page.locator('#credit-update').getAttribute('data-phase');
  const shot=async name=>page.locator('#credit-update').screenshot({path:path.join(runtime,'progress-'+name+'.png')});
  await page.goto(base+'/login');await page.locator('[name=login]').fill('fiction');await page.locator('[name=password]').fill('fictional-password');await submit(page.locator('button[type=submit]'));ok(new URL(page.url()).pathname==='/vinyls','real test login');
  await open('collecting');ok(await phase()==='collecting','collecting clearly pending');ok(await page.locator('input[type=checkbox]').count()===0,'no repeated confirmation checkboxes');
  ok((await page.locator('#credit-update').innerText()).includes('todavía no se han guardado'),'collecting not confused with save');await shot('collecting');
  await page.reload();ok(await phase()==='collecting','reload preserves checkpoint');
  await open('paused');ok(await phase()==='paused','paused separate stage');ok(await page.getByRole('button',{name:'Continuar consulta',exact:true}).isDisabled(),'wait deadline disables continuation');ok((await page.locator('#credit-update').innerText()).includes('HTTP 503'),'precise provider error displayed');await shot('paused');
  await page.setViewportSize({width:390,height:844});ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'responsive no overflow');await shot('paused-mobile');await page.setViewportSize({width:1280,height:960});
  let cancelForm=page.locator('form').filter({has:page.locator('input[name=operation][value=cancel]')});let payload=await cancelForm.evaluate(f=>Object.fromEntries(new FormData(f)));
  let r=await page.request.post(base+'/encyclopedia/credits/action',{form:payload});ok(r.status()===422,'cancel confirmation protected');
  r=await page.request.post(base+'/encyclopedia/credits/action',{form:{...payload,confirm:'on',_csrf:'bad'}});ok(r.status()===403,'CSRF protected');
  await page.getByText('Cancelar consulta',{exact:true}).click();await submit(page.getByRole('button',{name:'Confirmar cancelación',exact:true}));ok(await phase()==='cancelled','safe cancellation explicit');await shot('cancelled');
  // Resume performs no provider request and retains the original wait/deadline.
  await submit(page.getByRole('button',{name:'Reanudar consulta',exact:true}));ok(await phase()==='paused','resume retains pause and checkpoint');
  await open('no-new');ok(await phase()==='complete','saved is definitive');ok((await page.locator('#credit-update').innerText()).includes('no ha aportado créditos nuevos'),'no new credits explicit');ok(await page.locator('time').count()>0,'real commit timestamp shown');await shot('no-new');
  await open('ready');ok(await phase()==='ready','ready is not saved');ok(await page.locator('.credit-progress-counts').evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').filter(width=>parseFloat(width)>1).length===3),'three balanced progress columns');ok(await page.getByRole('button',{name:'Guardar créditos',exact:true}).count()===1,'clear save action');await shot('ready');
  const form=page.locator('#credit-update-form');payload=await form.evaluate(f=>Object.fromEntries(new FormData(f)));
  // Delay the explicit save briefly to observe the incompatible controls being disabled.
  await page.route('**/encyclopedia/credits/action',async route=>{await new Promise(resolve=>setTimeout(resolve,350));await route.continue();});
  const saving=page.getByRole('button',{name:'Guardar créditos',exact:true}).click();await page.waitForFunction(()=>document.querySelector('#credit-update-form button')?.disabled===true);ok(true,'double submit blocked');await saving;
  await page.waitForFunction(()=>document.querySelector('#credit-update')?.dataset.phase==='complete');ok(await phase()==='complete','committed success visible');
  ok((await page.locator('#credit-update').innerText()).includes('Los créditos musicales se han actualizado correctamente'),'success unambiguous');await shot('complete');
  r=await page.request.post(base+'/encyclopedia/credits/action',{form:payload});ok(r.status()===200,'repeat explicit apply idempotent');
  await page.getByRole('link',{name:'Volver a la ficha',exact:true}).click();ok(new URL(page.url()).pathname==='/vinyls/show','return to collection detail');
  await open('idle');ok(await phase()==='idle','fresh start state');await submit(page.getByRole('button',{name:'Iniciar consulta',exact:true}));ok(await phase()==='collecting','start without checkbox');
  for(let i=0;i<5&&await phase()==='collecting';i++)await submit(page.getByRole('button',{name:'Continuar consulta',exact:true}));ok(await phase()==='ready','explicit bounded steps reach review');
  await open('failed');ok(await phase()==='failed','permanent failure separate from pause');ok(await page.getByRole('button',{name:'Continuar consulta',exact:true}).count()===0,'no retry for permanent failure');await shot('failed');
  await page.goto(base+'/vinyls');await submit(page.locator('form[action="/logout"] button'));await page.goto(base+'/login');await page.locator('[name=login]').fill('other');await page.locator('[name=password]').fill('fictional-password');await submit(page.locator('button[type=submit]'));
  r=await open('collecting');ok(r.status()===404,'other owner cannot read progress');
  ok(errors.length===0,'no browser JS errors');
  fs.writeFileSync(path.join(runtime,'progress-browser-summary.json'),JSON.stringify({checks,external_network:'blocked',screenshots:'fictional fixtures only'},null,2));console.log('PASS '+checks+' progress browser/HTTP checks; screenshots synthetic; no external requests.');
 }finally{await context.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
