<script type="application/json" id="app-state">{!! $payloadJson !!}</script>
<script>window.TC_SERVER = {version: {{ (int) ($payloadVersion ?? 1) }}, canEdit: {{ ($canEdit ?? false) ? 'true' : 'false' }}, saveUrl: {!! json_encode(route('tools.transport.save')) !!}, csrf: {!! json_encode(csrf_token()) !!}};</script>
@verbatim
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAAYFBMVEWTk5L4yJdmZmXzoUwgIB763sLyjCBAQD7sdgD3voKAgH8AAAD9/fzvewAXFxXykSoJCQf2tnLxgw2Xl5Z5eXgnJyX75c7r6ulWVlX51a/IyMe4uLf0pVI1NTRKSkjY2Nipu3jJAAAAIHRSTlP////////4/0f//wD+/v/+///8/////////////////6i9+hsAAAIeSURBVHja7ZbZsqMgEIYBY5YZaHYQQX3/tzyNS5IzlUmmxnPpX1EQmo9esAz5vVPkAByAA3AANsD1LM/yP3WW5koE5TsUDJF8lyQ5c97bh0j/zpxYG/8EoAdEgXrIvQF4paYXAAv+BKtOoN74MIB/CVAebJ+rHHhl3wHgb4B1vEcAwaDYOF7GsWPVG9KNw3AZWV4B8/OwTq6AE4zdLA/QPScFeu5qA3Ugc5yOWS3Bqjkfd8C2ADxuhHZjrYg/KRdxCLeKtqIrwAEwLEUkxPVPgI7NGpFMcA3M1bIKWA8wWILW2NYQolMwMOYcsdOLHHAkXCbcdAZk3DVv7nm4xAqY/L3o+VsVZmV0ckCTBYhuVw8usarvpyWJE/qD8eFW7MU5wCqM1W5w6LX3Sw4yrreD73DiFJkfXM2BewC+n0TI6NImPDePKliOd6zCPPAcQu+I27SkpnesFpWR+pBd1+GZcGhO3K/IM5ufu3owFsC+t3E3wOhdMkTs1FuAMXjV+73d+uvvA6ChOpnUhFC0lqm0QYeQaAgNLSIU05pPgBI0L4kmGSiVIejSSC5So1NqGk4N/wxIOlAEmApoC6dCBpHa0KSCvsnwDwBK23CjCLjJ0GBjokg3XKzbwEsQn0Og+sY1xl3wwuj1mYu2aOyjFykm/QFg8NtjpCkFWY0wuuCIkMbUjxJ2mzohfuAcmD3LjSDXfYDr8Q/lAByAA/BjgC8Excrup7W/sAAAAABJRU5ErkJggg==">
<title>Transportcalculator Europoort</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Source+Sans+3:wght@400;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style id="app-style">
:root{
  --ground:#F5F7F8; --panel:#FFFFFF; --panel-2:#EBEFF2; --panel-3:#E1E7EB;
  --line:#D3DBE1; --line-strong:#B4C0C9;
  --ink:#0F1820; --ink-2:#48555F; --ink-3:#6E7D89;
  --accent:#D25405; --accent-ink:#FFFFFF; --accent-soft:#FBEADF;
  --harbour:#0E3D4C; --harbour-soft:#E2EDF1;
  --ok:#146B4A; --warn:#8A5A00; --warn-soft:#FBF0DA; --danger:#A8261C; --danger-soft:#FAE7E4;
  --shadow:0 1px 2px rgba(15,24,32,.06), 0 8px 24px -16px rgba(15,24,32,.35);
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    --ground:#0C1216; --panel:#141C22; --panel-2:#1B252C; --panel-3:#222E36;
    --line:#2A363E; --line-strong:#3C4A54;
    --ink:#E7EDF1; --ink-2:#A6B3BE; --ink-3:#7E8D99;
    --accent:#FF7D33; --accent-ink:#1A0E06; --accent-soft:#2B1A0F;
    --harbour:#93C7D7; --harbour-soft:#16303A;
    --ok:#4FBF8F; --warn:#D9A73C; --warn-soft:#2C2413; --danger:#EF7B6E; --danger-soft:#2E1815;
    --shadow:0 1px 2px rgba(0,0,0,.4), 0 10px 30px -18px rgba(0,0,0,.9);
  }
}
:root[data-theme="dark"]{
  --ground:#0C1216; --panel:#141C22; --panel-2:#1B252C; --panel-3:#222E36;
  --line:#2A363E; --line-strong:#3C4A54;
  --ink:#E7EDF1; --ink-2:#A6B3BE; --ink-3:#7E8D99;
  --accent:#FF7D33; --accent-ink:#1A0E06; --accent-soft:#2B1A0F;
  --harbour:#93C7D7; --harbour-soft:#16303A;
  --ok:#4FBF8F; --warn:#D9A73C; --warn-soft:#2C2413; --danger:#EF7B6E; --danger-soft:#2E1815;
  --shadow:0 1px 2px rgba(0,0,0,.4), 0 10px 30px -18px rgba(0,0,0,.9);
}
*{box-sizing:border-box}
body{
  margin:0; background:var(--ground); color:var(--ink);
  font-family:"Source Sans 3","Segoe UI",system-ui,sans-serif; font-size:15px; line-height:1.5;
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3{font-family:Archivo,"Segoe UI",system-ui,sans-serif; margin:0; text-wrap:balance; letter-spacing:-.01em}
button,input,select,textarea{font:inherit; color:inherit}
:focus-visible{outline:2px solid var(--accent); outline-offset:2px}
.mono{font-family:"IBM Plex Mono",ui-monospace,monospace; font-variant-numeric:tabular-nums}
.eyebrow{font-family:Archivo,sans-serif; font-size:11px; font-weight:600; letter-spacing:.11em; text-transform:uppercase; color:var(--ink-3)}

/* ---------- header ---------- */
.topbar{position:sticky; top:0; z-index:30; background:var(--panel); border-bottom:1px solid var(--line)}
.topbar-in{max-width:1320px; margin:0 auto; padding:12px 24px 2px; display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start}
.brand{display:flex; align-items:center; gap:14px; flex:1 1 auto; min-width:260px}
.brand .logo{height:42px; width:auto; display:block; flex:0 0 auto}
.brand .naam{display:flex; align-items:baseline; gap:12px; flex-wrap:wrap}
.brand h1{font-size:20px; font-weight:700}
.brand .loc{font-size:13px; color:var(--ink-2)}
.brand .loc b{color:var(--harbour); font-weight:600}
.tabs{display:flex; gap:2px; margin:10px auto 0 0; max-width:1320px; padding:0 24px; width:100%}
.tabs-wrap{max-width:1320px; margin:0 auto; padding:0 24px; display:flex; gap:4px}
.tab{
  appearance:none; background:none; border:0; border-bottom:2px solid transparent;
  padding:10px 2px; margin-right:22px; cursor:pointer; color:var(--ink-3);
  font-family:Archivo,sans-serif; font-weight:600; font-size:14px;
}
.tab:hover{color:var(--ink)}
.tab[aria-selected="true"]{color:var(--ink); border-bottom-color:var(--accent)}
.savebar{display:flex; align-items:center; gap:12px; padding-top:2px}
.status{font-size:13px; color:var(--ink-3)}
.status[data-tone="dirty"]{color:var(--warn)}
.status[data-tone="ok"]{color:var(--ok)}
.status[data-tone="err"]{color:var(--danger)}

/* ---------- buttons ---------- */
.btn{
  appearance:none; border:1px solid var(--line-strong); background:var(--panel);
  padding:8px 14px; border-radius:3px; cursor:pointer; font-family:Archivo,sans-serif;
  font-weight:600; font-size:13.5px; transition:background .12s, border-color .12s;
}
.btn:hover{background:var(--panel-2)}
.btn-primary{background:var(--accent); border-color:var(--accent); color:var(--accent-ink)}
.btn-primary:hover{filter:brightness(1.06); background:var(--accent)}
.btn:disabled{opacity:.45; cursor:not-allowed}
.btn-small{padding:5px 10px; font-size:12.5px}
.linkbtn{appearance:none; background:none; border:0; padding:0; color:var(--accent); font-weight:600; font-size:13px; cursor:pointer; text-decoration:underline; text-underline-offset:2px}

/* ---------- layout ---------- */
main{max-width:1320px; margin:0 auto; padding:26px 24px 80px}
.panel{background:var(--panel); border:1px solid var(--line); border-radius:4px; box-shadow:var(--shadow)}
.panel-head{padding:16px 20px; border-bottom:1px solid var(--line); display:flex; align-items:baseline; justify-content:space-between; gap:12px; flex-wrap:wrap}
.panel-head h2{font-size:15px; font-weight:600}
.panel-body{padding:20px}
.grid-calc{display:grid; grid-template-columns:minmax(320px,1fr) minmax(380px,1.15fr); gap:22px; align-items:start}
@media (max-width:900px){ .grid-calc{grid-template-columns:1fr} }

/* ---------- form ---------- */
.field{display:flex; flex-direction:column; gap:6px; margin-bottom:18px}
.field:last-child{margin-bottom:0}
.field label{font-family:Archivo,sans-serif; font-size:12.5px; font-weight:600; color:var(--ink-2)}
.field .hint{font-size:12.5px; color:var(--ink-3)}
.control{
  width:100%; padding:9px 11px; border:1px solid var(--line-strong); border-radius:3px;
  background:var(--panel); color:var(--ink);
}
select.control{cursor:pointer}
input.control:focus,select.control:focus{border-color:var(--accent)}
.pc-input{font-family:"IBM Plex Mono",monospace; font-size:17px; letter-spacing:.16em; max-width:170px}

/* ---------- result ---------- */
.ladder{display:flex; flex-direction:column}
.rung{display:grid; grid-template-columns:1fr auto; gap:14px; padding:11px 0; border-bottom:1px dashed var(--line); align-items:baseline}
.rung:first-child{padding-top:0}
.rung .lbl{font-size:14px; color:var(--ink-2)}
.rung .lbl b{color:var(--ink); font-weight:600}
.rung .amt{font-family:"IBM Plex Mono",monospace; font-variant-numeric:tabular-nums; font-size:14.5px}
.rung.sum{border-bottom:1px solid var(--line-strong); border-top:1px solid var(--line-strong); padding:13px 0; margin-top:4px}
.rung.sum .lbl{font-family:Archivo,sans-serif; font-weight:600; color:var(--ink); font-size:14px}
.rung.sum .amt{font-size:17px; font-weight:500}
.headline{display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-top:20px; padding:16px 18px; background:var(--accent-soft); border-left:3px solid var(--accent); border-radius:0 3px 3px 0}
.headline .k{font-family:Archivo,sans-serif; font-size:12.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-2)}
.headline .v{font-family:"IBM Plex Mono",monospace; font-size:30px; font-weight:500; letter-spacing:-.02em; color:var(--ink)}
.band-chip{display:inline-flex; align-items:center; gap:7px; font-family:"IBM Plex Mono",monospace; font-size:12.5px; background:var(--harbour-soft); color:var(--harbour); padding:3px 9px; border-radius:2px}
.notice{padding:12px 14px; border-radius:3px; font-size:13.5px; display:flex; gap:10px; align-items:flex-start; margin-bottom:16px}
.notice .ico{font-family:Archivo,sans-serif; font-weight:700}
.notice-warn{background:var(--warn-soft); color:var(--warn)}
.notice-danger{background:var(--danger-soft); color:var(--danger)}
.notice-info{background:var(--panel-2); color:var(--ink-2)}
.footnotes{margin-top:18px; font-size:12.5px; color:var(--ink-3); display:flex; flex-direction:column; gap:4px}

/* ---------- tables ---------- */
.toolbar{display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; padding:16px 20px; border-bottom:1px solid var(--line); background:var(--panel-2)}
.toolbar .field{margin:0; min-width:150px}
.toolbar .control{padding:7px 9px; font-size:14px}
.tablewrap{overflow-x:auto; max-height:70vh; overflow-y:auto}
table{border-collapse:separate; border-spacing:0; width:100%; font-size:13.5px}
th,td{padding:7px 10px; text-align:right; white-space:nowrap; border-bottom:1px solid var(--line)}
thead th{
  position:sticky; top:0; z-index:2; background:var(--panel-3); text-align:right;
  font-family:Archivo,sans-serif; font-weight:600; font-size:11.5px; color:var(--ink-2);
  border-bottom:1px solid var(--line-strong); vertical-align:bottom;
}
thead th .th-id{display:block; font-family:"IBM Plex Mono",monospace; font-weight:400; color:var(--ink-3); font-size:10.5px}
th.pc,td.pc{text-align:left; position:sticky; left:0; z-index:1; background:var(--panel); font-family:"IBM Plex Mono",monospace}
thead th.pc{z-index:3; background:var(--panel-3)}
tbody tr:hover td{background:var(--panel-2)}
tbody tr:hover td.pc{background:var(--panel-2)}
td.num{font-family:"IBM Plex Mono",monospace; font-variant-numeric:tabular-nums}
td.empty{color:var(--danger); font-style:italic; font-family:"Source Sans 3",sans-serif; font-size:12.5px}
.cellinput{
  width:92px; padding:3px 6px; text-align:right; border:1px solid transparent; border-radius:2px;
  background:transparent; font-family:"IBM Plex Mono",monospace; font-variant-numeric:tabular-nums; font-size:13px;
}
.cellinput:hover{border-color:var(--line)}
.cellinput:focus{border-color:var(--accent); background:var(--panel)}
.cellinput.blank{background:var(--danger-soft)}
.pager{display:flex; align-items:center; gap:14px; padding:12px 20px; border-top:1px solid var(--line); font-size:13px; color:var(--ink-2); flex-wrap:wrap}

/* ---------- settings ---------- */
.settings{display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:22px; align-items:start}
.kv{display:grid; grid-template-columns:1fr 120px; gap:10px 14px; align-items:center}
.kv label{font-size:14px; color:var(--ink-2)}
.kv .control{text-align:right; font-family:"IBM Plex Mono",monospace}
.pct-wrap{position:relative}
.small-table{width:100%; font-size:13.5px}
.small-table th{background:none; position:static; border-bottom:1px solid var(--line-strong); padding:6px 8px}
.small-table td{padding:4px 8px}
.small-table td:first-child, .small-table th:first-child{text-align:left}
.small-table .control{padding:4px 7px; text-align:right; width:88px}
.rowline{display:flex; gap:10px; align-items:center; margin-bottom:8px}
.rowline .control{flex:1}
.readonly-banner{background:var(--harbour-soft); color:var(--harbour); padding:10px 24px; font-size:13.5px; text-align:center}
/* ---------- beheerslot ---------- */
.overlay{position:fixed; inset:0; z-index:60; background:rgba(10,16,20,.55); display:flex; align-items:center; justify-content:center; padding:24px}
.dialog{background:var(--panel); border:1px solid var(--line); border-radius:5px; box-shadow:0 24px 60px -20px rgba(0,0,0,.5); width:100%; max-width:400px}
.dialog h2{font-size:16px; font-weight:600; margin-bottom:6px}
.dialog p{margin:0 0 16px; font-size:13.5px; color:var(--ink-2)}
.dialog .acties{display:flex; gap:10px; justify-content:flex-end; margin-top:18px}
.dialog .fout{color:var(--danger); font-size:13px; margin-top:8px}
@media print{ .topbar,.toolbar,.pager{display:none} }
</style>
</head>
<body>


<div id="root"></div>

<!-- app-state staat nu BUITEN @verbatim (server-geinjecteerd), zie bovenaan dit bestand -->
<script id="app-script">
(function(){
"use strict";

var LOGO = 'data:image/png;base64,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';
var S = JSON.parse(document.getElementById('app-state').textContent);
var root = document.getElementById('root');
var canWrite = null;          // null = onbekend, true/false na eerste publicatiepoging
var lokaleModus = false;      // true = losse HTML zonder gedeelde opslag: bewaren in deze browser
var artifactApi = null;
var dirty = false;
var saving = false;
var statusMsg = '';
var statusTone = '';
var ui = {
  tab: 'calc',
  ontgrendeld: false,
  dialoog: null,      // null | 'login' | 'wijzig'
  dialoogFout: '',
  middelId: 9,
  postcode: '3011',
  periode: 0,
  tabJaar: null,
  weergave: 'inkoop',
  kolomFilter: 'alle',
  zoek: '',
  pagina: 0
};

/* ---------------- beheerslot ---------------- */
/* Het wachtwoord staat niet leesbaar in de pagina: opgeslagen wordt alleen deze
   afgeleide waarde. Het is een drempel voor collega's, geen echte beveiliging —
   wie de broncode leest, ziet de tarieven zelf nog steeds. */
function hashPw(pw, salt){
  var s = String(salt) + '|' + String(pw);
  var h1 = 0x811c9dc5, h2 = 0x01000193;
  for(var round=0; round<2000; round++){
    for(var i=0;i<s.length;i++){
      var c = s.charCodeAt(i);
      h1 = ((h1 ^ c) * 16777619) >>> 0;
      h2 = (((h2 + c*(i+1)) >>> 0) ^ ((h1 << 5) | (h1 >>> 27))) >>> 0;
    }
    s = h1.toString(16) + h2.toString(16);
  }
  return ('0000000' + h1.toString(16)).slice(-8) + ('0000000' + h2.toString(16)).slice(-8);
}
function slotAan(){ return !!(S.beheer && S.beheer.hash); }
function magBeheren(){ return !!(window.TC_SERVER && window.TC_SERVER.canEdit); }
function onthoudOntgrendeling(){
  try{ sessionStorage.setItem('tc-beheer', S.beheer.hash); }catch(e){}
}
function herstelOntgrendeling(){
  try{ if(slotAan() && sessionStorage.getItem('tc-beheer') === S.beheer.hash) ui.ontgrendeld = true; }catch(e){}
}
function vergrendel(){
  ui.ontgrendeld = false; ui.tab = 'calc';
  try{ sessionStorage.removeItem('tc-beheer'); }catch(e){}
  render();
}

/* ---------------- helpers ---------------- */
var eur = new Intl.NumberFormat('nl-NL',{style:'currency',currency:'EUR',minimumFractionDigits:2,maximumFractionDigits:2});
function money(n){ return eur.format(n); }
function pct(n){ return (n*100).toLocaleString('nl-NL',{maximumFractionDigits:2}) + '%'; }
/* Accepteert 1.234,56 en 1234.56 en 1234,56 */
function parseNL(s){
  s = String(s==null?'':s).trim().replace(/\s|€/g,'');
  if(s === '') return null;
  if(s.indexOf(',') > -1) s = s.replace(/\./g,'').replace(',','.');
  var v = parseFloat(s);
  return isNaN(v) ? null : v;
}
function nl(n, dec){ return n==null ? '' : Number(n).toFixed(dec==null?2:dec).replace('.',','); }
function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function jaren(){ return Object.keys(S.jaren).sort(); }
function actiefJaar(){ return S.jaren[S.actiefJaar] ? S.actiefJaar : jaren()[jaren().length-1]; }
function middelById(id){ for(var i=0;i<S.middelen.length;i++){ if(S.middelen[i].id===id) return S.middelen[i]; } return null; }
function markDirty(){ dirty = true; statusMsg=''; statusTone=''; syncSave(); }

/* Zoekt de rij zoals Excel dat doet: benaderende match op PC_VAN (laatste band <= postcode). */
function vindBand(jaar, postcode){
  var rows = S.jaren[jaar].rows, hit = null;
  for(var i=0;i<rows.length;i++){ if(rows[i][0] <= postcode) hit = rows[i]; else break; }
  return hit;
}

function bereken(jaar, middelId, postcode, periodeIdx){
  var out = {ok:false};
  var band = vindBand(jaar, postcode);
  if(!band){ out.fout = 'Postcode ' + postcode + ' ligt onder de laagste postcodeband (' + S.jaren[jaar].rows[0][0] + ').'; return out; }
  out.band = band;
  out.buitenBand = postcode > band[1];
  var basis = band[1 + middelId];
  if(basis == null || basis === '' || isNaN(basis)){
    out.fout = 'Voor dit transportmiddel staat geen tarief in de tabel van ' + jaar + ' bij postcodeband ' + band[0] + '–' + band[1] + '.';
    return out;
  }
  var per = S.periodes[periodeIdx] || S.periodes[0];
  var dotIsKoerier = middelId <= S.dotDrempelId;
  var dot = dotIsKoerier ? S.dotKoerier : S.dotVracht;
  var naToeslag = basis * (1 + per.toeslag);
  var inkoop = naToeslag * (1 + dot);
  var verkoop = inkoop * (1 + S.marge);
  out.ok = true;
  out.basis = basis; out.periode = per; out.toeslagBedrag = naToeslag - basis;
  out.dot = dot; out.dotSoort = dotIsKoerier ? 'koerier' : 'vrachtwagen'; out.dotBedrag = inkoop - naToeslag;
  out.inkoop = inkoop; out.margeBedrag = verkoop - inkoop; out.verkoop = verkoop;
  return out;
}

/* ---------------- render: topbar ---------------- */
function renderTopbar(){
  var el = document.getElementById('topbar');
  if(!el) return;
  var tabs = magBeheren()
    ? [['calc','Calculator'],['tarieven','Tarieven'],['instellingen','Instellingen']]
    : [['calc','Calculator']];
  var t = '';
  for(var i=0;i<tabs.length;i++){
    t += '<button class="tab" role="tab" aria-selected="'+(ui.tab===tabs[i][0])+'" data-tab="'+tabs[i][0]+'">'+tabs[i][1]+'</button>';
  }
  var save = '';
  if(!magBeheren()){
    save = '';  // niet-beheerders: geen beheer-knop (de CORE-rol bepaalt toegang, geen wachtwoord meer)
  } else if(canWrite === false){
    save = '<span class="status">Alleen-lezen weergave</span>' +
           (slotAan() ? '<button class="btn btn-small" id="btn-vergrendel">Vergrendelen</button>' : '');
  } else {
    var msg = statusMsg ? statusMsg : (dirty ? 'Niet-opgeslagen wijzigingen' : 'Opgeslagen');
    var tone = statusTone ? statusTone : (dirty ? 'dirty' : 'ok');
    save = '<span class="status" data-tone="'+tone+'">'+esc(msg)+'</span>' +
           '<button class="btn btn-primary" id="btn-save"'+((!dirty||saving)?' disabled':'')+'>'+(saving?'Bezig…':(lokaleModus?'Opslaan':'Opslaan voor iedereen'))+'</button>' +
           (slotAan() ? '<button class="btn btn-small" id="btn-vergrendel">Vergrendelen</button>' : '');
  }
  el.innerHTML =
    '<div class="topbar-in">' +
      '<div class="brand"><img class="logo" src="'+LOGO+'" alt="Boels Industrial">' +
      '<div class="naam"><h1>Transportcalculator</h1>' +
      '<span class="loc">af <b>'+esc(S.vestiging)+'</b> · tarieven '+esc(actiefJaar())+'</span></div></div>' +
      '<div class="savebar">'+save+'</div>' +
    '</div>' +
    '<div class="tabs-wrap" role="tablist">'+t+'</div>';
}

/* Werkt alleen de opslaan-knop en de status bij, zonder de topbar opnieuw op te
   bouwen — dat zou een lopende klik op de knop onderbreken. */
function syncSave(){
  var st = document.querySelector('#topbar .status');
  var btn = document.getElementById('btn-save');
  if(!st || !btn){ renderTopbar(); return; }
  var msg = statusMsg ? statusMsg : (dirty ? 'Niet-opgeslagen wijzigingen' : 'Opgeslagen');
  var tone = statusTone ? statusTone : (dirty ? 'dirty' : 'ok');
  st.textContent = msg; st.setAttribute('data-tone', tone);
  btn.disabled = (!dirty || saving);
  btn.textContent = saving ? 'Bezig…' : (lokaleModus ? 'Opslaan' : 'Opslaan voor iedereen');
}

/* ---------------- render: calculator ---------------- */
function viewCalc(){
  var jaar = actiefJaar();
  var opties = '';
  for(var i=0;i<S.middelen.length;i++){
    var m = S.middelen[i];
    var leeg = tariefOntbreekt(jaar, m.id);
    opties += '<option value="'+m.id+'"'+(m.id===ui.middelId?' selected':'')+'>'+esc(m.naam)+(leeg?'  — geen tarief':'')+'</option>';
  }
  var perOpt = '';
  for(var p=0;p<S.periodes.length;p++){
    perOpt += '<option value="'+p+'"'+(p===ui.periode?' selected':'')+'>'+esc(S.periodes[p].naam)+(S.periodes[p].toeslag?'  (+'+pct(S.periodes[p].toeslag)+')':'')+'</option>';
  }
  var pcNum = parseInt(ui.postcode,10);
  var res = (ui.postcode.length===4 && !isNaN(pcNum)) ? bereken(jaar, ui.middelId, pcNum, ui.periode) : {ok:false, fout:null};

  var right = '';
  if(!res.ok && !res.fout){
    right = '<div class="notice notice-info"><span class="ico">→</span><span>Vul een postcode van vier cijfers in om de prijs te berekenen.</span></div>';
  } else if(!res.ok){
    right = '<div class="notice notice-danger"><span class="ico">!</span><span>'+esc(res.fout)+'</span></div>' +
            '<p style="font-size:13.5px;color:var(--ink-2)">Vul het ontbrekende tarief aan op het tabblad <b>Tarieven</b>, of kies een ander transportmiddel.</p>';
  } else {
    var b = res.band;
    var waarschuwing = res.buitenBand
      ? '<div class="notice notice-warn"><span class="ico">!</span><span>Postcode '+pcNum+' valt in een gat in de postcodetabel. Net als in Excel wordt het tarief van de laatste band daarvóór gebruikt ('+b[0]+'–'+b[1]+').</span></div>'
      : '';
    right =
      waarschuwing +
      '<div class="ladder">' +
        rung('Basistarief <b>'+esc(middelById(ui.middelId).naam)+'</b><br><span class="band-chip">postcodeband '+b[0]+'–'+b[1]+' · tarieven '+esc(jaar)+'</span>', money(res.basis)) +
        rung('Uitvoeringstoeslag — '+esc(res.periode.naam)+' <b>'+(res.periode.toeslag?'+'+pct(res.periode.toeslag):'0%')+'</b>', money(res.toeslagBedrag)) +
        rung('Dieseltoeslag DOT '+res.dotSoort+' <b>+'+pct(res.dot)+'</b>', money(res.dotBedrag)) +
        rung('Inkoopprijs', money(res.inkoop), 'sum') +
        rung('Marge <b>+'+pct(S.marge)+'</b>', money(res.margeBedrag)) +
      '</div>' +
      '<div class="headline"><div><div class="k">Verkoopprijs excl. btw</div></div><div class="v">'+money(res.verkoop)+'</div></div>' +
      '<div class="footnotes">' + S.notities.map(function(n){ return '<span>'+esc(n)+'</span>'; }).join('') + '</div>';
  }

  return '<div class="grid-calc">' +
    '<section class="panel"><div class="panel-head"><h2>Rit</h2><span class="eyebrow">af '+esc(S.vestiging)+'</span></div><div class="panel-body">' +
      '<div class="field"><label for="f-middel">Transportmiddel</label><select class="control" id="f-middel">'+opties+'</select></div>' +
      '<div class="field"><label for="f-pc">Postcode afleveradres</label>' +
        '<input class="control pc-input mono" id="f-pc" inputmode="numeric" maxlength="4" value="'+esc(ui.postcode)+'" placeholder="0000">' +
        '<span class="hint">Vier cijfers, 1000 t/m 9999.</span></div>' +
      '<div class="field"><label for="f-periode">Uitvoeringsperiode</label><select class="control" id="f-periode">'+perOpt+'</select></div>' +
    '</div></section>' +
    '<section class="panel"><div class="panel-head"><h2>Prijsopbouw</h2><span class="eyebrow">inkoop → verkoop</span></div><div class="panel-body">'+right+'</div></section>' +
  '</div>';
}
function rung(lbl, amt, cls){
  return '<div class="rung'+(cls?' '+cls:'')+'"><div class="lbl">'+lbl+'</div><div class="amt">'+amt+'</div></div>';
}
function tariefOntbreekt(jaar, middelId){
  var rows = S.jaren[jaar].rows;
  for(var i=0;i<rows.length;i++){ var v = rows[i][1+middelId]; if(v!=null && !isNaN(v)) return false; }
  return true;
}

/* ---------------- render: tarieven ---------------- */
var PER_PAGINA = 40;
function viewTarieven(){
  var jaar = ui.tabJaar || actiefJaar();
  ui.tabJaar = jaar;
  var rows = S.jaren[jaar].rows;
  var zoek = ui.zoek.trim();
  var gefilterd = [];
  for(var i=0;i<rows.length;i++){
    if(zoek){
      var q = parseInt(zoek,10);
      if(isNaN(q)) { if(String(rows[i][0]).indexOf(zoek)!==0) continue; }
      else if(!(rows[i][0] <= q && q <= rows[i][1]) && String(rows[i][0]).indexOf(zoek)!==0) continue;
    }
    gefilterd.push({r:rows[i], idx:i});
  }
  var paginas = Math.max(1, Math.ceil(gefilterd.length / PER_PAGINA));
  if(ui.pagina >= paginas) ui.pagina = 0;
  var slice = gefilterd.slice(ui.pagina*PER_PAGINA, ui.pagina*PER_PAGINA + PER_PAGINA);

  var kolommen = ui.kolomFilter === 'alle' ? S.middelen : S.middelen.filter(function(m){ return String(m.id)===String(ui.kolomFilter); });
  var isVerkoop = ui.weergave === 'verkoop';

  var jaarOpt = jaren().map(function(j){ return '<option value="'+j+'"'+(j===jaar?' selected':'')+'>Tarieven '+j+'</option>'; }).join('');
  var kolOpt = '<option value="alle">Alle transportmiddelen</option>' + S.middelen.map(function(m){
    return '<option value="'+m.id+'"'+(String(m.id)===String(ui.kolomFilter)?' selected':'')+'>'+esc(m.naam)+'</option>'; }).join('');

  var thead = '<tr><th class="pc">Postcode<span class="th-id">van – tot</span></th>';
  for(var k=0;k<kolommen.length;k++){
    thead += '<th title="'+esc(kolommen[k].naam)+'">'+esc(kort(kolommen[k].naam))+'<span class="th-id">id '+kolommen[k].id+'</span></th>';
  }
  thead += '</tr>';

  var tbody = '';
  for(var s=0;s<slice.length;s++){
    var r = slice[s].r, ri = slice[s].idx;
    tbody += '<tr><td class="pc">'+r[0]+' – '+r[1]+'</td>';
    for(var c=0;c<kolommen.length;c++){
      var mid = kolommen[c].id;
      var v = r[1+mid];
      if(isVerkoop){
        var vv = (v==null||isNaN(v)) ? null : v * (1 + S.dotVracht) * (1 + S.marge);
        tbody += '<td class="num">'+(vv==null?'<span class="empty">geen tarief</span>':money(vv))+'</td>';
      } else {
        var val = (v==null||isNaN(v)) ? '' : nl(v);
        tbody += '<td><input class="cellinput'+(val===''?' blank':'')+'" data-row="'+ri+'" data-col="'+mid+'" value="'+val+'" '+(canWrite===false?'readonly':'')+' placeholder="—"></td>';
      }
    }
    tbody += '</tr>';
  }

  var uitleg = isVerkoop
    ? '<div class="notice notice-info"><span class="ico">i</span><span>Verkooptarieven zijn berekend, niet ingevoerd: inkooptarief × (1 + DOT vrachtwagen '+pct(S.dotVracht)+') × (1 + marge '+pct(S.marge)+'). Dit volgt de prijslijst uit Excel, die voor élk middel de vrachtwagen-DOT hanteert — de calculator gebruikt wel de juiste DOT per middel.</span></div>'
    : '<div class="notice notice-info"><span class="ico">i</span><span>Dit zijn de kale inkooptarieven per postcodeband. Toeslagen, DOT en marge komen er in de calculator bovenop. Wijzig een bedrag en sla op; iedereen met de link ziet daarna hetzelfde tarief.</span></div>';

  return '<section class="panel">' +
    '<div class="toolbar">' +
      '<div class="field"><label for="t-jaar">Tariefjaar</label><select class="control" id="t-jaar">'+jaarOpt+'</select></div>' +
      '<div class="field"><label for="t-weergave">Weergave</label><select class="control" id="t-weergave">' +
        '<option value="inkoop"'+(!isVerkoop?' selected':'')+'>Inkooptarieven (bewerkbaar)</option>' +
        '<option value="verkoop"'+(isVerkoop?' selected':'')+'>Verkooptarieven (berekend)</option></select></div>' +
      '<div class="field" style="min-width:230px"><label for="t-kol">Transportmiddel</label><select class="control" id="t-kol">'+kolOpt+'</select></div>' +
      '<div class="field"><label for="t-zoek">Zoek postcode</label><input class="control mono" id="t-zoek" value="'+esc(ui.zoek)+'" placeholder="bv. 3011" maxlength="4" inputmode="numeric"></div>' +
    '</div>' +
    '<div class="panel-body" style="padding:16px 20px 0">'+uitleg+'</div>' +
    '<div class="tablewrap"><table><thead>'+thead+'</thead><tbody>'+tbody+'</tbody></table></div>' +
    '<div class="pager">' +
      '<button class="btn btn-small" id="p-prev"'+(ui.pagina===0?' disabled':'')+'>Vorige</button>' +
      '<span>Band '+(gefilterd.length?(ui.pagina*PER_PAGINA+1):0)+'–'+Math.min(gefilterd.length,(ui.pagina+1)*PER_PAGINA)+' van '+gefilterd.length+'</span>' +
      '<button class="btn btn-small" id="p-next"'+(ui.pagina>=paginas-1?' disabled':'')+'>Volgende</button>' +
      '<span style="margin-left:auto">'+rows.length+' postcodebanden · '+S.middelen.length+' transportmiddelen</span>' +
    '</div></section>';
}
function kort(n){ return n.replace('Bakwagen(k) met laadklep','Bakwagen laadklep').replace('Open bestelbus XXL (Pick Up)','Pick Up XXL').replace('Trekker met kraan tot','Trekker kraan').replace('Bakwagen met kraan tot','Bakwagen kraan').replace('Trekker met (stuur-)oplegger','Trekker + oplegger').replace(' + aanhanger',' + aanh.'); }

/* ---------------- render: instellingen ---------------- */
function viewInstellingen(){
  var nu = new Date();
  var huidigeMaand = nu.getFullYear() + '-' + ('0'+(nu.getMonth()+1)).slice(-2);
  var maandRij = null;
  for(var i=0;i<S.dotMaanden.length;i++){ if(S.dotMaanden[i].maand === huidigeMaand) maandRij = S.dotMaanden[i]; }
  var afwijking = maandRij && (Math.abs(maandRij.koerier - S.dotKoerier) > 1e-9 || Math.abs(maandRij.vracht - S.dotVracht) > 1e-9);

  var dotTabel = '<table class="small-table"><thead><tr><th>Maand</th><th>Koerier</th><th>Vrachtwagen</th></tr></thead><tbody>';
  for(var d=0;d<S.dotMaanden.length;d++){
    var m = S.dotMaanden[d];
    dotTabel += '<tr'+(m.maand===huidigeMaand?' style="background:var(--accent-soft)"':'')+'><td class="mono">'+esc(m.maand)+'</td>' +
      '<td><input class="control" data-dotm="'+d+'" data-veld="koerier" value="'+(m.koerier==null?'':nl(m.koerier*100,1))+'" placeholder="—"></td>' +
      '<td><input class="control" data-dotm="'+d+'" data-veld="vracht" value="'+(m.vracht==null?'':nl(m.vracht*100,1))+'" placeholder="—"></td></tr>';
  }
  dotTabel += '</tbody></table>';

  var perRijen = S.periodes.map(function(p,i){
    return '<div class="rowline"><input class="control" data-per="'+i+'" data-veld="naam" value="'+esc(p.naam)+'">' +
      '<input class="control" style="max-width:90px;text-align:right" data-per="'+i+'" data-veld="toeslag" value="'+nl(p.toeslag*100,1)+'"><span style="color:var(--ink-3)">%</span></div>';
  }).join('');

  var midRijen = S.middelen.map(function(m,i){
    return '<div class="rowline"><span class="mono" style="width:26px;color:var(--ink-3)">'+m.id+'</span>' +
      '<input class="control" data-mid="'+i+'" value="'+esc(m.naam)+'"></div>';
  }).join('');

  var jaarOpt = jaren().map(function(j){ return '<option value="'+j+'"'+(j===actiefJaar()?' selected':'')+'>'+j+'</option>'; }).join('');

  return '<div class="settings">' +
    '<section class="panel"><div class="panel-head"><h2>Marge en dieseltoeslag</h2></div><div class="panel-body">' +
      (afwijking ? '<div class="notice notice-warn"><span class="ico">!</span><span>De DOT-tabel geeft voor '+huidigeMaand+' '+pct(maandRij.koerier)+' (koerier) en '+pct(maandRij.vracht)+' (vrachtwagen). Dat wijkt af van wat de calculator nu gebruikt. <button class="linkbtn" id="dot-overnemen">Overnemen</button></span></div>' : '') +
      '<div class="kv">' +
        '<label for="s-marge">Marge op inkoopprijs</label><input class="control" id="s-marge" value="'+nl(S.marge*100,1)+'">' +
        '<label for="s-dotk">DOT koerier (id t/m '+S.dotDrempelId+')</label><input class="control" id="s-dotk" value="'+nl(S.dotKoerier*100,1)+'">' +
        '<label for="s-dotv">DOT vrachtwagen (id vanaf '+(S.dotDrempelId+1)+')</label><input class="control" id="s-dotv" value="'+nl(S.dotVracht*100,1)+'">' +
        '<label for="s-jaar">Actief tariefjaar in de calculator</label><select class="control" id="s-jaar">'+jaarOpt+'</select>' +
        '<label for="s-vest">Vestiging</label><input class="control" id="s-vest" style="text-align:left;font-family:inherit" value="'+esc(S.vestiging)+'">' +
      '</div>' +
      '<p class="hint" style="margin:14px 0 0;font-size:12.5px;color:var(--ink-3)">Percentages invullen als getal, bijvoorbeeld 26,1 voor 26,1%.</p>' +
    '</div></section>' +

    '<section class="panel"><div class="panel-head"><h2>Uitvoeringsperiodes</h2></div><div class="panel-body">'+perRijen+
      '<p class="hint" style="margin:10px 0 0;font-size:12.5px;color:var(--ink-3)">De toeslag komt vóór de dieseltoeslag over het basistarief.</p></div></section>' +

    '<section class="panel"><div class="panel-head"><h2>DOT per maand</h2><span class="eyebrow">naslag</span></div><div class="panel-body" style="max-height:420px;overflow:auto">'+dotTabel+'</div></section>' +

    '<section class="panel"><div class="panel-head"><h2>Transportmiddelen</h2></div><div class="panel-body" style="max-height:420px;overflow:auto">'+midRijen+
      '<p class="hint" style="margin:10px 0 0;font-size:12.5px;color:var(--ink-3)">Het id bepaalt welke kolom in de tarieventabel hoort en of de koerier- of vrachtwagen-DOT geldt.</p></div></section>' +

    '<section class="panel"><div class="panel-head"><h2>Tariefjaar toevoegen</h2></div><div class="panel-body">' +
      '<p style="margin:0 0 12px;font-size:13.5px;color:var(--ink-2)">Een nieuw jaar afleiden uit een bestaand jaar met één indexatiepercentage — zoals 2023 in Excel uit 2022 werd afgeleid (× 1,102).</p>' +
      '<div class="rowline"><select class="control" id="n-bron">'+jaren().map(function(j){ return '<option value="'+j+'">'+j+'</option>'; }).join('')+'</select>' +
      '<input class="control" id="n-jaar" placeholder="2026" maxlength="4" style="max-width:90px">' +
      '<input class="control" id="n-index" placeholder="3,5" style="max-width:80px;text-align:right"><span style="color:var(--ink-3)">%</span></div>' +
      '<button class="btn" id="n-maak">Jaar aanmaken</button>' +
    '</div></section>' +

    '<section class="panel"><div class="panel-head"><h2>Toegang</h2></div><div class="panel-body">' +
      '<p style="margin:0 0 6px;font-size:13.5px;color:var(--ink-2)">De tarieven staan centraal op de Boels-server: iedereen ziet dezelfde, actuele cijfers en wijzigingen gelden meteen voor iedereen.</p>' +
      '<p style="margin:0;font-size:13.5px;color:var(--ink-2)">Alleen <strong>planners, beheerders en superadmins</strong> (volgens hun CORE-rol) kunnen de tabbladen Tarieven en Instellingen openen en opslaan. Andere medewerkers zien alleen de calculator. Dit wordt op de server afgedwongen — geen apart wachtwoord meer.</p>' +
    '</div></section>' +

    '<section class="panel"><div class="panel-head"><h2>Voetnoten onder de prijs</h2></div><div class="panel-body">' +
      S.notities.map(function(n,i){ return '<div class="rowline"><input class="control" data-not="'+i+'" style="text-align:left" value="'+esc(n)+'"></div>'; }).join('') +
    '</div></section>' +
  '</div>';
}

/* ---------------- beheerdialoog ---------------- */
function viewDialoog(){
  if(!ui.dialoog) return '';
  var isWijzig = ui.dialoog === 'wijzig';
  var body = isWijzig
    ? '<h2>Beheerwachtwoord wijzigen</h2>' +
      '<p>Het nieuwe wachtwoord geldt zodra je de wijziging opslaat.</p>' +
      '<div class="field"><label for="d-oud">Huidig wachtwoord</label><input class="control" id="d-oud" type="password" autocomplete="current-password"></div>' +
      '<div class="field"><label for="d-nieuw">Nieuw wachtwoord</label><input class="control" id="d-nieuw" type="password" autocomplete="new-password"></div>' +
      '<div class="field"><label for="d-nieuw2">Nieuw wachtwoord herhalen</label><input class="control" id="d-nieuw2" type="password" autocomplete="new-password"></div>'
    : '<h2>Beheer openen</h2>' +
      '<p>De tabbladen Tarieven en Instellingen zijn afgeschermd. Vul het beheerwachtwoord in.</p>' +
      '<div class="field"><label for="d-pw">Wachtwoord</label><input class="control" id="d-pw" type="password" autocomplete="current-password"></div>';
  return '<div class="overlay" id="overlay"><div class="dialog"><div class="panel-body">' + body +
    (ui.dialoogFout ? '<div class="fout">'+esc(ui.dialoogFout)+'</div>' : '') +
    '<div class="acties"><button class="btn" id="d-annuleer">Annuleren</button>' +
    '<button class="btn btn-primary" id="d-ok">'+(isWijzig?'Wachtwoord instellen':'Openen')+'</button></div>' +
  '</div></div></div>';
}

/* ---------------- render root ---------------- */
function render(){
  if(!magBeheren()) ui.tab = 'calc';
  var body = ui.tab === 'calc' ? viewCalc() : (ui.tab === 'tarieven' ? viewTarieven() : viewInstellingen());
  root.innerHTML = '<div class="topbar" id="topbar"></div>' +
    (canWrite === false ? '<div class="readonly-banner">Je bekijkt deze tool alleen-lezen. Wijzigingen worden niet opgeslagen.</div>' : '') +
    '<main>'+body+'</main>' + viewDialoog();
  renderTopbar();
  bind();
}

function bind(){
  root.querySelectorAll('.tab').forEach(function(b){
    b.addEventListener('click', function(){ ui.tab = b.dataset.tab; render(); });
  });
  var save = document.getElementById('btn-save');
  if(save) save.addEventListener('click', opslaan);
  var slot = document.getElementById('btn-slot');
  if(slot) slot.addEventListener('click', function(){ ui.dialoog = 'login'; ui.dialoogFout=''; render(); });
  var verg = document.getElementById('btn-vergrendel');
  if(verg) verg.addEventListener('click', vergrendel);
  bindDialoog();

  if(ui.tab === 'calc'){
    var fm = document.getElementById('f-middel');
    fm.addEventListener('change', function(){ ui.middelId = parseInt(fm.value,10); render(); });
    var fp = document.getElementById('f-pc');
    fp.addEventListener('input', function(){
      ui.postcode = fp.value.replace(/\D/g,'').slice(0,4);
      var pos = fp.selectionStart; render();
      var nw = document.getElementById('f-pc'); nw.focus(); try{ nw.setSelectionRange(pos,pos); }catch(e){}
    });
    var fper = document.getElementById('f-periode');
    fper.addEventListener('change', function(){ ui.periode = parseInt(fper.value,10); render(); });
  }

  if(ui.tab === 'tarieven'){
    document.getElementById('t-jaar').addEventListener('change', function(e){ ui.tabJaar = e.target.value; ui.pagina=0; render(); });
    document.getElementById('t-weergave').addEventListener('change', function(e){ ui.weergave = e.target.value; render(); });
    document.getElementById('t-kol').addEventListener('change', function(e){ ui.kolomFilter = e.target.value; render(); });
    var z = document.getElementById('t-zoek');
    z.addEventListener('input', function(){
      ui.zoek = z.value.replace(/\D/g,'').slice(0,4); ui.pagina = 0; render();
      var nz = document.getElementById('t-zoek'); nz.focus();
    });
    var pv = document.getElementById('p-prev'), nx = document.getElementById('p-next');
    if(pv) pv.addEventListener('click', function(){ ui.pagina = Math.max(0, ui.pagina-1); render(); });
    if(nx) nx.addEventListener('click', function(){ ui.pagina = ui.pagina+1; render(); });
    root.querySelectorAll('.cellinput').forEach(function(inp){
      inp.addEventListener('change', function(){
        var r = parseInt(inp.dataset.row,10), c = parseInt(inp.dataset.col,10);
        var val = parseNL(inp.value);
        S.jaren[ui.tabJaar].rows[r][1+c] = val;
        inp.value = nl(val);
        inp.classList.toggle('blank', S.jaren[ui.tabJaar].rows[r][1+c] == null);
        markDirty();
      });
    });
  }

  if(ui.tab === 'instellingen'){
    numField('s-marge', function(v){ S.marge = v/100; });
    numField('s-dotk', function(v){ S.dotKoerier = v/100; });
    numField('s-dotv', function(v){ S.dotVracht = v/100; });
    var sj = document.getElementById('s-jaar');
    sj.addEventListener('change', function(){ S.actiefJaar = sj.value; markDirty(); render(); });
    var sv = document.getElementById('s-vest');
    sv.addEventListener('change', function(){ S.vestiging = sv.value; markDirty(); render(); });
    var ov = document.getElementById('dot-overnemen');
    if(ov) ov.addEventListener('click', function(){
      var nu = new Date(), hm = nu.getFullYear()+'-'+('0'+(nu.getMonth()+1)).slice(-2);
      for(var i=0;i<S.dotMaanden.length;i++){ if(S.dotMaanden[i].maand===hm){
        if(S.dotMaanden[i].koerier!=null) S.dotKoerier = S.dotMaanden[i].koerier;
        if(S.dotMaanden[i].vracht!=null) S.dotVracht = S.dotMaanden[i].vracht;
      }}
      markDirty(); render();
    });
    root.querySelectorAll('[data-per]').forEach(function(inp){
      inp.addEventListener('change', function(){
        var i = parseInt(inp.dataset.per,10);
        if(inp.dataset.veld === 'naam') S.periodes[i].naam = inp.value;
        else { var v = parseNL(inp.value); S.periodes[i].toeslag = v==null?0:v/100; inp.value = nl(S.periodes[i].toeslag*100,1); }
        markDirty();
      });
    });
    root.querySelectorAll('[data-mid]').forEach(function(inp){
      inp.addEventListener('change', function(){ S.middelen[parseInt(inp.dataset.mid,10)].naam = inp.value; markDirty(); });
    });
    root.querySelectorAll('[data-not]').forEach(function(inp){
      inp.addEventListener('change', function(){ S.notities[parseInt(inp.dataset.not,10)] = inp.value; markDirty(); });
    });
    root.querySelectorAll('[data-dotm]').forEach(function(inp){
      inp.addEventListener('change', function(){
        var i = parseInt(inp.dataset.dotm,10), v = parseNL(inp.value);
        S.dotMaanden[i][inp.dataset.veld] = v==null ? null : v/100;
        inp.value = nl(S.dotMaanden[i][inp.dataset.veld]==null?null:S.dotMaanden[i][inp.dataset.veld]*100,1);
        markDirty();
      });
    });
    document.getElementById('pw-wijzig').addEventListener('click', function(){
      ui.dialoog = 'wijzig'; ui.dialoogFout = ''; render();
    });
    document.getElementById('n-maak').addEventListener('click', function(){
      var bron = document.getElementById('n-bron').value;
      var jr = document.getElementById('n-jaar').value.trim();
      var ix = parseNL(document.getElementById('n-index').value); if(ix==null) ix = NaN;
      if(!/^\d{4}$/.test(jr)){ alertBanner('Vul een jaartal van vier cijfers in.'); return; }
      if(S.jaren[jr]){ alertBanner('Tarieven '+jr+' bestaan al.'); return; }
      if(isNaN(ix)){ alertBanner('Vul een indexatiepercentage in.'); return; }
      var f = 1 + ix/100;
      S.jaren[jr] = { rows: S.jaren[bron].rows.map(function(r){
        return r.map(function(v,i){ return i<2 ? v : (v==null||isNaN(v) ? null : Math.round(v*f*100)/100); });
      })};
      S.actiefJaar = jr; ui.tabJaar = jr; markDirty(); render();
    });
  }
}
/* Fout tonen zonder opnieuw te renderen — dat zou de ingevulde velden wissen. */
function toonDialoogFout(msg){
  ui.dialoogFout = msg;
  var dlg = document.querySelector('.dialog .panel-body');
  if(!dlg) return;
  var el = dlg.querySelector('.fout');
  if(!el){
    el = document.createElement('div');
    el.className = 'fout';
    dlg.insertBefore(el, dlg.querySelector('.acties'));
  }
  el.textContent = msg;
}

function bindDialoog(){
  if(!ui.dialoog) return;
  var sluit = function(){ ui.dialoog = null; ui.dialoogFout = ''; render(); };
  document.getElementById('d-annuleer').addEventListener('click', sluit);
  document.getElementById('overlay').addEventListener('mousedown', function(e){ if(e.target.id === 'overlay') sluit(); });
  document.addEventListener('keydown', function esc2(e){
    if(e.key === 'Escape' && ui.dialoog){ document.removeEventListener('keydown', esc2); sluit(); }
  });
  var ok = document.getElementById('d-ok');
  var isWijzig = ui.dialoog === 'wijzig';
  var bevestig = function(){
    if(isWijzig){
      var oud = document.getElementById('d-oud').value;
      var n1 = document.getElementById('d-nieuw').value;
      var n2 = document.getElementById('d-nieuw2').value;
      if(slotAan() && hashPw(oud, S.beheer.salt) !== S.beheer.hash){ toonDialoogFout('Het huidige wachtwoord klopt niet.'); return; }
      if(n1.length < 4){ toonDialoogFout('Kies een wachtwoord van minstens vier tekens.'); return; }
      if(n1 !== n2){ toonDialoogFout('De twee nieuwe wachtwoorden zijn niet gelijk.'); return; }
      S.beheer = { salt: String(Date.now()) + '-' + Math.random().toString(36).slice(2), hash: '', standaard: false };
      S.beheer.hash = hashPw(n1, S.beheer.salt);
      ui.ontgrendeld = true; onthoudOntgrendeling();
      ui.dialoog = null; ui.dialoogFout = '';
      markDirty(); render();
      statusMsg = 'Nieuw wachtwoord ingesteld — nog opslaan'; statusTone = 'dirty'; syncSave();
    } else {
      var pw = document.getElementById('d-pw').value;
      if(hashPw(pw, S.beheer.salt) !== S.beheer.hash){ toonDialoogFout('Onjuist wachtwoord.'); return; }
      ui.ontgrendeld = true; onthoudOntgrendeling();
      ui.dialoog = null; ui.dialoogFout = ''; ui.tab = 'tarieven'; render();
    }
  };
  ok.addEventListener('click', bevestig);
  var eerste = document.getElementById(isWijzig ? 'd-oud' : 'd-pw');
  if(eerste){ eerste.focus(); }
  var velden = document.querySelectorAll('.dialog input');
  for(var i=0;i<velden.length;i++){
    velden[i].addEventListener('keydown', function(e){ if(e.key === 'Enter') bevestig(); });
  }
}

function numField(id, set){
  var el = document.getElementById(id);
  el.addEventListener('change', function(){
    var v = parseNL(el.value);
    if(v!=null){ set(v); markDirty(); render(); }
  });
}
function alertBanner(msg){ statusMsg = msg; statusTone = 'err'; syncSave(); }

/* ---------------- opslaan ---------------- */
function bouwDocument(){
  var style = document.getElementById('app-style').textContent;
  var script = document.getElementById('app-script').textContent;
  var data = JSON.stringify(S).replace(/</g,'\\u003c');
  return '<!doctype html><html lang="nl"><head><meta charset="utf-8">' +
    '<meta name="viewport" content="width=device-width, initial-scale=1">' +
    '<title>Transportcalculator Europoort</title>' +
    '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Source+Sans+3:wght@400;600&family=IBM+Plex+Mono:wght@400;500&display=swap">' +
    '<style id="app-style">' + style + '</style></head><body>' +
    '<div id="root"></div>' +
    '<script type="application/json" id="app-state">' + data + '<\/script>' +
    '<script id="app-script">' + script + '<\/script>' +
    '</body></html>';
}

function opslaan(){
  if(saving) return;
  if(!magBeheren()){ statusMsg = 'Je mag de tarieven niet aanpassen.'; statusTone = 'err'; syncSave(); return; }
  saving = true; statusMsg = 'Bezig met opslaan…'; statusTone = ''; syncSave();
  fetch(window.TC_SERVER.saveUrl, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-TOKEN': window.TC_SERVER.csrf, 'X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({payload: S, version: window.TC_SERVER.version})
  }).then(function(r){ return r.json().then(function(j){ return {status: r.status, j: j}; }); })
  .then(function(res){
    saving = false;
    if(res.j && res.j.ok){
      window.TC_SERVER.version = res.j.version;
      dirty = false; statusMsg = 'Opgeslagen voor iedereen'; statusTone = 'ok';
    } else if(res.status === 409){
      statusMsg = (res.j && res.j.error) || 'Iemand anders sloeg net op — herlaad de pagina.'; statusTone = 'err';
    } else if(res.status === 403){
      statusMsg = 'Je mag de tarieven niet aanpassen.'; statusTone = 'err';
    } else {
      statusMsg = (res.j && res.j.error) || 'Opslaan mislukt. Je wijzigingen staan nog op het scherm — probeer opnieuw.'; statusTone = 'err';
    }
    syncSave();
  }).catch(function(){
    saving = false; statusMsg = 'Opslaan mislukt (netwerk). Je wijzigingen staan nog op het scherm.'; statusTone = 'err'; syncSave();
  });
}

window.addEventListener('beforeunload', function(e){ if(dirty){ e.preventDefault(); e.returnValue = ''; } });

/* Tarieven komen server-side uit #app-state (ingespoten door CORE). Opslaan gaat naar de
   server (alleen planner/admin/superadmin). Geen localStorage/artifact meer. */
canWrite = !!(window.TC_SERVER && window.TC_SERVER.canEdit);
render();
})();
</script>

</body>
</html>

@endverbatim
