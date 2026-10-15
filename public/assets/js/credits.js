/* Open the matching song and its ancestor disclosures when a track anchor is used. */
(() => {
 const reveal = () => {
  if (!/^#credits-track-\d+$/.test(location.hash)) return;
  const target = document.getElementById(location.hash.slice(1));
  if (!target) return;
  let node = target;
  while (node) { if (node.tagName === 'DETAILS') node.open = true; node = node.parentElement; }
  target.scrollIntoView({block:'start'});
 };
 window.addEventListener('hashchange',reveal);reveal();
})();

/* One explicit POST at a time. No provider polling or automatic retries. */
(() => {
 const screen=document.getElementById('credit-update');if(!screen)return;
 let submitting=false;
 screen.querySelectorAll('form[data-credit-action]').forEach(form=>form.addEventListener('submit',event=>{
  if(submitting){event.preventDefault();return;}
  submitting=true;
  if(event.submitter?.name==='confirm'){
   const input=document.createElement('input');input.type='hidden';input.name='confirm';input.value=event.submitter.value;form.append(input);
  }
  screen.querySelectorAll('button[type=submit]').forEach(button=>button.disabled=true);
  document.getElementById('credit-submit-status').textContent='Procesando este paso. Espera antes de realizar otra acción.';
 }));
 const wait=document.getElementById('credit-wait');
 if(wait){
  const tick=()=>{
   const seconds=Math.max(0,Math.ceil(Number(wait.dataset.deadline)-Date.now()/1000));
   wait.textContent=seconds?`Espera al menos ${seconds} segundos.`:'Ya puedes continuar la consulta.';
   if(!seconds){screen.querySelectorAll('[data-credit-waiting]').forEach(button=>button.disabled=false);clearInterval(timer);}
  };
  const timer=setInterval(tick,1000);tick();
 }
 window.addEventListener('pageshow',event=>{if(event.persisted)location.reload();});
})();
