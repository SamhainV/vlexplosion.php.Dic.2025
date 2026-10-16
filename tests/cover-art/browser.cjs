const {chromium}=require('playwright');
const fs=require('fs');
const path=require('path');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.VLEX_TEST_BROWSER});
 let checks=0;const ok=(b,n)=>{if(!b)throw Error(n);console.log('PASS browser '+n);checks++};
 const context=await browser.newContext({viewport:{width:1440,height:1100}});const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 // Do not contact CDNs or music services in test browsers.
 await context.route('**/*',route=>(route.request().url().startsWith('http://127.0.0.1:8153/'))?route.continue():route.abort());
 const root='http://127.0.0.1:8153';
 await page.goto(root+'/login');await page.locator('input[name=login]').fill('fiction');await page.locator('input[name=password]').fill('fictional-password');await page.locator('button[type=submit]').click();await page.waitForURL('**/vinyls*');
 ok(await page.locator('#collection-title').isVisible(),'login and Premium collection');
 await page.goto(root+'/covers?id=900');ok(await page.locator('#caa-title').isVisible(),'automatic cover manager');
 await page.locator('.caa-preview').waitFor();ok(await page.locator('.caa-preview').evaluate(i=>i.complete&&i.naturalWidth>0),'authenticated local automatic image');
 await page.waitForTimeout(250);await page.screenshot({path:'tests/runtime/desktop-cover.png',fullPage:true});
 await page.goto(root+'/vinyls/show?id=900');ok(await page.locator('#vinyl-detail').isVisible(),'traditional detail retained');ok((await page.locator('.caa-origin').innerText()).includes('edición exacta'),'source identified');
 await page.goto(root+'/vinyls/edit?id=900');ok(await page.locator('#vinyl-form').isVisible(),'personal editor retained');ok(await page.getByText('Gestionar carátula automática (independiente de tus datos)',{exact:true}).isVisible(),'editor cover access');
 await page.goto(root+'/encyclopedia/copy?id=900');ok(await page.locator('#enc-record').isVisible(),'encyclopedia preserved');
 await page.goto(root+'/covers');ok((await page.locator('body').innerText()).includes('Vista previa'),'batch preview');
 await page.waitForTimeout(250);await page.screenshot({path:'tests/runtime/desktop-batch.png',fullPage:true});
 // Successful POST actions use real forms and the central CSRF check.
 await page.goto(root+'/covers?id=901');await page.locator('button[value=auto]').click();ok((await page.locator('body').innerText()).includes('Carátula automática seleccionada'),'automatic selection through POST');
 await page.locator('button[value=manual]').click();ok((await page.locator('body').innerText()).includes('Carátula aportada por ti'),'manual selection through POST');
 await page.goto(root+'/covers?id=900');await page.locator('button[value=fetch]').click();ok((await page.locator('body').innerText()).includes('Carátula disponible en la caché local'),'cached lookup through POST');
 await page.goto(root+'/covers');await page.locator('button[value=start]').click();ok((await page.locator('body').innerText()).includes('Estado: Pausado'),'batch prepared without network');
 await page.locator('[data-caa-run]').click();await page.locator('[data-caa-status]').filter({hasText:'Revisados 1/2'}).waitFor();await page.locator('[data-caa-stop]').click();await page.waitForLoadState('load');await page.getByText('1 de 2 · Los errores no alteran el disco ni su imagen.',{exact:true}).waitFor();ok(true,'batch stopped after current record');
 await page.locator('[data-caa-run]').click();await page.getByRole('heading',{name:'Estado: Terminado'}).waitFor({timeout:60000});ok((await page.locator('body').innerText()).includes('2 de 2'),'bounded five-record runner persisted two downloads');
 await page.waitForTimeout(1200);await page.goto(root+'/covers?id=900');await page.locator('details').evaluate(e=>e.open=true);await page.locator('button[value=fetch-group]').click();await page.locator('details').evaluate(e=>e.open=true);await page.locator('button[value=group]').click();ok((await page.locator('body').innerText()).includes('grupo de ediciones (aproximada)'),'approximate image explicitly confirmed');
 await page.goto(root+'/vinyls');ok((await page.locator('#vinyl-900 .caa-origin').innerText()).includes('aproximada'),'approximate origin shown on collection card');await page.locator('#vinyl-900 [data-vinyl-modal]').click();ok((await page.locator('#cover-modal-origin').innerText()).includes('aproximada'),'popup labels approximate source');await page.keyboard.press('Escape');ok(!(await page.locator('#cover-modal').isVisible()),'popup Escape unchanged');
 await page.goto(root+'/covers?id=900');await page.locator('button[value=auto]').click();
 // CSRF failure and owner isolation are exercised through actual HTTP routes.
 const denied=await context.request.post(root+'/covers/action',{form:{id:'900',action:'fetch'}});ok(denied.status()===419||denied.status()===403,'POST without CSRF rejected');
 const other=await context.request.get(root+'/covers?id=903');ok(other.status()===404,'other owner cannot inspect cover');
 const otherImage=await context.request.get(root+'/covers/image?id=903');ok(otherImage.status()===404,'other owner cannot read image');
 await page.setViewportSize({width:390,height:844});await page.goto(root+'/covers?id=900');ok(await page.locator('.caa-preview').isVisible(),'mobile image controls');
 ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile no horizontal overflow');await page.waitForTimeout(250);await page.screenshot({path:'tests/runtime/mobile-cover.png',fullPage:true});
 await page.goto(root+'/vinyls');ok(await page.locator('#collection-title').isVisible(),'mobile Premium collection');await page.waitForTimeout(250);await page.screenshot({path:'tests/runtime/mobile-collection.png',fullPage:true});
 ok(errors.length===0,'no JavaScript exceptions');
 await browser.close();console.log('BROWSER CHECKS '+checks);
})().catch(e=>{console.error(e);process.exit(1)});
