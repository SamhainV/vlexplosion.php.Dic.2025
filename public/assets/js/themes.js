/* Synchronous head bootstrap: validate local preference before any page is painted.
   No server, identity, cookie, collection or provider data is read or written. */
(() => {
  'use strict';
  const key = 'vlexplosion.colorTheme.v1';
  const names = Object.freeze({archivo:'Archivo Musical', clasico:'Vinilo Clásico', borgona:'Borgoña', ambar:'Retro Ámbar'});
  const valid = value => Object.hasOwn(names, value);
  const read = () => { try { const value = localStorage.getItem(key); return valid(value) ? value : 'archivo'; } catch { return 'archivo'; } };
  const root = document.documentElement;
  root.dataset.vlTheme = read();
  let picker;
  const updateControls = () => {
    if (!picker) return;
    const selected = root.dataset.vlTheme;
    picker.querySelectorAll('[data-theme-choice]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.themeChoice === selected)));
    picker.querySelector('summary').setAttribute('aria-label', 'Tema actual: '+names[selected]+'. Cambiar tema');
  };
  const apply = (value, persist = false) => {
    if (!valid(value)) return;
    root.dataset.vlTheme = value;
    let saved = true;
    if (persist) { try { localStorage.setItem(key, value); } catch { saved = false; } }
    updateControls();
    return saved;
  };
  document.addEventListener('DOMContentLoaded', () => {
    picker = document.querySelector('[data-theme-picker]');
    if (!picker) return;
    picker.hidden = false;
    updateControls();
    const trigger = picker.querySelector('summary');
    const choices = [...picker.querySelectorAll('[data-theme-choice]')];
    choices.forEach(button => button.addEventListener('click', () => {
      const value = button.dataset.themeChoice;
      const saved = apply(value, true);
      document.querySelector('[data-theme-status]').textContent = 'Tema '+names[value]+' seleccionado.'+(saved?'':' No se ha podido guardar la preferencia.');
      picker.querySelector('[data-theme-storage]').textContent = saved ? 'Se recuerda en este navegador.' : 'Aplicado a esta página. El navegador no permite guardar la elección.';
      if (saved) { picker.open = false; trigger.focus({preventScroll:true}); }
    }));
    picker.addEventListener('keydown', event => {
      if (event.key === 'Escape') { picker.open = false; trigger.focus({preventScroll:true}); event.preventDefault(); return; }
      const index = choices.indexOf(document.activeElement);
      if (index < 0) return;
      const target = event.key === 'ArrowDown' ? (index+1)%choices.length : event.key === 'ArrowUp' ? (index+choices.length-1)%choices.length : event.key === 'Home' ? 0 : event.key === 'End' ? choices.length-1 : null;
      if (target !== null) { choices[target].focus(); event.preventDefault(); }
    });
    document.addEventListener('click', event => { if (!picker.contains(event.target)) picker.open = false; });
    document.addEventListener('focusin', event => { if (picker.open && !picker.contains(event.target)) picker.open = false; });
  });
  window.addEventListener('pageshow', () => apply(read()));
  window.addEventListener('storage', event => { if (event.key === key || event.key === null) apply(read()); });
})();
