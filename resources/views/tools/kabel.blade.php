@verbatim
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welke kabel? – Boels Industrial</title>
<style>
  :root{
    --ink:#1b1f23;--graphite:#3d454d;--fog:#eef0f2;--paper:#f7f8f9;--line:#d5d9de;
    --orange:#f26a1b;--orange-deep:#c9530f;--ok:#1f8a4c;--warn:#d9a400;--bad:#c8341f;
    --mono:"IBM Plex Mono","SFMono-Regular",Consolas,monospace;
    --sans:"IBM Plex Sans","Segoe UI",Helvetica,Arial,sans-serif;
    --cond:"Barlow Condensed","Arial Narrow","Helvetica Neue",Arial,sans-serif;
  }
  *{box-sizing:border-box}
  [hidden]{display:none!important}
  html,body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--sans);font-size:16px;line-height:1.45}
  header{background:var(--ink);color:#fff;padding:18px 28px;display:flex;align-items:baseline;gap:18px;flex-wrap:wrap}
  header .brand{font-family:var(--cond);font-weight:700;font-size:15px;letter-spacing:.14em;text-transform:uppercase;color:var(--orange)}
  header h1{font-family:var(--cond);font-weight:600;font-size:30px;margin:0;text-transform:uppercase}
  header .sub{color:#aab2ba;font-size:13px;margin-left:auto}

  main{max-width:1100px;margin:0 auto;padding:24px 20px 60px;display:grid;grid-template-columns:400px 1fr;gap:24px}
  @media(max-width:900px){main{grid-template-columns:1fr}}

  .step{background:#fff;border:1px solid var(--line);border-radius:6px;padding:18px 20px;margin-bottom:14px}
  .step h2{font-family:var(--cond);text-transform:uppercase;letter-spacing:.08em;font-size:15px;font-weight:700;color:var(--graphite);margin:0 0 12px;display:flex;gap:10px;align-items:center}
  .step h2 .n{display:inline-flex;width:26px;height:26px;border-radius:50%;background:var(--orange);color:#fff;align-items:center;justify-content:center;font-size:14px}
  .choices{display:grid;grid-template-columns:repeat(auto-fit,minmax(88px,1fr));gap:8px}
  .choices button{font:inherit;font-family:var(--cond);font-weight:700;font-size:19px;letter-spacing:.02em;padding:12px 6px;border:2px solid var(--line);border-radius:6px;background:#fff;color:var(--graphite);cursor:pointer}
  .choices button small{display:block;font-family:var(--sans);font-weight:400;font-size:12px;color:#6b737b;letter-spacing:0}
  .choices button.on{border-color:var(--ink);background:var(--ink);color:#fff}
  .choices button.on small{color:#cfd5da}
  .tabs{display:flex;gap:6px;margin-bottom:10px}
  .tabs button{font:inherit;font-size:13px;font-weight:600;padding:6px 12px;border:1px solid var(--line);border-radius:999px;background:#fff;color:var(--graphite);cursor:pointer}
  .tabs button.on{background:var(--orange);border-color:var(--orange);color:#fff}
  .field label{display:block;font-size:12px;font-weight:600;color:var(--graphite);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
  .field input,.field select{width:100%;font:inherit;font-family:var(--mono);font-size:20px;padding:10px 12px;border:1px solid var(--line);border-radius:4px;background:var(--paper);color:var(--ink)}
  .field input:focus,.field select:focus,button:focus-visible{outline:2px solid var(--orange);outline-offset:1px}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .hint{font-size:12px;color:#6b737b;margin-top:6px}
  .toggle{display:flex;align-items:center;gap:10px;font-size:14px;margin-top:10px;cursor:pointer}
  .toggle input{width:18px;height:18px;accent-color:var(--orange)}
  details{border-top:1px dashed var(--line);margin-top:12px;padding-top:10px}
  summary{cursor:pointer;font-size:13px;font-weight:600;color:var(--graphite)}
  details .row{margin-top:10px}
  details .field input,details .field select{font-size:15px;padding:7px 10px}

  /* result */
  .verdict{background:var(--ink);color:#fff;border-radius:6px;padding:20px 24px;position:relative;overflow:hidden;margin-bottom:14px}
  .verdict .stripe{position:absolute;left:0;top:0;bottom:0;width:6px;background:var(--ok)}
  .verdict.warn .stripe{background:var(--warn)}
  .verdict.bad .stripe{background:var(--bad)}
  .verdict .eyebrow{font-family:var(--cond);text-transform:uppercase;letter-spacing:.12em;font-size:13px;color:#aab2ba}
  .verdict .big{font-family:var(--cond);font-weight:700;font-size:40px;line-height:1.05;margin:6px 0 4px}
  .verdict .why{font-size:15px;color:#cfd5da;max-width:62ch}

  .bon{background:#fff;border:1px solid var(--line);border-radius:6px;overflow:hidden;margin-bottom:14px}
  .bon .head{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:var(--fog)}
  .bon .head h3{font-family:var(--cond);text-transform:uppercase;letter-spacing:.08em;font-size:15px;margin:0;color:var(--graphite)}
  .bon .head button{font:inherit;font-size:13px;font-weight:600;padding:6px 12px;border:1px solid var(--ink);border-radius:4px;background:#fff;cursor:pointer}
  .bon .head button:active{background:var(--ink);color:#fff}
  .line{display:grid;grid-template-columns:64px 110px 1fr;gap:12px;align-items:center;padding:12px 18px;border-top:1px solid var(--line)}
  .line .qty{font-family:var(--cond);font-weight:700;font-size:28px;text-align:right}
  .line .qty small{font-size:14px;color:#6b737b;font-weight:600}
  .line .grp{font-family:var(--mono);font-size:22px;font-weight:600;color:var(--orange-deep)}
  .line .desc{font-size:14px;line-height:1.3}
  .line .desc b{display:block;font-family:var(--cond);font-size:17px;font-weight:700}
  .line.note{grid-template-columns:1fr;background:#fff8f3;font-size:13px;color:var(--graphite)}

  .facts{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
  @media(max-width:600px){.facts{grid-template-columns:1fr}}
  .fact{background:#fff;border:1px solid var(--line);border-radius:6px;padding:12px 14px}
  .fact .k{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#6b737b;font-weight:600}
  .fact .v{font-family:var(--mono);font-size:22px;margin-top:2px}
  .fact .v small{font-size:12px;color:#6b737b}
  .bar{height:10px;background:var(--fog);border-radius:999px;position:relative;overflow:hidden;margin-top:8px}
  .bar .fill{position:absolute;left:0;top:0;bottom:0;background:var(--ok);border-radius:999px}
  .bar .fill.warn{background:var(--warn)}.bar .fill.bad{background:var(--bad)}
  .bar .lim{position:absolute;top:0;bottom:0;width:2px;background:var(--ink)}

  .alt{background:#fff;border:1px solid var(--line);border-radius:6px;padding:14px 18px;margin-bottom:14px}
  .alt h3{font-family:var(--cond);text-transform:uppercase;letter-spacing:.08em;font-size:15px;margin:0 0 8px;color:var(--graphite)}
  .alt ul{margin:0;padding-left:0;list-style:none}
  .alt li{padding:6px 0;border-top:1px solid var(--fog);font-size:14px;display:grid;grid-template-columns:80px 1fr;gap:10px}
  .alt li:first-child{border-top:0}
  .alt li code{font-family:var(--mono);color:var(--orange-deep);font-size:15px;font-weight:600}
  .alt p{margin:0 0 8px;font-size:14px;color:var(--graphite)}
  .notes{font-size:12px;color:#6b737b;line-height:1.5}
  .toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:var(--ink);color:#fff;padding:10px 18px;border-radius:999px;font-size:14px;opacity:0;transition:opacity .2s;pointer-events:none}
  .toast.show{opacity:1}
  @media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>
<header>
  <img src="/images/boels-industrial.png" alt="Boels Industrial" style="height:46px;width:auto;border-radius:6px;align-self:center;">
  <h1>Welke kabel?</h1>
  <span class="sub">Verlengkabels uit de materieellijst · spanningsval gecontroleerd</span>
  <a href="/launcher" style="color:#aab2ba;font-size:13px;text-decoration:none;border:1px solid #3d454d;border-radius:6px;padding:6px 12px;align-self:center;">&larr; Terug naar dashboard</a>
</header>

<main>
<section aria-label="Invoer">
  <div class="step">
    <h2><span class="n">1</span>Spanning</h2>
    <div class="choices" id="volt">
      <button data-v="230">230 V<small>1-fase</small></button>
      <button data-v="400" class="on">400 V<small>3-fase</small></button>
    </div>
  </div>

  <div class="step">
    <h2><span class="n">2</span>Wat sluit je aan?</h2>
    <div class="tabs" id="mode">
      <button data-m="plug" class="on">Stekker op de machine</button>
      <button data-m="A">Ampère</button>
      <button data-m="kW">kW</button>
    </div>
    <div id="mPlug" class="choices">
      <button data-a="16">16 A</button>
      <button data-a="32" class="on">32 A</button>
      <button data-a="63">63 A</button>
      <button data-a="125">125 A</button>
      <button data-a="126">&gt;125 A<small>losse aders</small></button>
    </div>
    <div id="big" hidden>
      <div class="field" style="margin-top:14px"><label for="bigA">Stroom (A per fase) – staat op de generator/machine</label><input id="bigA" type="number" min="126" step="10" value="250"></div>
      <div class="field"><label>Aansluiting op de generator</label>
        <div class="tabs" id="gen"><button data-c="pl" class="on">Powerlock</button><button data-c="m12">Klemmenbord (M12)</button></div></div>
      <div class="field"><label>Aansluiting bij de machine / verdeler</label>
        <div class="tabs" id="load"><button data-c="pl" class="on">Powerlock</button><button data-c="m12">Klemmenbord (M12)</button><button data-c="cee">CEE-stekkers</button></div></div>
      <div class="hint">Losse aders: 5 stuks per lengte (L1, L2, L3, N, PE). Bij meer stroom dan één ader aankan legt de tool aders parallel.</div>
    </div>
    <div id="mA" class="field" hidden>
      <label for="inA">Stroom (A per fase)</label>
      <input id="inA" type="number" min="1" step="1" value="40">
      <div class="hint">Staat op het typeplaatje van de machine</div>
    </div>
    <div id="mKW" class="field" hidden>
      <label for="inKW">Vermogen (kW)</label>
      <input id="inKW" type="number" min="0.1" step="0.5" value="20">
      <div class="hint">Bij kVA: vul kVA × 0,8 in als kW</div>
    </div>
  </div>

  <div class="step">
    <h2><span class="n">3</span>Afstand</h2>
    <div class="field">
      <label for="len">Van stroombron tot machine (m)</label>
      <input id="len" type="number" min="1" step="5" value="50">
      <div class="hint">Loopafstand van de kabel, ruim meten</div>
    </div>
    <label class="toggle"><input type="checkbox" id="ex"> Machine staat in een EX / ATEX-zone</label>
    <label class="toggle" id="raWrap" hidden><input type="checkbox" id="ra"> Machine heeft een gewone stekker (randaarde) in plaats van CEE</label>

    <details>
      <summary>Geavanceerd</summary>
      <div class="row">
        <div class="field">
          <label for="drop">Max. spanningsval</label>
          <select id="drop">
            <option value="3">3 % (verlichting, elektronica)</option>
            <option value="5" selected>5 % (standaard)</option>
            <option value="8">8 % (met klant afgestemd)</option>
          </select>
        </div>
        <div class="field">
          <label for="cos">cos φ</label>
          <input id="cos" type="number" min="0.5" max="1" step="0.05" value="0.85">
        </div>
      </div>
    </details>
  </div>
</section>

<section aria-live="polite" id="out">
  <div class="verdict" id="verdict">
    <div class="stripe"></div>
    <div class="eyebrow" id="vEye">Advies</div>
    <div class="big" id="vBig">—</div>
    <div class="why" id="vWhy"></div>
  </div>

  <div class="bon" id="bon">
    <div class="head"><h3>Materieel</h3><button type="button" id="copy">Kopieer regels</button></div>
    <div id="lines"></div>
  </div>

  <div class="facts">
    <div class="fact"><div class="k">Stroom per fase</div><div class="v" id="fI">— <small>A</small></div></div>
    <div class="fact"><div class="k">Spanningsval</div><div class="v" id="fD">— <small>%</small></div><div class="bar"><div class="fill" id="fFill"></div><div class="lim" id="fLim"></div></div></div>
    <div class="fact"><div class="k">Max. afstand met deze kabel</div><div class="v" id="fL">— <small>m</small></div></div>
  </div>

  <div class="alt" id="escal" hidden></div>
  <div class="alt" id="variants" hidden></div>

  <p class="notes">Spanningsval berekend op de werkelijk geleverde kabellengte (gekoppelde stukken opgeteld), koper bij bedrijfstemperatuur, cos φ instelbaar. Bij stekker-invoer rekent de tool met de volle stekkerstroom; vul de echte stroom in voor een scherper advies. Richtwaarde voor de binnendienst; de installatie ter plaatse blijft de verantwoordelijkheid van de klant.</p>
</section>
</main>
<div class="toast" id="toast">Gekopieerd</div>

<script>
(() => {
/* =====================================================================
   KABELS UIT DE MATERIEELLIJST (12-08-2026)
   Per familie: V, stekkerklasse (A), aders x mm², EX ja/nee, stekkertype,
   en de beschikbare lengtes met groepsnummer. Aanpassen = hier.
   ===================================================================== */
const FAM = [
  // 230 V · 16 A
  {V:230,cls:16,cores:3,s:2.5,plug:'CEE',ex:false,items:[
    {g:'82115',len:10,d:'Extension cable 230V 16A 10m 3x2.5mm² CEE'},
    {g:'81514',len:25,d:'Extension cable 230V 16A 25m 3x2.5mm² CEE'},
    {g:'81516',len:50,d:'Extension cable 230V 16A 50m 3x2.5mm² CEE'}],
   variants:[
    {g:'81515',d:'Extension cable 230V 16A 25m 3x2.5mm² CEE IP67'},
    {g:'81517',d:'Extension cable 230V 16A 25m 3x2.5mm² PA (penaarde, BE)'}]},
  {V:230,cls:16,cores:3,s:2.5,plug:'RA',ex:false,items:[
    {g:'81518',len:10,d:'Extension cable 230V 16A 10m 3x2.5mm² RA'},
    {g:'81519',len:25,d:'Extension cable 230V 16A 25m 3x2.5mm² RA'}],
   variants:[
    {g:'81502',d:'Cable reel 25m 230V 16A 3x2.5mm² RA (haspel – volledig afrollen)'},
    {g:'81504',d:'Cable reel 30m 230V 16A 3x2.5mm² RA (haspel – volledig afrollen)'},
    {g:'81505',d:'Cable reel 40m 230V 16A 3x2.5mm² RA (haspel – volledig afrollen)'},
    {g:'81562',d:'Adapter cable 230V 16A 25m 2p RA > 3p CEE'}]},
  {V:230,cls:16,cores:3,s:2.5,plug:'CEE',ex:true,items:[
    {g:'71797',len:25,d:'Extension cable EX 230V 16A 25m 3x2.5mm²'}]},

  // 400 V · 16 A
  {V:400,cls:16,cores:5,s:2.5,plug:'CEE',ex:false,items:[
    {g:'81512',len:10,d:'Extension cable 400V 16A 10m 5x2.5mm² CEE'},
    {g:'81513',len:25,d:'Extension cable 400V 16A 25m 5x2.5mm² CEE'}],
   variants:[
    {g:'81511',d:'Extension cable 400V 16A 10m 4x2.5mm² CEE (4-polig, zonder nul)'},
    {g:'81538',d:'Extension cable 400V 16A 25m 4x2.5mm² CEE (4-polig, zonder nul)'}]},
  {V:400,cls:16,cores:5,s:6,plug:'CEE',ex:true,items:[
    {g:'71799',len:25,d:'Extension cable EX 400V 16A 25m 5x6mm²'}]},

  // 400 V · 32 A
  {V:400,cls:32,cores:5,s:6,plug:'CEE',ex:false,items:[
    {g:'81529',len:5,d:'Extension cable 400V 32A 5m 5x6mm² CEE'},
    {g:'81525',len:10,d:'Extension cable 400V 32A 10m 5x6mm² CEE'},
    {g:'81524',len:25,d:'Extension cable 400V 32A 25m 5x6mm² CEE'},
    {g:'81528',len:50,d:'Extension cable 400V 32A 50m 5x6mm² CEE'}],
   variants:[
    {g:'81527',d:'Extension cable 400V 32A 25m 5x6mm² CEE IP67'},
    {g:'81522',d:'Extension cable 400V 32A 10m 4x6mm² CEE (4-polig, zonder nul)'},
    {g:'81523',d:'Extension cable 400V 32A 25m 4x6mm² CEE (4-polig, zonder nul)'},
    {g:'81814',d:'Extension cable 400V 32A 4p CEE 25m 5x6mm², 3h Reefer'}]},
  {V:400,cls:32,cores:5,s:6,plug:'CEE',ex:true,items:[
    {g:'71800',len:25,d:'Extension cable EX 400V 32A 25m 5x6mm²'}]},

  // 400 V · 63 A
  {V:400,cls:63,cores:5,s:16,plug:'CEE',ex:false,items:[
    {g:'81543',len:5,d:'Extension cable 63A 5m 5x16mm², CEE'},
    {g:'81545',len:10,d:'Extension cable 400V 63A 10m 5x16mm² CEE'},
    {g:'81547',len:25,d:'Extension cable 400V 63A 25m 5x16mm² CEE'},
    {g:'81539',len:50,d:'Extension cable 400V 63A 50m 5x16mm² CEE'}]},
  {V:400,cls:63,cores:5,s:16,plug:'CEE',ex:true,items:[
    {g:'71803',len:25,d:'Extension cable EX 400V 63A 25m 5x16mm²'},
    {g:'71804',len:50,d:'Extension cable EX 400V 63A 50m 5x16mm²'},
    {g:'71802',len:100,d:'Extension cable EX 400V 63A 100m 5x16mm²'}]},

  // 400 V · 125 A  (twee doorsnedes: 35 en 50 mm²)
  {V:400,cls:125,cores:5,s:35,plug:'CEE',ex:false,items:[
    {g:'85315',len:5,d:'Extension cable 5m 125A CEE, 5x35mm²'},
    {g:'85319',len:10,d:'Extension cable 125A CEE, 5x35mm², 10m'},
    {g:'85320',len:25,d:'Extension cable 125A CEE, 5x35mm², 25m'}],
   variants:[
    {g:'81530',d:'Extension cable 400V 125A 25m 4x35mm² CEE (4-polig, zonder nul)'}]},
  {V:400,cls:125,cores:5,s:50,plug:'CEE',ex:false,items:[
    {g:'81535',len:2,d:'Extension cable 400V 125A 2m 5x50mm² CEE'},
    {g:'81532',len:10,d:'Extension cable 400V 125A 10m 5x50mm² CEE'},
    {g:'81534',len:25,d:'Extension cable 400V 125A 25m 5x50mm² CEE'}]},
  {V:400,cls:125,cores:5,s:16,plug:'CEE',ex:true,items:[
    {g:'14307',len:25,d:'Extension cable EX 125A/5P 5x16mm² 25m (omschrijving controleren: 16mm² is normaal 63A)'}]},
];

// Powerlock singles (> 125 A): per stuk, 5 stuks per lengte (L1 L2 L3 N PE)
const PL = [
  {s:70, amp:256, items:[{g:'84373',len:5},{g:'84370',len:10},{g:'84371',len:20},{g:'84372',len:25}]},
  {s:95, amp:314, items:[{g:'84380',len:5},{g:'84374',len:10},{g:'84375',len:15},{g:'84376',len:20},{g:'84377',len:25},{g:'84379',len:50}]},
  {s:120,amp:365, items:[{g:'84338',len:5},{g:'84326',len:10},{g:'84327',len:15},{g:'84333',len:20},{g:'84334',len:25},{g:'84335',len:30},{g:'84337',len:50},{g:'84340',len:60},{g:'84341',len:90}]},
  {s:185,amp:484, items:[{g:'84345',len:5},{g:'84342',len:10},{g:'84343',len:25},{g:'84344',len:30},{g:'84382',len:60}]},
  {s:240,amp:573, items:[{g:'84351',len:5},{g:'84346',len:10},{g:'84347',len:15},{g:'84348',len:20},{g:'84349',len:25},{g:'84350',len:30},{g:'81486',len:50},{g:'84352',len:60},{g:'84353',len:90}]},
];
// Aansluitstaarten 1 m 120mm² met M12-kabelschoen (source = generatorzijde, drain = machinezijde)
const TAIL_SRC=[{g:'84390',d:'L1'},{g:'84386',d:'L2'},{g:'84387',d:'L3'},{g:'84388',d:'N'},{g:'84385',d:'PE'}];
const TAIL_DRN=[{g:'81488',d:'L1'},{g:'81489',d:'L2'},{g:'84389',d:'L3'},{g:'84384',d:'N'},{g:'84339',d:'PE'}];
const TAIL_DRN5=[{g:'84392',d:'L1'},{g:'84393',d:'L2'},{g:'84394',d:'L3'},{g:'84395',d:'N'},{g:'84391',d:'PE'}];
// Aansluitkasten: van kabelschoenen naar CEE-stekkers
const PDB=[{s:16,g:'82375',d:'Power distribution box 5x16mm²'},{s:35,g:'82377',d:'Power distribution box 5x35mm²'},{s:50,g:'82378',d:'Power distribution box 5x50mm²'},{s:120,g:'82388',d:'Power distribution box 5x120mm²'},{s:240,g:'82376',d:'Power distribution box 5x185/240mm²'}];
// 5-aderige kabel met M12-kabelschoenen aan beide kanten (klemmenbord ↔ klemmenbord)
const M12_5C=[
  {s:16,amp:82,items:[{g:'84354',len:25,d:'Cable 25m 5x16mm² H07 RN-F 2xM12'}]},
  {s:35,amp:135,items:[{g:'84355',len:10,d:'Cable 10m 5x35mm² H07 RN-F 2xM12'},{g:'84356',len:25,d:'Cable 25m 5x35mm² H07 RN-F 2xM12'},{g:'84357',len:50,d:'Cable 50m 5x35mm² H07 RN-F 2xM12'}]},
  {s:50,amp:168,items:[{g:'84359',len:10,d:'Cable 10m 5x50mm² H07 RN-F 2xM12'},{g:'84362',len:25,d:'Cable 25m 5x50mm² H07 RN-F 2xM12'},{g:'84363',len:50,d:'Cable 50m 5x50mm² H07 RN-F 2xM12'},{g:'84358',len:100,d:'Cable 100m 5x50mm² H07 RN-F 2xM12'}]},
  {s:70,amp:207,items:[{g:'84396',len:10,d:'Cable 10m 5x70mm² H07 RN-F'},{g:'84365',len:25,d:'Cable 25m 5x70mm² H07 RN-F 2xM12'},{g:'84397',len:50,d:'Cable 50m 5x70mm² H07 RN-F'},{g:'84398',len:100,d:'Cable 100m 5x70mm² H07 RN-F'}]},
  {s:95,amp:250,items:[{g:'84368',len:25,d:'Cable 25m 5x95mm² H07 RN-F 2xM12'},{g:'84369',len:45,d:'Cable 45m 5x95mm² H07 RN-F 2xM12'}]},
];
const PL_PERPHASE='120 mm² 10 m is ook per ader geregistreerd: 84329 L1 · 84330 L2 · 84331 L3 · 84332 N · 84328 PE. 25 m: 71730 L2 · 71731 L3. Powerlock naar kabelschoen 10 m 240 mm²: 84336.';
const PL_ADAPT = [
  {g:'84528',d:'Adapter cable 32A 400V to powerlocks'},
  {g:'84527',d:'Converter cable 63A 400V to powerlocks'},
  {g:'84526',d:'Adapter cable 125A 400V to powerlocks'}];

// Opschalen naar een zwaardere klasse: verloop aan de bron + verdeelkast bij de machine
const UP = {
  16:{to:32, adapter:{g:'81811',d:'Adapter cable 400V 16A 5p CEE > 32A 5p CEE'},
          cabinet:{g:'81668',d:'Distribution cabinet 32A 400V CEE (16-2/400V 16-6x230V) linkable'}},
  32:{to:63, adapter:{g:'84216',d:'Adapter cable 400V 32A 5p CEE male > 63A female'},
          cabinet:{g:'81693',d:'Distribution cabinet 63A 400V CEE (32-4/400V, 16-6/230V)'}},
  63:{to:125,adapter:{g:'84217',d:'Adapter cable 400V 63A 5p CEE male > 125A female'},
          cabinet:{g:'81652',d:'Distribution cabinet 125A 400V CEE (63-2/400V, 32-2/400V)'}},
};

/* ===================== rekenen ===================== */
const RHO=0.021, X=0.00008;
const $=id=>document.getElementById(id);
const S={V:400,mode:'plug',plug:32,gen:'pl',load:'pl'};
const f1=n=>n.toLocaleString('nl-NL',{maximumFractionDigits:1});
const f0=n=>n.toLocaleString('nl-NL',{maximumFractionDigits:0});

function dropV(I,cos,L,s){const k=S.V===400?Math.sqrt(3):2;const sin=Math.sqrt(Math.max(0,1-cos*cos));return k*L*I*((RHO/s)*cos+X*sin);}
function maxLen(I,cos,s,limV){const k=S.V===400?Math.sqrt(3):2;const sin=Math.sqrt(Math.max(0,1-cos*cos));return limV/(k*I*((RHO/s)*cos+X*sin));}
function clsFor(I){return I<=16?16:I<=32?32:I<=63?63:I<=125?125:126;}

// kleinste aantal stukken, dan kleinste totale lengte, dat samen >= L
function compose(items,L){
  const maxL=Math.max(...items.map(i=>i.len));
  const cap=L+maxL;
  const best=Array(cap+1).fill(null); best[0]={n:0,pieces:[]};
  for(let t=1;t<=cap;t++){
    for(const it of items){
      if(it.len>t||!best[t-it.len])continue;
      const c={n:best[t-it.len].n+1,pieces:[...best[t-it.len].pieces,it]};
      if(!best[t]||c.n<best[t].n)best[t]=c;
    }
  }
  for(let t=L;t<=cap;t++){
    if(best[t]){
      const cnt={};best[t].pieces.forEach(p=>cnt[p.g]=(cnt[p.g]||0)+1);
      return {total:t,lines:Object.keys(cnt).map(g=>({qty:cnt[g],...items.find(i=>i.g===g)}))};
    }
  }
  return null;
}

function current(){
  const cos=+$('cos').value||0.85;
  if(S.mode==='plug')return S.plug===126?{I:+$('bigA').value||126,cos}:{I:S.plug,cos,fromPlug:true};
  if(S.mode==='A')return {I:+$('inA').value||0,cos};
  const P=(+$('inKW').value||0)*1000;
  return {I:S.V===400?P/(Math.sqrt(3)*400*cos):P/(230*cos),cos};
}

function evalFam(fam,I,cos,L,limPct){
  const c=compose(fam.items,L); if(!c)return null;
  const dv=dropV(I,cos,c.total,fam.s), pct=dv/S.V*100;
  return {fam,comp:c,dv,pct,ok:pct<=limPct,maxL:maxLen(I,cos,fam.s,S.V*limPct/100)};
}

function render(){
  const {I,cos,fromPlug}=current();
  const L=+$('len').value||0, limPct=+$('drop').value, ex=$('ex').checked, ra=$('ra').checked;
  const v=$('verdict'), lines=$('lines'); lines.innerHTML=''; $('escal').hidden=true; $('variants').hidden=true;
  v.className='verdict';
  if(!I||!L){$('vBig').textContent='—';$('vWhy').textContent='Kies spanning, aansluiting en afstand.';return;}

  const cls=S.mode==='plug'?S.plug:clsFor(I);
  $('big').hidden=!(cls===126&&S.V===400);
  const plugType=(S.V===230&&ra)?'RA':'CEE';
  let picked=null, extra=[], statusWord='';

  if(S.V===230&&cls>16){
    v.classList.add('bad');
    $('vEye').textContent='Let op';
    $('vBig').textContent='Meer dan 16 A op 230 V hebben we niet';
    $('vWhy').textContent=`${f0(I)} A op 230 V past niet op onze 230 V-verlengkabels (max 16 A). Kijk of de machine op 400 V kan, of verdeel over meerdere 16 A-groepen via een verdeelkast.`;
    $('fI').innerHTML=`${f1(I)} <small>A</small>`;$('fD').innerHTML='— <small>%</small>';$('fL').innerHTML='— <small>m</small>';
    return;
  }

  if(cls===126){ /* losse aders */
    const gen=S.gen, load=S.load;
    const res=PL.map(p=>{
      const n=Math.ceil(I/p.amp); const c=compose(p.items,L); if(!c)return null;
      const dv=dropV(I/n,cos,c.total,p.s), pct=dv/S.V*100;
      return {p,n,comp:c,dv,pct,ok:pct<=limPct,maxL:maxLen(I/n,cos,p.s,S.V*limPct/100)};
    }).filter(Boolean);
    picked=res.find(r=>r.ok&&r.n===1)||res.find(r=>r.ok)||res[res.length-1];
    v.classList.add(picked.ok?'ok':'warn');
    $('vEye').textContent='Advies · losse aders';
    $('vBig').textContent=`Losse aders ${picked.p.s} mm²${picked.n>1?` · ${picked.n}× parallel`:''}`;
    $('vWhy').textContent=(picked.ok
      ?`${f0(I)} A over ${picked.comp.total} m: spanningsval ${f1(picked.pct)} % (grens ${limPct} %). `
      :`Ook 240 mm² haalt over ${picked.comp.total} m de ${limPct} % niet (${f1(picked.pct)} %). Zet de generator dichterbij of overleg met de specialist. `)
      +`Aders zijn powerlock; ${gen==='m12'?'aan de generator':''}${gen==='m12'&&load!=='pl'?' en ':''}${load==='m12'?'bij de machine':load==='cee'?'bij de aansluitkast':''}${gen==='m12'||load!=='pl'?' gaan ze via 1 m-staarten met M12-kabelschoen.':''}`;
    picked.comp.lines.forEach(l=>addLine(lines,l.qty*5*picked.n,l.g,`Cable 400V ${picked.p.amp}A ${l.len}m ${picked.p.s}mm² H07 RN-F powerlock`,`losse aders ${l.len} m · 5 geleiders${picked.n>1?` × ${picked.n} parallel`:''}${l.qty>1?` × ${l.qty} stukken achter elkaar`:''}`));
    const sets=picked.n;
    const tails=(arr)=>arr.map(t=>`${t.g} ${t.d}`).join(' · ');
    if(gen==='m12') addLine(lines,sets,'5 st.',`Staarten generatorzijde 1m 120mm² powerlock source – lug M12: ${tails(TAIL_SRC)}`,'per ader één staart');
    if(load==='m12') addLine(lines,sets,'5 st.',`Staarten machinezijde 1m 120mm² powerlock drain – lug M12: ${tails(TAIL_DRN)}`,'per ader één staart');
    if(load==='cee'){
      const box=PDB.find(b=>b.s>=picked.p.s)||PDB[PDB.length-1];
      addLine(lines,1,box.g,box.d,'aansluitkast: kabelschoenen in, CEE-stekkers uit');
      addLine(lines,sets,'5 st.',`Staarten naar de aansluitkast 1m 120mm² powerlock drain – lug M12: ${tails(TAIL_DRN)}`,'per ader één staart');
    }
    if(gen==='m12'||load!=='pl') addNote(lines,'Staarten zijn 120 mm²; bij aders zwaarder dan 120 mm² even met het depot afstemmen. 5 m-drainstaarten: 84392 L1 · 84393 L2 · 84394 L3 · 84395 N · 84391 PE.');
    addNote(lines,PL_PERPHASE);
    facts(I,picked.pct,limPct,picked.maxL);

    // alternatief: 5-aderige kabel met kabelschoenen (klemmenbord aan beide kanten)
    if(gen==='m12'&&(load==='m12'||load==='cee')&&I<=250){
      const r5=M12_5C.filter(f=>f.amp>=I).map(f=>{const c=compose(f.items,L);if(!c)return null;const dv=dropV(I,cos,c.total,f.s),pct=dv/S.V*100;return {f,comp:c,pct,ok:pct<=limPct};}).filter(Boolean);
      const b5=r5.find(r=>r.ok)||r5[r5.length-1];
      if(b5){const e=$('escal');e.hidden=false;
        e.innerHTML=`<h3>Alternatief: één 5-aderige kabel met kabelschoenen</h3><p>Geen losse aders en geen staarten: 5x${b5.f.s} mm² met M12-kabelschoenen aan beide kanten, spanningsval ${f1(b5.pct)} %${b5.ok?'.':' – boven de grens.'}</p><ul>${b5.comp.lines.map(l=>`<li><code>${l.g}</code><span>${l.qty}× ${l.d}</span></li>`).join('')}${load==='cee'?`<li><code>${(PDB.find(b=>b.s>=b5.f.s)||PDB[PDB.length-1]).g}</code><span>1× ${(PDB.find(b=>b.s>=b5.f.s)||PDB[PDB.length-1]).d}</span></li>`:''}</ul>`;}
    } else if(gen==='pl'&&load==='cee'){
      addNote(lines,'Kleinere afnemers direct op powerlock: verloop naar CEE '+PL_ADAPT.map(a=>`${a.g} (${a.d.replace(/ 400V to powerlocks/,'')})`).join(' · '));
    }
    return;
  }

  // standaard CEE-verlengkabels van de gevraagde klasse
  const fams=FAM.filter(f=>f.V===S.V&&f.cls===cls&&f.ex===ex&&f.plug===plugType);
  const res=fams.map(f=>evalFam(f,I,cos,L,limPct)).filter(Boolean).sort((a,b)=>a.fam.s-b.fam.s);
  if(!res.length){
    v.classList.add('bad');$('vEye').textContent='Niet in de lijst';
    $('vBig').textContent=`Geen ${cls} A ${S.V} V ${ex?'EX-':''}${plugType==='RA'?'randaarde-':''}verlengkabel in de materieellijst`;
    $('vWhy').textContent='Kies een andere optie of overleg met het depot.';
    facts(I,0,limPct,0);return;
  }
  picked=res.find(r=>r.ok)||res[res.length-1];
  const lbl=`${picked.fam.cores}x${String(picked.fam.s).replace('.',',')} mm²`;
  const plugTxt=plugType==='RA'?'randaarde':`CEE ${cls} A`;
  $('vEye').textContent=picked.ok?'Advies':'Advies met kanttekening';
  $('vBig').textContent=`${lbl} · ${plugTxt}${ex?' · EX':''}`;
  const bigger=res.find(r=>r.ok&&r!==picked);
  if(picked.ok){
    v.classList.add('ok');
    $('vWhy').textContent=`${f0(I)} A over ${picked.comp.total} m geleverde kabel: spanningsval ${f1(picked.pct)} % (grens ${limPct} %). ${picked.comp.lines.length>1||picked.comp.lines[0].qty>1?'Stukken koppelen.':''}`
      +(fromPlug?' Gerekend met de volle stekkerstroom; vul de echte stroom in voor een scherper advies.':'');
  }else{
    v.classList.add('warn');
    $('vWhy').textContent=`Over ${picked.comp.total} m komt ${lbl} op ${f1(picked.pct)} % spanningsval; de grens is ${limPct} %. Met deze kabel is ${f0(picked.maxL)} m het maximum bij ${f0(I)} A. Hieronder de alternatieven.`;
  }
  picked.comp.lines.forEach(l=>addLine(lines,l.qty,l.g,l.d,`${l.len} m`));
  facts(I,picked.pct,limPct,picked.maxL);

  // opschalen bij te veel spanningsval
  if(!picked.ok&&S.V===400&&!ex&&UP[cls]){
    const u=UP[cls];
    const upFams=FAM.filter(f=>f.V===400&&f.cls===u.to&&!f.ex).map(f=>evalFam(f,I,cos,L,limPct)).filter(Boolean).sort((a,b)=>a.fam.s-b.fam.s);
    const up=upFams.find(r=>r.ok)||upFams[upFams.length-1];
    if(up){
      const e=$('escal'); e.hidden=false;
      const pieces=up.comp.lines.reduce((a,l)=>a+l.qty,0);
      e.innerHTML=`<h3>Alternatief: dikkere kabel via verdeelkast</h3>
        <p>Er loopt nog steeds ${f0(I)} A, maar een ${u.to} A-kabel is dikker (${up.fam.cores}x${up.fam.s} mm² in plaats van ${picked.fam.cores}x${picked.fam.s} mm²) en heeft dus minder spanningsval: ${f1(up.pct)} %${up.ok?', binnen de grens.':' – nog steeds te hoog; bron dichterbij zetten.'}</p>
        <p>Opbouw: aan de bron een verloop van ${cls} A-stekker naar ${u.to} A-contactdoos → ${up.comp.total} m ${u.to} A-kabel → ${u.to} A-verdeelkast naast de machine → daar de ${cls} A-stekker van de machine in.</p>
        <ul><li><code>${u.adapter.g}</code><span>1× verloop ${cls} A stekker → ${u.to} A contactdoos, aan de bron (${u.adapter.d})</span></li>
        ${up.comp.lines.map(l=>`<li><code>${l.g}</code><span>${l.qty}× ${l.d}</span></li>`).join('')}
        <li><code>${u.cabinet.g}</code><span>1× verdeelkast bij de machine, met ${cls} A-uitgangen (${u.cabinet.d})</span></li></ul>
        ${pieces>4?`<p style="margin-top:8px"><b>${pieces} losse stukken koppelen is onpraktisch.</b> Bij zulke afstanden liever de generator dichterbij, of via het klemmenbord van de generator met één lange kabel met kabelschoenen (84357 · 50 m 5x35 mm², 84358 · 100 m 5x50 mm²) naar de verdeelkast – overleg met de specialist.</p>`:''}`;
    }
  } else if(!picked.ok){
    const e=$('escal'); e.hidden=false;
    e.innerHTML=`<h3>Wat kan wel</h3><p>Zet de stroombron dichterbij (max. ${f0(picked.maxL)} m met deze kabel), gebruik een lagere werkelijke stroom als de machine niet vol belast wordt, of stem een hogere spanningsval af met de klant (Geavanceerd).${ex?' In EX-zones is opschalen via verdeelkast niet zonder EX-materieel mogelijk; overleg met de specialist.':''}</p>`;
  }

  // varianten van dezelfde familie
  const vars=(picked.fam.variants||[]);
  if(vars.length){
    const w=$('variants'); w.hidden=false;
    w.innerHTML=`<h3>Ook beschikbaar in deze klasse</h3><ul>${vars.map(x=>`<li><code>${x.g}</code><span>${x.d}</span></li>`).join('')}</ul>`;
  }
}

function addLine(host,qty,g,d,sub){
  const el=document.createElement('div');el.className='line';
  el.innerHTML=`<div class="qty">${qty}<small>×</small></div><div class="grp">${g}</div><div class="desc"><b>${d}</b>${sub||''}</div>`;
  host.appendChild(el);
}
function addNote(host,txt){const el=document.createElement('div');el.className='line note';el.textContent=txt;host.appendChild(el);}
function facts(I,pct,limPct,maxL){
  $('fI').innerHTML=`${f1(I)} <small>A</small>`;
  $('fD').innerHTML=`${f1(pct)} <small>% (max ${limPct})</small>`;
  const scale=Math.max(limPct*1.6,pct*1.1);
  const fill=$('fFill');fill.style.width=`${Math.min(100,pct/scale*100)}%`;
  fill.className='fill '+(pct>limPct?'bad':pct>limPct*0.8?'warn':'');
  $('fLim').style.left=`${limPct/scale*100}%`;
  $('fL').innerHTML=`${maxL?f0(maxL):'—'} <small>m</small>`;
}

/* ===================== events ===================== */
$('volt').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;S.V=+b.dataset.v;
  [...$('volt').children].forEach(x=>x.classList.toggle('on',x===b));
  $('raWrap').hidden=S.V!==230;
  [...$('mPlug').children].forEach(x=>{const a=+x.dataset.a;x.disabled=S.V===230&&a>16;x.style.opacity=x.disabled?.35:1;});
  if(S.V===230&&S.plug>16){S.plug=16;[...$('mPlug').children].forEach(x=>x.classList.toggle('on',+x.dataset.a===16));}
  render();});
$('mode').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;S.mode=b.dataset.m;
  [...$('mode').children].forEach(x=>x.classList.toggle('on',x===b));
  $('mPlug').hidden=S.mode!=='plug';$('mA').hidden=S.mode!=='A';$('mKW').hidden=S.mode!=='kW';render();});
$('mPlug').addEventListener('click',e=>{const b=e.target.closest('button');if(!b||b.disabled)return;S.plug=+b.dataset.a;
  [...$('mPlug').children].forEach(x=>x.classList.toggle('on',x===b));render();});
['inA','inKW','bigA','len','drop','cos','ex','ra'].forEach(id=>$(id).addEventListener('input',render));
[['gen','gen'],['load','load']].forEach(([id,key])=>$(id).addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;S[key]=b.dataset.c;[...$(id).children].forEach(x=>x.classList.toggle('on',x===b));render();}));
$('copy').addEventListener('click',()=>{
  const txt=[...$('lines').querySelectorAll('.line:not(.note)')].map(l=>`${l.querySelector('.qty').textContent.replace('×','x')} ${l.querySelector('.grp').textContent} - ${l.querySelector('.desc b').textContent}`).join('\n');
  navigator.clipboard?.writeText(txt).then(()=>{const t=$('toast');t.classList.add('show');setTimeout(()=>t.classList.remove('show'),1400);});
});
render();
})();
</script>
</body>
</html>

@endverbatim
