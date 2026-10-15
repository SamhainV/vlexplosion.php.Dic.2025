const {chromium}=require('playwright');const fs=require('fs'),path=require('path');
const base=process.env.VLEX_ENC_BASE,run=process.env.VLEX_ENC_RUNTIME,state=JSON.parse(fs.readFileSync(path.join(run,'scope-states.json')));
let checks=0;function ok(v,m){if(!v)throw Error(m);checks++;}
(async()=>{
 const ctx=await chromium.launchPersistentContext(path.join(run,'view-browser'),{executablePath:process.env.VLEX_TEST_BROWSER,headless:true,viewport:{width:1280,height:1000},args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking'],env:{...process.env,TMPDIR:'/tmp',XDG_CONFIG_HOME:path.join(run,'chrome-config')}});
 try{
 await ctx.route('**/*',r=>new URL(r.request().url()).origin===new URL(base).origin?r.continue():r.abort());const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base+'/login');await page.locator('[name=login]').fill('fiction');await page.locator('[name=password]').fill('fictional-password');await Promise.all([page.waitForNavigation(),page.locator('button[type=submit]').click()]);ok(new URL(page.url()).pathname==='/vinyls','login');
 for(const width of [1280,390]){
  await page.setViewportSize({width,height:1000});
  for(const version of ['before','after']){
   const r=await page.goto(base+'/test/credits-'+version+'?id='+state.copy);ok(r.status()===200,'comparison HTTP '+version);
   ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'responsive '+version+' '+width);
   await page.screenshot({path:path.join(run,'credits-'+version+'-'+width+'.png'),fullPage:true});
  }
 }
 await page.setViewportSize({width:1280,height:1000});await page.goto(base+'/vinyls/show?id='+state.copy);
 ok(await page.locator('#enc-credits .credit-section > ul > .credit-group').count()===24,'24 main cards');ok((await page.locator('.credit-count-guide').textContent()).includes('30 participaciones documentadas'),'30 participation counter');ok((await page.locator('.credit-count-guide').textContent()).includes('20 identidades'),'20 identity counter');
 const performance=page.locator('#enc-credits > .credit-section').first();ok((await performance.locator('summary').first().textContent()).includes('2 tarjetas · 4 participaciones'),'category counts clear');
 const dave=page.locator('#enc-credits > .credit-section > ul > .credit-group').filter({has:page.locator('.credit-person strong',{hasText:'Dave Gahan'})});ok(await dave.count()===1,'one Dave card');ok((await dave.locator('.credit-function').textContent())==='Voz principal','lead vocals Spanish');await dave.locator('.credit-songs > summary').click();let songs=await dave.locator('.credit-songs li').allTextContents();ok(songs.length===2&&songs.some(s=>s.includes('Enjoy the Silence'))&&songs.some(s=>s.includes('World in My Eyes')),'exact linked songs');
 await dave.locator('.credit-originals > summary').click();ok(await dave.locator('.credit-originals .credit-provenance').count()===2,'two original relationship details');ok((await dave.locator('.credit-originals').textContent()).includes('Función original: vocal'),'English original retained');
 const rights=page.locator('#enc-credits > .credit-section').filter({has:page.locator('summary',{hasText:'Discográficas, editoriales y derechos'})});await rights.locator('summary').first().click();ok((await rights.textContent()).includes('su vigencia en esta edición no está verificada'),'work publishers qualified');ok((await rights.textContent()).includes('2021'),'later dated relation warned');
 ok(await page.locator('#enc-context').count()===1&&(await page.locator('#enc-context > summary').textContent()).includes('783'),'context separate');ok(!await page.locator('#enc-context').evaluate(e=>e.open),'context collapsed');ok((await page.locator('#enc-context').textContent()).includes('748 · ajenas a esta edición'),'748 other recordings separate');
 ok(!(await page.locator('#enc-credits').textContent()).includes('Relación a recording'),'other recordings not participants');
 const heading=page.getByRole('heading',{name:'CRÉDITOS Y PERSONAL',exact:true});ok(await heading.count()===1,'full detail route and identity intact');
 await page.screenshot({path:path.join(run,'credits-expanded-desktop.png'),fullPage:true});
 await page.setViewportSize({width:390,height:844});ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'full detail responsive');
 await page.goto(base+'/encyclopedia/credits?id='+state.copy);ok(await page.locator('#credit-update').getAttribute('data-phase')==='collecting','pending query untouched');ok(await page.locator('#last-saved-credits').count()===1,'saved update available');
 await page.goto(base+'/vinyls');ok(await page.locator('select[name=sort] option').count()===12,'twelve sort orders intact');
 const submit=async b=>Promise.all([page.waitForNavigation(),b.click()]);
 await page.goto(base+'/vinyls/create');await page.locator('#title').fill('Presentation browser CRUD');await page.locator('[name="authors[]"]').fill('Fictional artist');await submit(page.locator('#vinyl-form button[type=submit]'));const id=new URL(page.url()).searchParams.get('highlight');ok(Number(id)>0,'traditional create');
 await page.goto(base+'/vinyls/edit?id='+id);await page.locator('#title').fill('Presentation edited');await submit(page.locator('#vinyl-form button[type=submit]'));await page.goto(base+'/vinyls/show?id='+id);ok((await page.locator('#vinyl-title').textContent()).includes('Presentation edited'),'traditional edit');
 await page.goto(base+'/vinyls?q=Presentation');ok((await page.locator('body').textContent()).includes('Presentation edited'),'collection filter/search');
 const sorts=await page.locator('select[name=sort] option').evaluateAll(xs=>xs.map(x=>x.value));for(const sort of sorts){const response=await page.goto(base+'/vinyls?sort='+encodeURIComponent(sort));ok(response.status()===200,'sort '+sort);}
 await page.goto(base+'/vinyls?q=Presentation');const del=page.locator('.delete-vinyl-form').filter({has:page.locator('input[name=id][value="'+id+'"]')});await del.locator('button').click();ok(await page.locator('#delete-record-title').isVisible(),'delete popup');await page.locator('#delete-cancel').click();ok(await del.count()===1,'cancel retains copy');await del.locator('button').click();await Promise.all([page.waitForResponse(r=>r.url().endsWith('/vinyls/delete')&&r.request().method()==='POST'),page.locator('#delete-confirm').click()]);await page.waitForFunction(()=>document.body.textContent.includes('Vinilo eliminado correctamente.'));let deleted=await page.goto(base+'/vinyls/show?id='+id);ok(deleted.status()===404,'traditional delete');
 ok(errors.length===0,'no JavaScript exceptions');
 fs.writeFileSync(path.join(run,'presentation-browser-summary.json'),JSON.stringify({checks,external_network:'blocked',fictional:true},null,2));console.log('PASS '+checks+' presentation browser checks and four comparative screenshots.');
 }finally{await ctx.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
