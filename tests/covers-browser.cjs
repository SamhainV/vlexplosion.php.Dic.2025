const {chromium}=require('/home/amr/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs=require('fs');const path=require('path');
const runtime=process.env.VLEX_COVER_RUNTIME, base=process.env.VLEX_COVER_BASE;
let count=0;function check(ok,label){if(!ok)throw new Error('FAIL: '+label);count++;}
(async()=>{
 const browser=await chromium.launchPersistentContext(path.join(runtime,'chrome-profile'),{executablePath:'/usr/bin/google-chrome',headless:true,ignoreHTTPSErrors:true,viewport:{width:1280,height:900},extraHTTPHeaders:{'X-Test-Key':process.env.VLEX_COVER_KEY},env:{...process.env,TMPDIR:process.env.VLEX_COVER_TEMP},args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking','--disable-component-update','--disk-cache-dir='+path.join(runtime,'cache')]});
 try{
  const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  for(const id of [1,2,3,4,5]){
   await page.goto(base+'/vinyls/show?id='+id+'&return_page=2&return_sort=title_asc&q=Jazz&fav=1');
   await page.waitForFunction(()=>{const i=document.querySelector('#vinyl-cover');return i && i.complete && i.naturalWidth>0;});
   const src=await page.locator('#vinyl-cover').getAttribute('src');
   check(id===1?src.endsWith('/__fixture/existing.png'):src==='/assets/images/default-cover.webp','cover detail case '+id);
   check(await page.locator('h1').textContent()==='The Quiet Sessions','title preserved '+id);
  }
  await page.waitForFunction(()=>getComputedStyle(document.querySelector('#vinyl-detail')).maxWidth==='1040px');
  check((await page.locator('#vinyl-information').textContent()).includes('Green Room Records'),'record fields retained');
  const edit=await page.locator('#vinyl-edit-link').getAttribute('href');const back=await page.locator('#vinyl-return-link').getAttribute('href');
  check(edit.includes('id=5') && edit.includes('return_page=2') && edit.includes('q=Jazz'),'edit context');
  check(back.includes('page=2') && back.includes('sort=title_asc') && back.includes('fav=1'),'return context');
  await page.locator('#vinyl-edit-link').click();check((await page.locator('#title').inputValue())==='The Quiet Sessions','edit button works');
  await page.goto(base+'/vinyls/show?id=2&return_page=2&return_sort=title_asc&q=Jazz&fav=1');
  await page.locator('#vinyl-return-link').click();check(new URL(page.url()).pathname==='/vinyls','return button works');
  await page.waitForFunction(()=>[...document.querySelectorAll('article img')].every(i=>i.complete && i.naturalWidth>0));
  check(await page.locator('article img').count()===5,'five list covers');
  check(await page.locator('article img').evaluateAll(images=>images.slice(1).every(i=>i.getAttribute('src')==='/assets/images/default-cover.webp')),'list fallback cases');
  const button=page.locator('[data-vinyl-modal]').last();await button.click();
  await page.waitForFunction(()=>document.querySelector('#cover-modal-image').getAttribute('src')==='/assets/images/default-cover.webp');check(true,'popup fallback first error');
  await page.keyboard.press('Escape');await button.click();
  await page.waitForFunction(()=>document.querySelector('#cover-modal-image').getAttribute('src')==='/assets/images/default-cover.webp');check(true,'popup fallback remains active');
  let brokenFallbackRequests=0;
  await page.route('**/assets/images/default-cover.webp',route=>{brokenFallbackRequests++;return route.fulfill({status:404,body:''});});
  await page.locator('#cover-modal-image').evaluate(i=>{i.src='/broken-cover.webp?again=1';});
  await page.waitForTimeout(400);check(brokenFallbackRequests===1,'failed fallback requested once, no loop');
  await page.unroute('**/assets/images/default-cover.webp');
  for(const [name,width,height] of [['desktop',1280,900],['tablet',768,1024],['mobile',375,812]]){
   await page.setViewportSize({width,height});await page.goto(base+'/vinyls/show?id=2');
   await page.waitForFunction(()=>getComputedStyle(document.querySelector('#vinyl-detail')).maxWidth==='1040px');
   await page.waitForFunction(()=>document.querySelector('#vinyl-cover').naturalWidth>0);
   const cover=await page.locator('#vinyl-artwork').boundingBox(),info=await page.locator('#vinyl-information').boundingBox(),detail=await page.locator('#vinyl-detail').boundingBox();
   check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'no horizontal overflow '+name);
   check(width>=768?info.x>cover.x+cover.width:info.y>=cover.y+cover.height,'responsive placement '+name);
   if(name==='desktop')check(detail.width>=900 && detail.width<=1100,'desktop width');
   check(await page.locator('#vinyl-edit-link').isVisible() && await page.locator('#vinyl-return-link').isVisible(),'actions accessible '+name);
   await page.screenshot({path:path.join(runtime,name+'.png'),fullPage:true});
  }
  check(await page.locator('h1').count()===1,'one primary heading');check(await page.locator('dl dt').count()===7 && await page.locator('dl dd').count()===7,'semantic metadata');check(!!await page.locator('#vinyl-cover').getAttribute('alt'),'cover alternative text');
  await page.locator('#vinyl-edit-link').focus();check(await page.locator('#vinyl-edit-link').evaluate(e=>e===document.activeElement),'keyboard focus');
  await page.goto(base+'/vinyls/show?id=6');
  await page.waitForFunction(()=>getComputedStyle(document.querySelector('#vinyl-detail')).maxWidth==='1040px');
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'long title no horizontal overflow');
  check((await page.locator('h1').textContent()).length===65,'long title preserved without truncation');
  for (const [name,width,height] of [['desktop',1280,900],['mobile',375,812]]) {
   await page.setViewportSize({width,height});await page.goto(base+'/vinyls/edit?id=1');
   await page.waitForFunction(()=>getComputedStyle(document.querySelector('#vinyl-editor')).maxWidth==='1040px');
   check(await page.locator('h1').textContent()==='Editar vinilo','edit heading '+name);
   check(await page.locator('[role="dialog"]').count()===0,'edit is normal page '+name);
   check(await page.evaluate(()=>!document.querySelector('header').inert && !document.querySelector('footer').inert),'header active '+name);
   check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'editor no horizontal overflow '+name);
   check(await page.locator('#vinyl-editor').evaluate(e=>getComputedStyle(e).overflowY==='visible'),'no internal editor scroll '+name);
   check(await page.locator('#vinyl-fields').evaluate((e,w)=>getComputedStyle(e).gridTemplateColumns.split(' ').length===(w>=768?2:1),width),'editor columns '+name);
   await page.waitForFunction(()=>document.querySelector('#current-cover').naturalWidth>0);
   check((await page.locator('#current-cover').getAttribute('src')).endsWith('/__fixture/existing.png'),'current cover loaded '+name);
   check(await page.locator('[name="authors[]"]').count()===2,'multiple authors loaded '+name);
   await page.locator('#add-author').click();check(await page.locator('[name="authors[]"]').count()===3,'author added '+name);
   await page.locator('.remove-author').last().click();check(await page.locator('[name="authors[]"]').count()===2,'author removed '+name);
   await page.locator('#cover').setInputFiles(path.join(runtime,'fiction.png'));
   await page.waitForFunction(()=>document.querySelector('#coverPreview').naturalWidth>0);
   check((await page.locator('#coverPreview').getAttribute('src')).startsWith('blob:'),'new image preview '+name);
   await page.screenshot({path:path.join(runtime,'edit-'+name+'.png'),fullPage:true});
  }
  await page.goto(base+'/vinyls');await page.locator('.delete-vinyl-form button').first().click();
  check(await page.locator('#delete-dialog').isVisible(),'custom delete dialog');
  check(await page.locator('#delete-record-title').textContent()==='The Quiet Sessions','delete identifies title');
  check(await page.locator('#delete-cancel').evaluate(e=>e===document.activeElement),'cancel initial focus');
  await page.screenshot({path:path.join(runtime,'delete-dialog.png')});
  await page.keyboard.press('Escape');check(!await page.locator('#delete-dialog').isVisible(),'escape cancels delete');
  check(await page.locator('.delete-vinyl-form button').first().evaluate(e=>e===document.activeElement),'focus restored');
  check(errors.length===0,'no browser JavaScript errors');
  console.log('OK: '+count+' browser checks, desktop/tablet/mobile; fictional records only.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
