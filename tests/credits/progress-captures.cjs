const {chromium}=require('playwright'),fs=require('fs'),path=require('path');
const base=process.env.VLEX_ENC_BASE,runtime=process.env.VLEX_ENC_RUNTIME;
(async()=>{
 const short=fs.mkdtempSync('/tmp/vlex-captures-');
 const context=await chromium.launchPersistentContext(path.join(short,'profile'),{headless:true,executablePath:process.env.VLEX_TEST_BROWSER,viewport:{width:1280,height:960},args:['--no-sandbox','--disable-breakpad','--disable-crash-reporter','--disable-background-networking','--disable-component-update'],env:{...process.env,TMPDIR:short,XDG_CONFIG_HOME:path.join(short,'config')}});
 let checks=0;const ok=(v,label)=>{if(!v)throw Error(label);checks++;};
 try{
  await context.route('**/*',r=>new URL(r.request().url()).origin===new URL(base).origin?r.continue():r.abort());
  const page=await context.newPage();await page.goto(base+'/login');await page.locator('[name=login]').fill('fiction');await page.locator('[name=password]').fill('fictional-password');await Promise.all([page.waitForNavigation(),page.locator('button[type=submit]').click()]);
  const states=JSON.parse(fs.readFileSync(path.join(runtime,'progress-states.json'),'utf8'));const cancelled=JSON.parse(fs.readFileSync(path.join(runtime,'capture-cancelled.json'),'utf8'));
  const pages={collecting:'job='+states.collecting.job,paused:'job='+states.paused.job,ready:'id='+states.idle.copy,complete:'job='+states.ready.job,'no-new':'job='+states['no-new'].job,failed:'job='+states.failed.job,cancelled:'job='+cancelled.job};
  for(const [name,query] of Object.entries(pages)){
   const r=await page.goto(base+'/encyclopedia/credits?'+query);ok(r.status()===200,'capture HTTP '+name);
   ok(await page.locator('#credit-update').getAttribute('data-phase')===(name==='no-new'?'complete':name),'capture state '+name);
   await page.locator('#credit-update').screenshot({path:path.join(runtime,'progress-'+name+'.png')});
  }
  await page.setViewportSize({width:390,height:844});await page.goto(base+'/encyclopedia/credits?'+pages.paused);
  ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile page does not overflow');
  ok(await page.locator('.credit-actions').evaluate(el=>[...el.querySelectorAll('button,a')].every(control=>{const b=control.getBoundingClientRect(),panel=document.querySelector('#credit-update').getBoundingClientRect();return b.left>=panel.left+10&&b.right<=panel.right-10;})),'mobile controls stay within panel');
  ok(await page.locator('.credit-progress-counts').evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').filter(v=>parseFloat(v)>1).length===1),'mobile counts remain legible in one column');
  await page.locator('#credit-update').screenshot({path:path.join(runtime,'progress-paused-mobile.png')});
  fs.writeFileSync(path.join(runtime,'progress-capture-summary.json'),JSON.stringify({checks,read_only_browser_actions:true,external_network:'blocked',private_data:false},null,2));console.log('PASS '+checks+' final capture/layout checks; synthetic data only.');
 }finally{await context.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
