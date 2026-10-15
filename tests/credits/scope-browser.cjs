const {chromium}=require('playwright');const fs=require('fs'),path=require('path');
const base=process.env.VLEX_ENC_BASE,run=process.env.VLEX_ENC_RUNTIME;
const states=JSON.parse(fs.readFileSync(path.join(run,'scope-states.json'),'utf8'));
let checks=0;function ok(v,label){if(!v)throw Error(label);checks++;}
(async()=>{
 const context=await chromium.launchPersistentContext(path.join(run,'scope-browser'),{executablePath:process.env.VLEX_TEST_BROWSER,headless:true,viewport:{width:1280,height:900},args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking'],env:{...process.env,TMPDIR:'/tmp',XDG_CONFIG_HOME:path.join(run,'chrome-config')}});
 try{
 await context.route('**/*',r=>new URL(r.request().url()).origin===new URL(base).origin?r.continue():r.abort());
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 const submit=async b=>Promise.all([page.waitForNavigation(),b.click()]);
 await page.goto(base+'/login');await page.locator('[name=login]').fill('fiction');await page.locator('[name=password]').fill('fictional-password');await submit(page.locator('button[type=submit]'));ok(new URL(page.url()).pathname==='/vinyls','login');
 await page.goto(base+'/vinyls/show?id='+states.copy);
 ok(await page.locator('#enc-credits > .credit-section').count()===9,'nine participant sections retained');
 const main=await page.locator('#enc-credits').textContent();ok(!main.includes('Relación a recording')&&!main.includes('Relación a url'),'context excluded from participants');
 ok((await page.locator('#enc-context > summary').textContent()).includes('783'),'783 context links retained');
 ok(!await page.locator('#enc-context').evaluate(e=>e.open),'context collapsed by default');
 await page.screenshot({path:path.join(run,'scope-detail-desktop.png'),fullPage:true});
 await page.locator('#enc-context > summary').click();ok((await page.locator('#enc-context details > summary').first().textContent()).includes('748'),'other recordings grouped');
 ok(await page.locator('#enc-context .credit-card').count()===35,'only locally relevant context expanded in DOM');
 await page.screenshot({path:path.join(run,'scope-context.png'),fullPage:true});
 await page.setViewportSize({width:390,height:844});ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile width');await page.screenshot({path:path.join(run,'scope-detail-mobile.png'),fullPage:true});await page.setViewportSize({width:1280,height:900});
 const url=base+'/encyclopedia/credits?id='+states.copy;
 await page.goto(url+'&job='+states.first);ok(await page.locator('#credit-update').getAttribute('data-phase')==='complete','historical completed state');ok((await page.locator('#credit-update').textContent()).includes('Resumen histórico: 811 relaciones'),'historical count qualified');ok((await page.locator('#last-saved-credits').textContent()).includes('28 créditos'),'corrected credit count');
 await page.goto(url+'&job='+states.second);ok((await page.locator('#credit-status-title').textContent()).includes('cancelada'),'second canceled');ok(await page.locator('#last-saved-credits').count()===1,'saved result survives cancellation');
 await page.goto(url+'&job='+states.latest);ok(await page.locator('#credit-update').getAttribute('data-phase')==='complete','latest saved');await page.goto(url);await page.reload();ok(await page.locator('#credit-update').getAttribute('data-phase')==='complete','reopening keeps completed state');
 await page.screenshot({path:path.join(run,'scope-saved.png'),fullPage:true});
 const start=page.locator('form').filter({has:page.locator('input[name=operation][value=start]')});const payload=await start.evaluate(f=>Object.fromEntries(new FormData(f)));
 let response=await page.request.post(base+'/encyclopedia/credits/action',{form:{...payload,_csrf:'bad'}});ok(response.status()===403,'CSRF enforced');
 await start.locator('..').locator('summary').click();await submit(start.locator('button'));ok(await page.locator('#credit-update').getAttribute('data-phase')==='collecting','new operation explicit POST');ok(await page.locator('#new-credit-query').count()===1,'new operation distinguished');ok((await page.locator('#last-saved-credits').textContent()).includes('28 créditos'),'saved credits still visible');
 const job=page.url();await page.reload();ok(page.url()===job,'new checkpoint survives reload');ok((await page.locator('.credit-progress-counts').textContent()).replace(/\s/g,'').includes('Entidadesrecibidas0'),'new query received zero');await page.screenshot({path:path.join(run,'scope-new-query.png'),fullPage:true});
 await page.goto(base+'/vinyls');ok(await page.locator('select[name=sort] option').count()===12,'twelve sorts unchanged');
 await submit(page.locator('form[action="/logout"] button'));await page.goto(base+'/login');await page.locator('[name=login]').fill('other');await page.locator('[name=password]').fill('fictional-password');await submit(page.locator('button[type=submit]'));response=await page.goto(job);ok(response.status()===404,'other owner denied');ok(errors.length===0,'no JavaScript exceptions');
 fs.writeFileSync(path.join(run,'scope-browser-summary.json'),JSON.stringify({checks,external_network:'blocked',fictional_personal_data:true},null,2));console.log('PASS '+checks+' scope browser/HTTP checks.');
 }finally{await context.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
