@extends('layouts.app')
@section('title', 'Generator Adviestool')

@section('content')
<div style="max-width: 980px; margin: 0 auto 0.5rem;">
    <a href="{{ route('launcher') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Terug naar dashboard</a>
</div>
@verbatim
<div id="boels-gen-tool" style="margin-top: 1.25rem; margin-bottom: 2rem;">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Semi+Condensed:wght@600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ---- scope alles binnen #boels-gen-tool zodat het niets in je app breekt ---- */
    #boels-gen-tool{
      --navy:#14223D; --navy2:#1B2A4E; --orange:#FF6600; --orange-d:#E25A00;
      --ink:#1d2433; --muted:#6b7686; --line:#e3e7ee; --line2:#eef1f6;
      --bg:#f4f6fa; --card:#ffffff; --good:#0f8a4f;
      --r:14px; --shadow:0 10px 30px rgba(20,34,61,.10), 0 2px 6px rgba(20,34,61,.06);
      font-family:"Barlow", -apple-system, "Segoe UI", system-ui, sans-serif;
      color:var(--ink); box-sizing:border-box; max-width:980px; margin:0 auto;
      -webkit-font-smoothing:antialiased; line-height:1.4;
    }
    #boels-gen-tool *,#boels-gen-tool *::before,#boels-gen-tool *::after{box-sizing:border-box;}

    /* ---------- TILE / CARD ---------- */
    #boels-gen-tool .bgt-card{
      background:var(--card); border-radius:var(--r); overflow:hidden;
      box-shadow:var(--shadow); border:1px solid var(--line);
    }

    /* ---------- HEADER ---------- */
    #boels-gen-tool .bgt-head{
      background:linear-gradient(135deg,var(--navy) 0%,var(--navy2) 100%);
      padding:20px 26px; display:flex; align-items:center; gap:20px;
      position:relative;
    }
    #boels-gen-tool .bgt-head::after{
      content:""; position:absolute; left:0; right:0; bottom:0; height:4px;
      background:var(--orange);
    }
    #boels-gen-tool .bgt-logo{
      height:54px; width:auto; display:block; border-radius:6px;
      box-shadow:0 2px 10px rgba(0,0,0,.25); flex:0 0 auto;
    }
    #boels-gen-tool .bgt-titles{flex:1 1 auto; min-width:0;}
    #boels-gen-tool .bgt-titles h1{
      font-family:"Barlow Semi Condensed", "Barlow", sans-serif;
      color:#fff; font-size:24px; font-weight:800; letter-spacing:.4px;
      margin:0; text-transform:uppercase; line-height:1.05;
    }
    #boels-gen-tool .bgt-titles p{
      color:#9fb0cc; font-size:13px; margin:3px 0 0; font-weight:500;
      letter-spacing:.2px;
    }

    /* ---------- MODE TOGGLE ---------- */
    #boels-gen-tool .bgt-mode{
      display:inline-flex; background:rgba(255,255,255,.10); border-radius:10px;
      padding:4px; gap:4px; flex:0 0 auto;
    }
    #boels-gen-tool .bgt-mode button{
      font-family:inherit; border:0; background:transparent; color:#cdd7e8;
      font-weight:700; font-size:14px; padding:7px 16px; border-radius:7px;
      cursor:pointer; transition:.18s; letter-spacing:.3px;
    }
    #boels-gen-tool .bgt-mode button.active{background:var(--orange); color:#fff; box-shadow:0 2px 8px rgba(255,102,0,.4);}
    #boels-gen-tool .bgt-mode button:not(.active):hover{color:#fff;}

    /* ---------- BODY GRID ---------- */
    #boels-gen-tool .bgt-body{display:grid; grid-template-columns:340px 1fr; gap:0;}
    @media(max-width:760px){#boels-gen-tool .bgt-body{grid-template-columns:1fr;}}

    /* ---------- INPUT PANEL ---------- */
    #boels-gen-tool .bgt-inputs{
      background:var(--bg); padding:22px 22px 24px; border-right:1px solid var(--line);
    }
    @media(max-width:760px){#boels-gen-tool .bgt-inputs{border-right:0; border-bottom:1px solid var(--line);}}
    #boels-gen-tool .bgt-sectlabel{
      font-family:"Barlow Semi Condensed", sans-serif;
      font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;
      color:var(--navy); padding-left:10px; position:relative; margin:0 0 14px;
    }
    #boels-gen-tool .bgt-sectlabel::before{
      content:""; position:absolute; left:0; top:1px; bottom:1px; width:4px;
      background:var(--orange); border-radius:2px;
    }
    #boels-gen-tool .bgt-field{margin-bottom:13px;}
    #boels-gen-tool .bgt-field label{
      display:block; font-size:12.5px; font-weight:600; color:var(--muted);
      margin-bottom:5px;
    }
    #boels-gen-tool .bgt-inwrap{position:relative; display:flex; align-items:center;}
    #boels-gen-tool .bgt-inwrap input{
      width:100%; font-family:inherit; font-size:15px; font-weight:600; color:var(--ink);
      padding:10px 52px 10px 12px; border:1.5px solid var(--line); border-radius:9px;
      background:#fff; transition:.15s; -moz-appearance:textfield;
    }
    #boels-gen-tool .bgt-inwrap input:focus{
      outline:0; border-color:var(--orange); box-shadow:0 0 0 3px rgba(255,102,0,.14);
    }
    #boels-gen-tool .bgt-inwrap input::-webkit-outer-spin-button,
    #boels-gen-tool .bgt-inwrap input::-webkit-inner-spin-button{-webkit-appearance:none; margin:0;}
    #boels-gen-tool .bgt-unit{
      position:absolute; right:12px; font-size:12px; font-weight:700; color:var(--muted);
      pointer-events:none; letter-spacing:.3px;
    }
    #boels-gen-tool .bgt-primary-field input{border-color:var(--navy); border-width:2px;}

    /* ---------- RESULTS PANEL ---------- */
    #boels-gen-tool .bgt-results{padding:22px 24px 18px;}
    #boels-gen-tool .bgt-block{margin-bottom:20px;}
    #boels-gen-tool .bgt-block:last-child{margin-bottom:0;}

    /* generator highlight */
    #boels-gen-tool .bgt-gen{
      background:linear-gradient(135deg,#fff 0%,#fff7f1 100%);
      border:2px solid var(--orange); border-radius:12px; padding:14px 16px;
      display:flex; align-items:center; gap:14px; margin-bottom:18px;
    }
    #boels-gen-tool .bgt-gen .ic{
      width:42px; height:42px; flex:0 0 auto; border-radius:10px; background:var(--navy);
      display:flex; align-items:center; justify-content:center;
    }
    #boels-gen-tool .bgt-gen .ic svg{width:24px; height:24px; fill:var(--orange);}
    #boels-gen-tool .bgt-gen .lbl{font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--orange-d);}
    #boels-gen-tool .bgt-gen .val{font-size:16px; font-weight:700; color:var(--navy); line-height:1.2;}

    /* spec chips (kVA / kW / A) */
    #boels-gen-tool .bgt-chips{display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px;}
    #boels-gen-tool .bgt-chip{
      background:var(--navy); border-radius:10px; padding:11px 12px; text-align:center;
    }
    #boels-gen-tool .bgt-chip .n{font-family:"Barlow Semi Condensed",sans-serif; font-size:22px; font-weight:800; color:#fff; line-height:1;}
    #boels-gen-tool .bgt-chip .u{font-size:11px; font-weight:700; color:var(--orange); letter-spacing:1px; margin-top:3px; text-transform:uppercase;}

    /* generic rows */
    #boels-gen-tool .bgt-row{
      display:flex; justify-content:space-between; align-items:center;
      padding:9px 0; border-bottom:1px solid var(--line2); font-size:14px; gap:12px;
    }
    #boels-gen-tool .bgt-row:last-child{border-bottom:0;}
    #boels-gen-tool .bgt-row .k{color:var(--muted); font-weight:600;}
    #boels-gen-tool .bgt-row .v{font-weight:700; color:var(--ink); text-align:right;}

    /* tables */
    #boels-gen-tool table.bgt-tbl{width:100%; border-collapse:collapse; font-size:13.5px;}
    #boels-gen-tool table.bgt-tbl th{
      background:var(--navy); color:#fff; font-weight:600; text-align:right;
      padding:8px 10px; font-size:12px; letter-spacing:.3px;
    }
    #boels-gen-tool table.bgt-tbl th:first-child{text-align:left; border-radius:8px 0 0 0;}
    #boels-gen-tool table.bgt-tbl th:last-child{border-radius:0 8px 0 0;}
    #boels-gen-tool table.bgt-tbl td{padding:8px 10px; text-align:right; border-bottom:1px solid var(--line2); font-weight:600;}
    #boels-gen-tool table.bgt-tbl td:first-child{text-align:left; color:var(--muted); font-weight:700;}
    #boels-gen-tool table.bgt-tbl tr:last-child td{border-bottom:0;}
    #boels-gen-tool table.bgt-tbl .tot{color:var(--orange-d); font-weight:800;}

    /* diesel stock highlight */
    #boels-gen-tool .bgt-stock{
      background:var(--navy); border-radius:12px; padding:14px 18px; margin-top:6px;
      display:flex; justify-content:space-between; align-items:center; gap:12px;
    }
    #boels-gen-tool .bgt-stock .lbl{color:#cdd7e8; font-size:12.5px; font-weight:600; max-width:60%;}
    #boels-gen-tool .bgt-stock .lbl b{color:#fff; display:block; font-size:14px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px;}
    #boels-gen-tool .bgt-stock .amt{font-family:"Barlow Semi Condensed",sans-serif; font-size:28px; font-weight:800; color:var(--orange); line-height:1; white-space:nowrap;}
    #boels-gen-tool .bgt-stock .amt span{font-size:14px; color:#9fb0cc;}

    /* footer */
    #boels-gen-tool .bgt-foot{
      background:var(--navy); padding:13px 26px; display:flex; align-items:center;
      justify-content:space-between; gap:12px;
    }
    #boels-gen-tool .bgt-foot .tag{
      font-family:"Barlow Semi Condensed",sans-serif; color:#fff; font-weight:800;
      font-size:14px; letter-spacing:1.5px; text-transform:uppercase;
    }
    #boels-gen-tool .bgt-foot .tag b{color:var(--orange);}
    #boels-gen-tool .bgt-foot .note{color:#8294b3; font-size:11px; font-weight:500; text-align:right;}

    #boels-gen-tool .bgt-callboels{color:var(--orange-d); font-weight:800;}
  </style>

  <div class="bgt-card">
    <!-- HEADER -->
    <div class="bgt-head">
      <img class="bgt-logo" src="data:image/png;base64,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" alt="Boels Industrial">
      <div class="bgt-titles">
        <h1>Generator Adviestool</h1>
        <p>Aggregaat-, kabel-, tank- &amp; brandstofadvies op basis van uw situatie</p>
      </div>
      <div class="bgt-mode" id="bgtMode">
        <button data-mode="kva" class="active">kVA</button>
        <button data-mode="kw">kW</button>
      </div>
    </div>

    <!-- BODY -->
    <div class="bgt-body">
      <!-- INPUTS -->
      <div class="bgt-inputs">
        <div class="bgt-sectlabel">Gegevens uitgangssituatie</div>

        <div class="bgt-field bgt-primary-field">
          <label id="lblPower">Benodigd vermogen</label>
          <div class="bgt-inwrap">
            <input type="number" id="inPower" value="1250" min="0" step="any">
            <span class="bgt-unit" id="unitPower">kVA</span>
          </div>
        </div>

        <div class="bgt-field">
          <label>Voltage</label>
          <div class="bgt-inwrap"><input type="number" id="inVolt" value="400" min="1" step="any"><span class="bgt-unit">V</span></div>
        </div>

        <div class="bgt-field">
          <label>Powerfactor (cos φ)</label>
          <div class="bgt-inwrap"><input type="number" id="inPf" value="0.8" min="0.1" max="1" step="0.01"><span class="bgt-unit">cos φ</span></div>
        </div>

        <div class="bgt-field">
          <label>Frequentie</label>
          <div class="bgt-inwrap"><input type="number" id="inFreq" value="50" min="1" step="any"><span class="bgt-unit">Hz</span></div>
        </div>

        <div class="bgt-field">
          <label>Gemiddelde belasting</label>
          <div class="bgt-inwrap"><input type="number" id="inLoad" value="56" min="1" max="100" step="any"><span class="bgt-unit">%</span></div>
        </div>

        <div class="bgt-field">
          <label>Draaiuren per dag</label>
          <div class="bgt-inwrap"><input type="number" id="inHours" value="8" min="0" max="24" step="any"><span class="bgt-unit">u</span></div>
        </div>

        <div class="bgt-field">
          <label>Dagen per week</label>
          <div class="bgt-inwrap"><input type="number" id="inDays" value="5" min="0" max="7" step="any"><span class="bgt-unit">dgn</span></div>
        </div>

        <div class="bgt-field">
          <label>Brandstofautonomie</label>
          <div class="bgt-inwrap"><input type="number" id="inAuto" value="3" min="0" step="any"><span class="bgt-unit">dgn</span></div>
        </div>

        <div class="bgt-field" style="margin-bottom:0;">
          <label>Dieselprijs</label>
          <div class="bgt-inwrap"><input type="number" id="inFuel" value="1.4" min="0" step="0.01"><span class="bgt-unit">€/ltr</span></div>
        </div>
      </div>

      <!-- RESULTS -->
      <div class="bgt-results">
        <div class="bgt-sectlabel">Indicatie benodigd materieel</div>

        <div class="bgt-gen">
          <div class="ic">
            <svg viewBox="0 0 24 24"><path d="M13 2L3 14h7v8l10-12h-7V2z"/></svg>
          </div>
          <div>
            <div class="lbl">Geadviseerd aggregaat</div>
            <div class="val" id="resGen">—</div>
          </div>
        </div>

        <div class="bgt-chips">
          <div class="bgt-chip"><div class="n" id="resKva">—</div><div class="u">kVA</div></div>
          <div class="bgt-chip"><div class="n" id="resKw">—</div><div class="u">kW</div></div>
          <div class="bgt-chip"><div class="n" id="resAmp">—</div><div class="u">Ampère</div></div>
        </div>

        <div class="bgt-row"><span class="k">Brandstoftank</span><span class="v" id="resTank">—</span></div>

        <div class="bgt-block" style="margin-top:18px;">
          <div class="bgt-sectlabel">Vermogenskabels</div>
          <table class="bgt-tbl">
            <thead><tr><th>Kabel</th><th>Fase</th><th>Nul</th><th>Aarde</th><th>Totaal</th></tr></thead>
            <tbody>
              <tr><td>120 mm²</td><td id="c120f">—</td><td id="c120n">—</td><td id="c120e">—</td><td class="tot" id="c120t">—</td></tr>
              <tr><td>240 mm²</td><td id="c240f">—</td><td id="c240n">—</td><td id="c240e">—</td><td class="tot" id="c240t">—</td></tr>
            </tbody>
          </table>
        </div>

        <div class="bgt-block">
          <div class="bgt-sectlabel">Indicatie brandstofverbruik &amp; -kosten</div>
          <table class="bgt-tbl">
            <thead><tr><th></th><th>Uur</th><th>Dag</th><th>Week</th></tr></thead>
            <tbody>
              <tr><td>Verbruik (ltr)</td><td id="fU">—</td><td id="fD">—</td><td id="fW">—</td></tr>
              <tr><td>Kosten (€)</td><td id="kU">—</td><td id="kD">—</td><td id="kW">—</td></tr>
            </tbody>
          </table>
        </div>

        <div class="bgt-stock">
          <div class="lbl"><b>Benodigde dieselvoorraad</b>bij de opgegeven autonomie</div>
          <div class="amt" id="resStock">—</div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="bgt-foot">
      <div class="tag">SAMEN VOOR DE <b>HOOGSTE UPTIME.</b></div>
      <div class="note">Indicatief advies — raadpleeg Boels Industrial voor de definitieve configuratie.</div>
    </div>
  </div>

  <script>
  (function(){
    // ===== DATA (uit Generator_Adviestool_2024.xlsx → Blad3) =====
    // Generatorkeuze: vanaf-drempel (kVA) → kies aggregaat dat de gevraagde kVA dekt
    // verbruik = liter/uur bij 100% belasting
    const GENERATORS = [
      {vanaf:1,       kva:3.6,  kw:2.88, oms:"11602 - Aggregaat benzine 3,6kVA 2x230V RA",        verbruik:0.72},
      {vanaf:3.7,     kva:6.5,  kw:5.2,  oms:"11603 - Aggregaat benzine 6,5kVA 1x230V RA 1x400V",  verbruik:1.3},
      {vanaf:6.6,     kva:10,   kw:8,    oms:"11620 - Aggregaat diesel 10kVA geluidgedempt",       verbruik:2},
      {vanaf:10.1,    kva:12,   kw:9.6,  oms:"84179 - Aggregaat diesel 12kVA geluidgedempt",       verbruik:2.4},
      {vanaf:12.1,    kva:17.5, kw:14,   oms:"12213 - Aggregaat diesel 17,5 kVA Stage V",          verbruik:3.5},
      {vanaf:17.6,    kva:20,   kw:16,   oms:"11604 - Aggregaat diesel 20kVA geluidgedempt",       verbruik:4},
      {vanaf:20.1,    kva:30,   kw:24,   oms:"84184 - Aggregaat diesel 30kVA geluidgedempt",       verbruik:6},
      {vanaf:30.1,    kva:40,   kw:32,   oms:"11637 - Aggregaat diesel 40kVA geluidgedempt",       verbruik:8},
      {vanaf:40.1,    kva:60,   kw:48,   oms:"11607 - Aggregaat diesel 60kVA geluidgedempt",       verbruik:12},
      {vanaf:60.1,    kva:100,  kw:80,   oms:"11608 - Aggregaat diesel 100kVA geluidgedempt",      verbruik:20},
      {vanaf:100.1,   kva:150,  kw:120,  oms:"11609 - Aggregaat diesel 150kVA geluidgedempt",      verbruik:30},
      {vanaf:150.1,   kva:200,  kw:160,  oms:"84109 - Aggregaat diesel 200kVA geluidgedempt",      verbruik:40},
      {vanaf:200.1,   kva:250,  kw:200,  oms:"84110 - Aggregaat diesel 250kVA geluidgedempt",      verbruik:50},
      {vanaf:250.1,   kva:325,  kw:260,  oms:"84186 - Aggregaat diesel 325kVA geluidgedempt",      verbruik:65},
      {vanaf:325.1,   kva:350,  kw:280,  oms:"84188 - Aggregaat diesel 350kVA geluidgedempt",      verbruik:70},
      {vanaf:350.1,   kva:500,  kw:400,  oms:"84191 - Aggregaat diesel 500kVA geluidgedempt",      verbruik:100},
      {vanaf:500.1,   kva:700,  kw:560,  oms:"84194 - Aggregaat diesel 700kVA geluidgedempt",      verbruik:140},
      {vanaf:700.1,   kva:1000, kw:800,  oms:"84529 - Aggregaat diesel 1000kVA geluidgedempt",     verbruik:200},
      {vanaf:1000.1,  kva:1250, kw:1000, oms:"84180 - Aggregaat diesel 1250kVA geluidgedempt",     verbruik:250},
      {vanaf:1250.1,  kva:1600, kw:1280, oms:"84183 - Aggregaat diesel 1600kVA geluidgedempt",     verbruik:320},
      {vanaf:1601,    kva:null, kw:null, oms:"Bel Boels",                                          verbruik:null}
    ];
    const TANKS = [
      {vanaf:1,      oms:"14617 - Brandstoftank 995L met vulbeveiliging"},
      {vanaf:996,    oms:"72108 - Brandstoftank 1600L met handpomp"},
      {vanaf:1601,   oms:"14618 - Brandstoftank 3000L met vulbeveiliging"},
      {vanaf:3001,   oms:"72111 - Brandstoftank 5500L"},
      {vanaf:5501,   oms:"81791 - Brandstoftank 8000L"},
      {vanaf:8001,   oms:"81787 - Brandstoftank 16.500L"},
      {vanaf:16501,  oms:"Bel Boels"}
    ];
    const SQRT3 = 1.73;       // zoals in de Excel gebruikt
    const CAP_120 = 365;      // A per 120mm² kabel
    const CAP_240 = 573;      // A per 240mm² kabel

    // ===== HELPERS =====
    const $ = id => document.getElementById(id);
    function pickByThreshold(arr, x){ // VLOOKUP TRUE: laatste rij waar vanaf <= x
      let r = arr[0];
      for(const row of arr){ if(x >= row.vanaf) r = row; else break; }
      return r;
    }
    const nf  = (d=0) => new Intl.NumberFormat('nl-NL',{minimumFractionDigits:d,maximumFractionDigits:d});
    function num(v, d=0){ return (v==null||!isFinite(v)) ? '—' : nf(d).format(v); }
    function eur(v, d=0){ return (v==null||!isFinite(v)) ? '—' : '€ '+nf(d).format(v); }
    const BEL = '<span class="bgt-callboels">Bel Boels</span>';

    let mode = 'kva';

    function calc(){
      const power = parseFloat($('inPower').value)||0;
      const V     = parseFloat($('inVolt').value)||0;
      const pf    = parseFloat($('inPf').value)||0;
      const load  = parseFloat($('inLoad').value)||0;
      const hours = parseFloat($('inHours').value)||0;
      const days  = parseFloat($('inDays').value)||0;
      const auto  = parseFloat($('inAuto').value)||0;
      const fuel  = parseFloat($('inFuel').value)||0;

      // kVA en kW afleiden uit invoermodus + powerfactor
      let kva, kw;
      if(mode==='kva'){ kva = power; kw = power*pf; }
      else            { kw  = power; kva = pf>0 ? power/pf : 0; }

      // Amperage = (kW × 1000) / (√3 × cos φ × V)
      const amp = (pf>0 && V>0) ? (kw*1000)/(SQRT3*pf*V) : 0;

      // Generatorkeuze op kVA
      const gen = pickByThreshold(GENERATORS, kva);

      // Brandstofverbruik: l/u aggregaat (100%) × belasting%
      const vh = gen.verbruik!=null ? gen.verbruik*(load/100) : null;
      const vd = vh!=null ? vh*hours : null;
      const vw = vd!=null ? vd*days  : null;

      // Benodigde dieselvoorraad → tankkeuze
      const stock = gen.verbruik!=null ? (load/100)*hours*auto*gen.verbruik : null;
      const tank  = stock!=null ? pickByThreshold(TANKS, stock) : null;

      // Kabels (afronden omhoog)
      const f120 = Math.ceil(amp/CAP_120), f240 = Math.ceil(amp/CAP_240);

      // ===== UITVOER =====
      $('resGen').innerHTML = gen.oms==='Bel Boels' ? BEL : gen.oms;
      $('resKva').textContent = gen.kva!=null ? num(gen.kva, gen.kva%1?1:0) : '—';
      $('resKw').textContent  = num(kw, kw%1?1:0);
      $('resAmp').textContent = num(amp, 0);
      $('resTank').innerHTML  = !tank ? '—' : (tank.oms==='Bel Boels' ? BEL : tank.oms);

      $('c120f').textContent=num(f120); $('c120n').textContent=num(f120*0.5,1); $('c120e').textContent='1'; $('c120t').textContent=num(1+f120*0.5+3*f120,1);
      $('c240f').textContent=num(f240); $('c240n').textContent=num(f240*0.5,1); $('c240e').textContent='1'; $('c240t').textContent=num(1+f240*0.5+3*f240,1);

      $('fU').innerHTML = vh==null?BEL:num(vh,1); $('fD').innerHTML = vd==null?BEL:num(vd,0); $('fW').innerHTML = vw==null?BEL:num(vw,0);
      $('kU').innerHTML = vh==null?BEL:eur(vh*fuel,2); $('kD').innerHTML = vd==null?BEL:eur(vd*fuel,0); $('kW').innerHTML = vw==null?BEL:eur(vw*fuel,0);

      $('resStock').innerHTML = stock==null ? BEL : (num(stock,0)+' <span>ltr</span>');
    }

    // ===== MODE TOGGLE =====
    $('bgtMode').addEventListener('click', e=>{
      const b = e.target.closest('button'); if(!b) return;
      mode = b.dataset.mode;
      $('bgtMode').querySelectorAll('button').forEach(x=>x.classList.toggle('active', x===b));
      $('unitPower').textContent = mode==='kva'?'kVA':'kW';
      $('lblPower').textContent  = 'Benodigd vermogen';
      calc();
    });

    // ===== LIVE BEREKENEN =====
    document.querySelectorAll('#boels-gen-tool input').forEach(i=> i.addEventListener('input', calc));
    calc();
  })();
  </script>
</div>
@endverbatim
@endsection
