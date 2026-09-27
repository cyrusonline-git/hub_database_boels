<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Boels Vraagbaak — eigen AI</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23FF6600'/%3E%3Ctext x='32' y='46' font-family='Arial,Helvetica,sans-serif' font-size='40' font-weight='bold' fill='%23fff' text-anchor='middle'%3EB%3C/text%3E%3C/svg%3E">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --oranje:#FF6600; --oranje-dk:#E55A00; --ink:#1B1F23; --grijs:#5f6670; --lijn:#e6e6e6; --zacht:#fff4ec; --groen:#1F7A4D; --geel:#8a6d00; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif; background:#f5f5f5; color:var(--ink); }
  header.top { background:var(--oranje); color:#fff; padding:12px 20px; display:flex; align-items:center; gap:14px; }
  header.top .b { width:38px; height:38px; border-radius:8px; background:#fff; color:var(--oranje); font-weight:800; font-size:22px; display:grid; place-items:center; }
  header.top h1 { font-size:1.15rem; margin:0; font-weight:700; }
  header.top a { color:#fff; margin-left:auto; text-decoration:none; font-size:.9rem; opacity:.9; }
  .banner { background:var(--zacht); border-bottom:1px solid #ffd7b8; padding:10px 20px; font-size:.9rem; color:#7a3d00; display:flex; gap:10px; align-items:center; }
  .banner i { font-size:1.1rem; }
  .wrap { max-width:1180px; margin:0 auto; padding:20px; display:grid; grid-template-columns:1fr 320px; gap:20px; }
  @media (max-width: 900px) { .wrap { grid-template-columns:1fr; } }
  .kaart { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); padding:18px 20px; }
  form.vraag { display:flex; gap:10px; }
  form.vraag input { flex:1; font-size:1.05rem; padding:12px 14px; border:1px solid #cfcfcf; border-radius:10px; }
  form.vraag input:focus { outline:none; border-color:var(--oranje); box-shadow:0 0 0 3px rgba(255,102,0,.15); }
  form.vraag button { background:var(--oranje); color:#fff; border:0; border-radius:10px; padding:0 20px; font-weight:700; font-size:1rem; cursor:pointer; }
  form.vraag button:hover { background:var(--oranje-dk); }
  .sug { position:relative; }
  .sug ul { position:absolute; left:0; right:0; top:100%; background:#fff; border:1px solid var(--lijn); border-radius:10px; margin:4px 0 0; padding:4px 0; list-style:none; z-index:5; box-shadow:0 6px 18px rgba(0,0,0,.12); max-height:280px; overflow:auto; display:none; }
  .sug ul li { padding:8px 12px; cursor:pointer; font-size:.95rem; }
  .sug ul li small { color:var(--grijs); margin-left:6px; }
  .sug ul li:hover, .sug ul li.actief { background:var(--zacht); }
  .chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
  .chip { border:1px solid var(--lijn); background:#fafafa; border-radius:999px; padding:4px 11px; font-size:.82rem; cursor:pointer; }
  .chip:hover { border-color:var(--oranje); color:var(--oranje-dk); }
  .chip.filter { cursor:default; background:#fff; }
  .chip.filter.gegokt { border-style:dashed; color:var(--grijs); }
  .antwoord { margin-top:16px; }
  .a-kop { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
  .a-kop h2 { font-size:1.05rem; margin:0 0 4px; font-weight:700; }
  .a-bron { font-size:.8rem; color:var(--grijs); }
  .getal { font-size:2.6rem; font-weight:800; color:var(--oranje-dk); line-height:1.1; margin:12px 0 4px; }
  .getal small { font-size:1rem; color:var(--grijs); font-weight:600; margin-left:6px; }
  .subs { display:flex; flex-wrap:wrap; gap:14px; margin:6px 0 8px; }
  .subs div { background:#fafafa; border:1px solid var(--lijn); border-radius:8px; padding:6px 10px; font-size:.85rem; }
  .subs div b { display:block; font-size:1rem; }
  .tbl { overflow-x:auto; margin-top:10px; }
  table { border-collapse:collapse; width:100%; font-size:.9rem; }
  th, td { text-align:left; padding:7px 10px; border-bottom:1px solid var(--lijn); white-space:nowrap; }
  th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--grijs); background:#fafafa; }
  td.num { text-align:right; font-variant-numeric:tabular-nums; }
  details { margin-top:10px; font-size:.88rem; color:var(--grijs); }
  details summary { cursor:pointer; color:var(--ink); }
  .acties { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
  .knop { border:1px solid var(--lijn); background:#fff; border-radius:8px; padding:6px 12px; font-size:.85rem; cursor:pointer; color:var(--ink); text-decoration:none; }
  .knop:hover { border-color:var(--oranje); }
  .melding { background:#fff8e6; border:1px solid #ffe2a0; color:#6b4f00; border-radius:10px; padding:12px 14px; margin-top:14px; }
  .fout { background:#fdecec; border:1px solid #f5b5b5; color:#8a1f1f; }
  .rechts h3 { font-size:.8rem; text-transform:uppercase; letter-spacing:.05em; color:var(--grijs); margin:0 0 8px; }
  .rechts details { margin:0 0 6px; color:var(--ink); }
  .rechts details summary { font-weight:600; font-size:.92rem; padding:6px 0; }
  .rechts ul { list-style:none; padding:0 0 4px 8px; margin:0; }
  .rechts li { font-size:.86rem; padding:4px 0; cursor:pointer; color:#333; }
  .rechts li:hover { color:var(--oranje-dk); }
  .rechts li .rol { font-size:.7rem; color:var(--grijs); border:1px solid var(--lijn); border-radius:6px; padding:0 5px; margin-left:4px; }
  .laden { color:var(--grijs); font-size:.9rem; margin-top:12px; }
  .hist { margin-top:14px; }
  .hist .item { border-top:1px solid var(--lijn); padding:8px 0; font-size:.88rem; display:flex; justify-content:space-between; gap:10px; }
  .hist .item span { color:var(--grijs); }
  .hist .item a { color:var(--oranje-dk); cursor:pointer; }
  footer { text-align:center; color:#999; font-size:.8rem; padding:14px; }
</style>
</head>
<body>
<header class="top">
  <div class="b">B</div>
  <h1>Boels Vraagbaak <span style="font-weight:400;opacity:.85">· eigen AI</span></h1>
  <a href="{{ url('/') }}"><i class="bi bi-grid-3x3-gap me-1"></i> Terug naar CORE</a>
</header>
<div class="banner"><i class="bi bi-shield-lock"></i><div><strong>Eigen AI van Boels Industrial.</strong> {{ config('vraagbaak.banner') }}</div></div>

<div class="wrap">
  <main>
    <div class="kaart">
      <form class="vraag" id="f" autocomplete="off">
        <div class="sug" style="flex:1">
          <input id="q" placeholder="Stel je vraag, bijvoorbeeld: hoeveel liter heeft Rotterdam getankt deze maand?" aria-label="Vraag">
          <ul id="sug"></ul>
        </div>
        <button type="submit">Vraag</button>
      </form>
      <div class="chips" id="voorbeelden"></div>
      <div id="uit"></div>
    </div>
    <div class="kaart hist" id="hist" style="margin-top:16px;display:none">
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:var(--grijs);margin:0 0 4px">Eerder gevraagd</h3>
      <div id="histlijst"></div>
    </div>
  </main>
  <aside class="rechts">
    <div class="kaart">
      <h3><i class="bi bi-lightbulb me-1"></i>Wat kun je vragen?</h3>
      <div id="catalogus"><div class="laden">Catalogus laden…</div></div>
    </div>
    <div class="kaart" style="margin-top:16px">
      <h3><i class="bi bi-info-circle me-1"></i>Hoe werkt het?</h3>
      <p style="font-size:.86rem;margin:0;color:#444">Je vraag wordt herkend op trefwoorden (vestiging, periode, onderwerp) en daarna <em>live</em> berekend in de databases van onze eigen apps. Elk antwoord laat zien hoe het is berekend en over welke periode en vestiging. Rotterdam, Rdam of Europoort? Werkt allemaal.</p>
      <p style="font-size:.86rem;margin:8px 0 0;color:#444">Vraag niet herkend? Kies een van de voorstellen of laat een opmerking achter; de beheerder leert de vraagbaak nieuwe vragen.</p>
    </div>
  </aside>
</div>
<footer>Boels Industrial · Boels Vraagbaak · alle berekeningen op de eigen server</footer>

<script>
window.CORE_CSRF = "{{ csrf_token() }}";
var CONFIG = { api: "{{ route('tools.vraagbaak.api') }}", naam: {!! json_encode(auth()->user()->name, JSON_UNESCAPED_UNICODE) !!} };
</script>
@verbatim
<script>
(function(){
  var q = document.getElementById('q'), sug = document.getElementById('sug'), uit = document.getElementById('uit');
  var hist = [];
  function api(actie, data, method){
    var o = { credentials:'same-origin', headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':window.CORE_CSRF} };
    if (data){ o.method='POST'; o.body=JSON.stringify(data); }
    return fetch(CONFIG.api + '?action=' + encodeURIComponent(actie) + (method||''), o).then(function(r){ return r.json().then(function(j){ if(!r.ok && !j.ok){ throw new Error(j.fout || j.message || ('Fout ' + r.status)); } return j; }); });
  }
  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function fmt(v){ if (typeof v === 'number'){ return v.toLocaleString('nl-NL', {maximumFractionDigits:2}); } return esc(v); }
  function isNum(v){ return typeof v === 'number'; }

  // Catalogus rechts + voorbeeldchips
  api('catalogus').then(function(j){
    var html = '';
    (j.bronnen||[]).forEach(function(b, i){
      html += '<details' + (i<2?' open':'') + '><summary>' + esc(b.naam) + ' <small style="color:#999;font-weight:400">(' + b.vragen.length + ')</small></summary><ul>';
      b.vragen.forEach(function(v){ html += '<li data-id="' + esc(v.id) + '" data-titel="' + esc(v.titel) + '">' + esc(v.titel) + (v.rol!=='iedereen'?'<span class="rol">' + esc(v.rol) + '</span>':'') + '</li>'; });
      html += '</ul></details>';
    });
    document.getElementById('catalogus').innerHTML = html || '<div class="laden">Je hebt nog geen toegang tot apps met een vraagbaak.</div>';
    document.querySelectorAll('#catalogus li').forEach(function(li){ li.addEventListener('click', function(){ q.value = li.dataset.titel; stel(li.dataset.titel, li.dataset.id); }); });
    var vb = ['hoeveel liter heeft Rotterdam getankt deze maand', 'welke inhuur is te laat retour', 'hoeveel schades vorige maand in area zuid', 'wie heeft volgende week dienst', 'top 10 machines op brandstof dit jaar'];
    document.getElementById('voorbeelden').innerHTML = vb.map(function(t){ return '<span class="chip" data-t="' + esc(t) + '">' + esc(t) + '</span>'; }).join('');
    document.querySelectorAll('#voorbeelden .chip').forEach(function(c){ c.addEventListener('click', function(){ q.value = c.dataset.t; stel(c.dataset.t); }); });
    if ((j.recent||[]).length){ hist = j.recent.map(function(t){ return {vraag:t}; }); toonHist(); }
  }).catch(function(e){ document.getElementById('catalogus').innerHTML = '<div class="laden">' + esc(e.message) + '</div>'; });

  // Autocomplete
  var timer = null, actief = -1;
  q.addEventListener('input', function(){
    clearTimeout(timer); actief = -1;
    var t = q.value.trim();
    if (t.length < 2){ sug.style.display='none'; return; }
    timer = setTimeout(function(){
      api('suggesties', null, '&q=' + encodeURIComponent(t)).then(function(j){
        if (!j.suggesties || !j.suggesties.length){ sug.style.display='none'; return; }
        sug.innerHTML = j.suggesties.map(function(s){ return '<li data-id="' + esc(s.id) + '" data-titel="' + esc(s.titel) + '">' + esc(s.titel) + '<small>' + esc(s.bron) + '</small></li>'; }).join('');
        sug.style.display = 'block';
        sug.querySelectorAll('li').forEach(function(li){ li.addEventListener('mousedown', function(ev){ ev.preventDefault(); q.value = li.dataset.titel; sug.style.display='none'; stel(q.value, li.dataset.id); }); });
      });
    }, 180);
  });
  q.addEventListener('keydown', function(ev){
    var items = sug.querySelectorAll('li');
    if (sug.style.display !== 'block' || !items.length) return;
    if (ev.key === 'ArrowDown'){ actief = Math.min(actief+1, items.length-1); } else if (ev.key === 'ArrowUp'){ actief = Math.max(actief-1, 0); } else if (ev.key === 'Enter' && actief >= 0){ ev.preventDefault(); items[actief].dispatchEvent(new Event('mousedown')); return; } else if (ev.key === 'Escape'){ sug.style.display='none'; return; } else { return; }
    items.forEach(function(li, i){ li.classList.toggle('actief', i === actief); });
  });
  q.addEventListener('blur', function(){ setTimeout(function(){ sug.style.display='none'; }, 150); });
  document.getElementById('f').addEventListener('submit', function(ev){ ev.preventDefault(); sug.style.display='none'; stel(q.value.trim()); });

  function toonHist(){
    var h = document.getElementById('hist'); var l = document.getElementById('histlijst');
    if (!hist.length){ h.style.display='none'; return; }
    h.style.display='block';
    l.innerHTML = hist.slice(0,8).map(function(x){ return '<div class="item"><a data-t="' + esc(x.vraag) + '">' + esc(x.vraag) + '</a><span>' + (x.kort ? esc(x.kort) : '') + '</span></div>'; }).join('');
    l.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', function(){ q.value = a.dataset.t; stel(a.dataset.t); }); });
  }

  function stel(tekst, kies){
    if (!tekst && !kies) return;
    uit.innerHTML = '<div class="laden"><i class="bi bi-hourglass-split"></i> Even rekenen…</div>';
    api('vraag', { vraag: tekst, kies: kies || null }).then(function(j){
      if (j.status === 'ok'){ toon(j, tekst); hist.unshift({vraag: tekst || j.vraag.titel, kort: j.resultaat.type === 'getal' ? fmt(j.resultaat.waarde) + ' ' + j.resultaat.eenheid : j.resultaat.rijen.length + ' rijen'}); toonHist(); return; }
      var html = '<div class="melding' + (j.status==='fout'?' fout':'') + '">' + esc(j.melding) + '</div>';
      if (j.kandidaten && j.kandidaten.length){
        html += '<div class="chips">' + j.kandidaten.map(function(k){ return '<span class="chip" data-id="' + esc(k.id) + '">' + esc(k.titel) + ' <small style="color:#999">· ' + esc(k.bron_naam||k.bron) + '</small></span>'; }).join('') + '</div>';
      }
      if (j.status === 'onbekend'){ html += '<div class="acties"><button class="knop" id="fb">Doorgeven aan beheerder</button></div>'; }
      uit.innerHTML = html;
      uit.querySelectorAll('.chip[data-id]').forEach(function(c){ c.addEventListener('click', function(){ stel(tekst, c.dataset.id); }); });
      var fb = document.getElementById('fb'); if (fb){ fb.addEventListener('click', function(){ api('feedback', {log_id: j.log_id, feedback: 'graag toevoegen'}).then(function(){ fb.textContent = 'Doorgegeven, bedankt!'; fb.disabled = true; }); }); }
    }).catch(function(e){ uit.innerHTML = '<div class="melding fout">' + esc(e.message) + '</div>'; });
  }

  function toon(j, tekst){
    var r = j.resultaat;
    var html = '<div class="antwoord"><div class="a-kop"><div><h2>' + esc(j.vraag.titel) + '</h2><div class="a-bron">Bron: ' + j.bronnen.map(function(b){ return '<a href="' + esc(b.url) + '" target="_blank" style="color:inherit">' + esc(b.naam) + '</a>'; }).join(' + ') + ' · berekend in ' + j.duur_ms + ' ms</div></div></div>';
    html += '<div class="chips">' + j.filters.map(function(f){ return '<span class="chip filter' + (f.gegokt?' gegokt':'') + '" title="' + (f.gegokt?'Niet in je vraag genoemd, standaard gekozen':'Uit je vraag gehaald') + '">' + esc(f.label) + ': <b>' + esc(f.waarde) + '</b></span>'; }).join('') + '</div>';
    if (r.type === 'getal'){ html += '<div class="getal">' + fmt(r.waarde) + '<small>' + esc(r.eenheid) + '</small></div>'; }
    if (r.type === 'tekst'){ html += '<div class="melding">' + esc(r.waarde) + '</div>'; }
    if (r.sub && Object.keys(r.sub).length){ html += '<div class="subs">' + Object.keys(r.sub).map(function(k){ return '<div><b>' + fmt(r.sub[k]) + '</b>' + esc(k) + '</div>'; }).join('') + '</div>'; }
    if (r.kolommen && r.kolommen.length){
      html += '<div class="tbl"><table><thead><tr>' + r.kolommen.map(function(k){ return '<th>' + esc(k) + '</th>'; }).join('') + '</tr></thead><tbody>';
      if (!r.rijen.length){ html += '<tr><td colspan="' + r.kolommen.length + '" style="color:#999">Geen gegevens gevonden voor deze selectie.</td></tr>'; }
      r.rijen.forEach(function(rij){ html += '<tr>' + rij.map(function(c){ return '<td' + (isNum(c)?' class="num"':'') + '>' + fmt(c) + '</td>'; }).join('') + '</tr>'; });
      html += '</tbody></table></div>';
    }
    html += '<details><summary><i class="bi bi-calculator"></i> Hoe is dit berekend?</summary><div style="margin-top:6px">' + esc(r.uitleg) + '</div></details>';
    html += '<div class="acties">';
    if (r.kolommen && r.kolommen.length && r.rijen.length){ html += '<a class="knop" href="' + CONFIG.api + '?action=csv"><i class="bi bi-download"></i> Download als CSV</a>'; }
    html += '<button class="knop" id="fbok"><i class="bi bi-hand-thumbs-up"></i> Klopt</button><button class="knop" id="fbnee"><i class="bi bi-hand-thumbs-down"></i> Klopt niet</button>';
    if (j.kandidaten && j.kandidaten.length){ html += '<span style="align-self:center;font-size:.82rem;color:#777">Bedoelde je: ' + j.kandidaten.map(function(k){ return '<a class="chip" data-id="' + esc(k.id) + '">' + esc(k.titel) + '</a>'; }).join(' ') + '</span>'; }
    html += '</div></div>';
    uit.innerHTML = html;
    uit.querySelectorAll('.chip[data-id]').forEach(function(c){ c.addEventListener('click', function(){ stel(tekst, c.dataset.id); }); });
    ['fbok','fbnee'].forEach(function(id){ var b = document.getElementById(id); b.addEventListener('click', function(){ api('feedback', {log_id: j.log_id, feedback: id==='fbok'?'klopt':'klopt niet'}).then(function(){ b.textContent = 'Bedankt!'; b.disabled = true; }); }); });
  }
  q.focus();
})();
</script>
@endverbatim
</body>
</html>
