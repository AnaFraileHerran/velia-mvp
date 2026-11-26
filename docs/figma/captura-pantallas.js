// Captura una pantalla de Velia en local y la envía a Figma como capas editables (23/11/2026).
// Uso (Claude): 1) pedir una URL de envío con la herramienta html_to_figma del MCP de Figma (fileKey jX0tVZ5A0uheE4VzqXw62X,
// nodeId de una sección de la página «Velia · Pantallas por flujo»); 2) abrir la pantalla en el navegador integrado con el tamaño
// de su dispositivo (dirección 1440×900, tablet 1194×834, móvil 390×844); 3) ejecutar en la página:
//    await (CAPTURA)(submitUrl, 'CÓDIGO · nombre')
// Copia el DOM tal y como se ve (valores de formularios, modales abiertos, lo oculto sigue oculto), aplana las media queries
// al tamaño actual y pasa las imágenes a base64. Las fotos de webs externas sin CORS salen como círculos grises.
(async (submitUrl, nombre) => {
  window.scrollTo(0, 0);
  await new Promise(r => setTimeout(r, 300));
  const flatten = (rules) => { let out = ''; for (const r of rules) {
    if (r instanceof CSSMediaRule) { if (matchMedia(r.conditionText || r.media.mediaText).matches) out += flatten(r.cssRules); }
    else if (window.CSSLayerBlockRule && r instanceof CSSLayerBlockRule) out += '@layer ' + r.name + '{' + flatten(r.cssRules) + '}';
    else if (r instanceof CSSSupportsRule) out += '@supports ' + r.conditionText + '{' + flatten(r.cssRules) + '}';
    else out += r.cssText;
    out += '\n'; } return out; };
  let css = '';
  for (const s of document.styleSheets) { try { css += flatten(s.cssRules); } catch (e) {} }
  const W = window.innerWidth, H = Math.max(document.documentElement.scrollHeight, window.innerHeight);
  const doc = document.documentElement.cloneNode(true);
  const la = document.querySelectorAll('body *'), ca = doc.querySelectorAll('body *');
  for (let i = 0; i < la.length; i++) {
    const cs = getComputedStyle(la[i]);
    if (cs.display === 'none') { ca[i].style.setProperty('display', 'none', 'important'); continue; }
    if (cs.position === 'fixed') { const r = la[i].getBoundingClientRect(); let h = r.height; if (Math.abs(h - window.innerHeight) < 3) h = H; const st = ca[i].style;
      st.setProperty('width', r.width + 'px', 'important'); st.setProperty('height', h + 'px', 'important'); st.setProperty('top', r.top + 'px', 'important');
      st.setProperty('left', r.left + 'px', 'important'); st.setProperty('right', 'auto', 'important'); st.setProperty('bottom', 'auto', 'important'); }
  }
  const live = document.querySelectorAll('input,textarea,select'), cl = doc.querySelectorAll('input,textarea,select');
  live.forEach((el, i) => { const c = cl[i]; if (!c) return;
    if (el.tagName === 'TEXTAREA') c.textContent = el.value;
    else if (el.tagName === 'SELECT') [...c.options].forEach((o, j) => o.toggleAttribute('selected', j === el.selectedIndex));
    else if (el.type === 'checkbox' || el.type === 'radio') c.toggleAttribute('checked', el.checked);
    else if (el.type !== 'password') c.setAttribute('value', el.value); });
  doc.querySelectorAll('script,link,style,noscript,template').forEach(n => n.remove());
  const imgs = doc.querySelectorAll('img'), limg = document.querySelectorAll('img');
  for (let i = 0; i < imgs.length; i++) { const li = limg[i], ci = imgs[i]; ci.removeAttribute('srcset'); ci.removeAttribute('loading');
    let ok = false;
    try { if (li && li.complete && li.naturalWidth) { const w = Math.max(1, Math.min(li.naturalWidth, (li.clientWidth || 64) * 2)), h = Math.round(w * li.naturalHeight / li.naturalWidth);
      const c = document.createElement('canvas'); c.width = w; c.height = h; c.getContext('2d').drawImage(li, 0, 0, w, h); ci.src = c.toDataURL('image/jpeg', 0.85); ok = true; } } catch (e) {}
    if (!ok) { try { const b = await fetch(li.currentSrc || li.src).then(r => { if (!r.ok) throw 0; return r.blob(); });
      ci.src = await new Promise(r => { const fr = new FileReader(); fr.onload = () => r(fr.result); fr.readAsDataURL(b); }); ok = true; } catch (e) {} }
    if (!ok) { ci.removeAttribute('src'); ci.setAttribute('style', (ci.getAttribute('style') || '') + ';background:#E5E7EB'); } }
  doc.querySelector('head').insertAdjacentHTML('beforeend', '<meta charset="utf-8"><title>' + nombre + '</title><style>' + css + '</style><style>html{width:' + W + 'px;min-height:' + H
    + 'px;overflow:hidden}body{width:' + W + 'px;min-height:' + H + 'px}.fi-sidebar{height:' + H + 'px !important;max-height:none !important}</style>');
  const html = '<!DOCTYPE html>' + doc.outerHTML;
  const r = await fetch(submitUrl, { method: 'POST', headers: { 'Content-Type': 'text/html' }, body: html });
  return { nombre, status: r.status, kb: Math.round(html.length / 1024), W, H, resp: (await r.text()).slice(0, 300) };
})
