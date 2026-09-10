@verbatim
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Go-Assets aanvraagtool — Boels Industrial</title>
<style>
  :root{
    --oranje:#F08100;
    --oranje-dk:#C86A00;
    --ink:#1B1F23;
    --ink-2:#4A5560;
    --ink-3:#7A8794;
    --lijn:#E2E6EA;
    --vlak:#F5F7F9;
    --wit:#FFFFFF;
    --industrial:#1F7A5A;
    --industrial-bg:#E8F4EF;
    --security:#4B4F9E;
    --security-bg:#ECEDF8;
    --ok:#1F7A5A;
    --warn:#B26B00;
    --warn-bg:#FDF3E3;
    --fout:#B3261E;
    --fout-bg:#FCEEED;
    --radius:10px;
    --schaduw:0 1px 2px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.06);
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--vlak);color:var(--ink);
    font:15px/1.5 "Segoe UI",Roboto,-apple-system,Helvetica,Arial,sans-serif;}
  .wrap{max-width:1080px;margin:0 auto;padding:0 16px 64px}

  /* ---------- header ---------- */
  header.app{background:var(--wit);border-bottom:1px solid var(--lijn);position:sticky;top:0;z-index:50}
  .hd{max-width:1080px;margin:0 auto;padding:12px 16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .logo{font-weight:800;letter-spacing:-.02em;font-size:18px;display:flex;align-items:center;gap:9px}
  .logo .blok{background:var(--oranje);color:#fff;border-radius:6px;padding:3px 8px;font-size:13px;letter-spacing:.04em}
  .hd .sub{color:var(--ink-3);font-size:13px;border-left:1px solid var(--lijn);padding-left:14px}
  .hd .rechts{margin-left:auto;display:flex;align-items:center;gap:10px}
  .pill{font-size:12px;padding:4px 10px;border-radius:999px;background:var(--vlak);border:1px solid var(--lijn);color:var(--ink-2);white-space:nowrap}
  .pill.demo{background:var(--warn-bg);border-color:#EBD3A8;color:var(--warn)}
  .pill.live{background:var(--industrial-bg);border-color:#BFE0D3;color:var(--industrial)}
  .pill.user{background:#fff}
  .pill.user b{color:var(--ink)}

  nav.tabs{max-width:1080px;margin:0 auto;padding:0 16px;display:flex;gap:2px;overflow-x:auto}
  nav.tabs button{background:none;border:0;border-bottom:2px solid transparent;padding:10px 14px;
    font:inherit;font-weight:600;color:var(--ink-3);cursor:pointer;white-space:nowrap}
  nav.tabs button:hover{color:var(--ink)}
  nav.tabs button[aria-selected="true"]{color:var(--oranje-dk);border-bottom-color:var(--oranje)}
  nav.tabs .telbadge{display:inline-block;min-width:18px;padding:0 5px;margin-left:6px;border-radius:9px;
    background:var(--lijn);color:var(--ink-2);font-size:11px;line-height:18px;text-align:center;vertical-align:1px}

  /* ---------- kaarten ---------- */
  .kaart{background:var(--wit);border:1px solid var(--lijn);border-radius:var(--radius);box-shadow:var(--schaduw);margin-top:16px}
  .kaart > h2{margin:0;padding:14px 18px;border-bottom:1px solid var(--lijn);font-size:15px;letter-spacing:-.01em;
    display:flex;align-items:center;gap:10px}
  .kaart > h2 .nr{width:22px;height:22px;border-radius:50%;background:var(--oranje);color:#fff;font-size:12px;
    display:grid;place-items:center;flex:none}
  .kaart .body{padding:18px}
  .hint{color:var(--ink-3);font-size:13px;margin:0 0 14px}

  /* ---------- formulier ---------- */
  .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .grid.drie{grid-template-columns:repeat(3,minmax(0,1fr))}
  .veld{display:flex;flex-direction:column;gap:5px;min-width:0}
  .veld.vol{grid-column:1/-1}
  label{font-size:13px;font-weight:600;color:var(--ink-2)}
  label .ster{color:var(--fout)}
  input[type=text],input[type=email],input[type=tel],input[type=date],input[type=number],select,textarea{
    font:inherit;padding:9px 11px;border:1px solid var(--lijn);border-radius:8px;background:#fff;color:var(--ink);width:100%}
  input:focus,select:focus,textarea:focus{outline:2px solid #FFD9A6;outline-offset:0;border-color:var(--oranje)}
  input.fout,select.fout{border-color:var(--fout);background:var(--fout-bg)}
  textarea{resize:vertical;min-height:70px}
  .keuze{display:flex;gap:8px;flex-wrap:wrap}
  .keuze label{display:flex;align-items:center;gap:7px;border:1px solid var(--lijn);border-radius:8px;
    padding:8px 12px;cursor:pointer;font-weight:500;color:var(--ink);background:#fff}
  .keuze label:has(input:checked){border-color:var(--oranje);background:#FFF6EC}
  .keuze input{accent-color:var(--oranje)}
  .subdomein{display:flex;align-items:center;gap:0}
  .subdomein input{border-radius:8px 0 0 8px}
  .subdomein .achtervoegsel{border:1px solid var(--lijn);border-left:0;border-radius:0 8px 8px 0;padding:9px 11px;
    background:var(--vlak);color:var(--ink-2);white-space:nowrap;font-size:14px}
  .waarschuwing{background:var(--warn-bg);border:1px solid #EBD3A8;color:#7A4A00;border-radius:8px;padding:10px 12px;font-size:13px;margin-top:10px}
  .veldfout{color:var(--fout);font-size:12px}

  /* gebruikers */
  .gebruiker{border:1px solid var(--lijn);border-radius:8px;padding:14px;margin-bottom:10px;background:#FCFDFE;position:relative}
  .gebruiker .kop{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
  .gebruiker .kop span{font-size:12px;font-weight:700;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em}

  /* hardware */
  table.hw{width:100%;border-collapse:collapse}
  table.hw th{text-align:left;font-size:12px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.04em;
    padding:0 8px 8px;font-weight:700;border-bottom:1px solid var(--lijn)}
  table.hw td{padding:8px;border-bottom:1px solid var(--lijn);vertical-align:middle}
  table.hw tr:last-child td{border-bottom:0}
  table.hw .code{color:var(--ink-3);font-variant-numeric:tabular-nums;font-size:13px;white-space:nowrap}
  table.hw input[type=number]{width:82px;text-align:center}
  table.hw td:last-child{text-align:right;width:110px}
  table.hw tr.aan{background:#FFF9F2}

  /* ---------- knoppen ---------- */
  .knop{font:inherit;font-weight:600;border-radius:8px;padding:10px 16px;border:1px solid var(--lijn);
    background:#fff;color:var(--ink);cursor:pointer;display:inline-flex;align-items:center;gap:8px}
  .knop:hover{background:var(--vlak)}
  .knop.primair{background:var(--oranje);border-color:var(--oranje);color:#fff}
  .knop.primair:hover{background:var(--oranje-dk);border-color:var(--oranje-dk)}
  .knop.gevaar{color:var(--fout);border-color:#F0C6C3}
  .knop.gevaar:hover{background:var(--fout-bg)}
  .knop.klein{padding:6px 11px;font-size:13px}
  .knop:disabled{opacity:.5;cursor:not-allowed}
  .acties{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--lijn)}
  .acties .rechts{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

  /* ---------- mailpreview ---------- */
  .mailvoorbeeld{background:#0E1418;color:#D7DEE4;border-radius:8px;padding:14px 16px;font:13px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace;
    white-space:pre-wrap;word-break:break-word;max-height:340px;overflow:auto}
  .mailkop{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;font-size:13px;color:var(--ink-2)}
  .mailkop b{color:var(--ink)}

  /* ---------- projecten ---------- */
  .filterbalk{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .filterbalk input[type=text]{max-width:280px}
  .filterbalk select{max-width:220px}
  .proj{border:1px solid var(--lijn);border-radius:10px;padding:14px 16px;margin-bottom:10px;background:#fff}
  .proj .kop{display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap}
  .proj .titel{font-weight:700;letter-spacing:-.01em}
  .proj .ref{font:12px/1 ui-monospace,Consolas,monospace;color:var(--ink-3);background:var(--vlak);
    border:1px solid var(--lijn);border-radius:6px;padding:4px 7px}
  .proj .meta{color:var(--ink-2);font-size:13px;margin-top:6px;display:flex;gap:16px;flex-wrap:wrap}
  .proj .meta span{white-space:nowrap}
  .proj .knoppen{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px dashed var(--lijn)}
  .status{font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;white-space:nowrap}
  .s-aangevraagd{background:var(--warn-bg);color:var(--warn)}
  .s-omgeving_gereed{background:var(--security-bg);color:var(--security)}
  .s-getest{background:#E7F0FB;color:#1D5FA8}
  .s-actief{background:var(--industrial-bg);color:var(--industrial)}
  .s-afgemeld{background:#F1E7FA;color:#6B3FA0}
  .s-beeindigd{background:var(--vlak);color:var(--ink-3)}
  .leeg{text-align:center;color:var(--ink-3);padding:34px 10px;font-size:14px}

  /* ---------- log ---------- */
  table.log{width:100%;border-collapse:collapse;font-size:13px}
  table.log th{text-align:left;padding:8px 10px;border-bottom:1px solid var(--lijn);color:var(--ink-3);
    font-size:12px;text-transform:uppercase;letter-spacing:.04em}
  table.log td{padding:9px 10px;border-bottom:1px solid var(--lijn);vertical-align:top}
  table.log tr:hover td{background:#FCFDFE}
  table.log .tijd{white-space:nowrap;color:var(--ink-3);font-variant-numeric:tabular-nums}
  table.log .ref{font-family:ui-monospace,Consolas,monospace;white-space:nowrap}
  .logwrap{overflow-x:auto}

  /* ---------- procedure ---------- */
  .legenda{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;font-size:13px}
  .legenda i{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:6px;vertical-align:-1px}
  .stroom{display:flex;flex-direction:column;gap:0}
  .stap{display:grid;grid-template-columns:130px 1fr;gap:16px;align-items:start;padding:0}
  .stap .lane{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding-top:14px;text-align:right}
  .lane.ind{color:var(--industrial)}
  .lane.sec{color:var(--security)}
  .stap .doos{border-radius:10px;padding:12px 14px;margin:5px 0;border:1px solid transparent}
  .doos.ind{background:var(--industrial-bg);border-color:#BFE0D3}
  .doos.sec{background:var(--security-bg);border-color:#CFD2EE}
  .doos.uitz{background:var(--fout-bg);border-color:#F0C6C3}
  .doos b{display:block;font-size:14px}
  .doos small{color:var(--ink-2);display:block;margin-top:3px}
  .pijl{grid-column:2;color:var(--lijn);font-size:18px;line-height:1;padding-left:18px}
  .contactkaart{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:6px}
  .contactkaart div{border:1px solid var(--lijn);border-radius:10px;padding:14px}
  .contactkaart b{display:block;margin-bottom:5px}
  .contactkaart a{color:var(--oranje-dk)}

  /* ---------- modaal ---------- */
  .overlay{position:fixed;inset:0;background:rgba(16,24,40,.45);display:grid;place-items:center;padding:16px;z-index:100}
  .modaal{background:#fff;border-radius:12px;max-width:560px;width:100%;box-shadow:0 20px 50px rgba(16,24,40,.25);max-height:90vh;overflow:auto}
  .modaal h3{margin:0;padding:16px 18px;border-bottom:1px solid var(--lijn);font-size:16px}
  .modaal .inhoud{padding:18px;display:flex;flex-direction:column;gap:14px}
  .modaal .voet{padding:14px 18px;border-top:1px solid var(--lijn);display:flex;gap:10px;justify-content:flex-end}

  /* ---------- toast ---------- */
  #toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:var(--ink);color:#fff;
    padding:11px 18px;border-radius:8px;font-size:14px;box-shadow:var(--schaduw);z-index:200;max-width:90vw;text-align:center}
  #toast.fout{background:var(--fout)}

  .verborgen{display:none !important}

  @media (max-width:720px){
    .grid,.grid.drie,.contactkaart{grid-template-columns:1fr}
    .stap{grid-template-columns:1fr}
    .stap .lane{text-align:left;padding-top:8px}
    .pijl{grid-column:1;padding-left:6px}
    .hd .sub{border-left:0;padding-left:0;flex-basis:100%}
  }
  @media print{
    header.app,nav.tabs,.acties,.knoppen,.filterbalk{display:none !important}
    body{background:#fff}
    .kaart{box-shadow:none;break-inside:avoid}
  }
</style>
<style>
.klant-suggesties{position:absolute;z-index:50;left:0;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #cfd6dc;border-radius:10px;box-shadow:0 8px 24px rgba(15,24,32,.15);max-height:320px;overflow:auto}
.klant-suggesties button{display:block;width:100%;text-align:left;border:0;background:none;padding:9px 12px;cursor:pointer;font:inherit;border-bottom:1px solid #eef1f4}
.klant-suggesties button:last-child{border-bottom:0}
.klant-suggesties button:hover,.klant-suggesties button.actief{background:#fbeadf}
.klant-suggesties .nr{font-family:ui-monospace,Menlo,monospace;font-size:.85em;color:#6e7d89;margin-right:8px}
.klant-suggesties .sub{display:block;font-size:.85em;color:#6e7d89}
#klantzoekVeld{position:relative}
.testbadge{display:inline-block;background:#8a5a00;color:#fff;font-size:11px;font-weight:700;letter-spacing:.06em;padding:2px 7px;border-radius:6px;margin-left:6px;vertical-align:middle}
body.testmodus header.app{box-shadow:inset 0 -4px 0 #d9a73c}
</style>
</head>
<body>

<header class="app">
  <div class="hd">
    <div class="logo"><img src="/images/boels-industrial-logo.jpg" alt="Boels Industrial" style="height:36px;width:auto;border-radius:6px;display:block"> Go-Assets aanvraagtool</div>
    <div class="sub">Industrial &rarr; Site Security</div>
    <div class="rechts">
      <span class="pill user" id="pillGebruiker" title="Klik om te wijzigen" style="cursor:pointer"></span>
      <span class="pill" id="pillVerbinding">verbinden&hellip;</span>
      <span class="pill" id="pillTest" title="Aan = TEST-nummers, geen Outlook (mail wordt getoond), testaanvragen zijn herkenbaar en in één keer op te ruimen" style="cursor:pointer">Testmodus: uit</span>
      <button type="button" class="knop klein gevaar" id="btnOpruimTest" hidden title="Alle testaanvragen en hun logregels verwijderen">Testaanvragen opruimen</button>
    </div>
  </div>
  <nav class="tabs" role="tablist">
    <button role="tab" data-tab="aanvraag" aria-selected="true">Nieuwe aanvraag</button>
    <button role="tab" data-tab="projecten" aria-selected="false">Lopende projecten<span class="telbadge" id="telProjecten">0</span></button>
    <button role="tab" data-tab="log" aria-selected="false">Log<span class="telbadge" id="telLog">0</span></button>
    <button role="tab" data-tab="procedure" aria-selected="false">Procedure</button>
  </nav>
</header>

<div class="wrap">

<!-- ======================= TAB: AANVRAAG ======================= -->
<section id="tab-aanvraag">

  <div class="kaart">
    <h2><span class="nr">1</span> Projectgegevens</h2>
    <div class="body">
      <div class="grid">
        <div class="veld"><label for="f_land">Land <span class="ster">*</span></label>
          <select id="f_land" data-verplicht>
            <option value="Nederland" selected>Nederland</option>
            <option value="België">België</option>
            <option value="Luxemburg">Luxemburg</option>
            <option value="Duitsland">Duitsland</option>
            <option value="Frankrijk">Frankrijk</option>
            <option value="Overig">Overig</option>
          </select></div>
        <div class="veld"><label for="f_plaats">Plaats <span class="ster">*</span></label>
          <input type="text" id="f_plaats" data-verplicht placeholder="bijv. Rotterdam Europoort"></div>
        <div class="veld"><label for="f_gewenst">Gewenste datum omgeving beschikbaar <span class="ster">*</span></label>
          <input type="date" id="f_gewenst" data-verplicht></div>
        <div class="veld"><label for="f_start">Startdatum project (bij klant) <span class="ster">*</span></label>
          <input type="date" id="f_start" data-verplicht></div>
        <div class="veld"><label for="f_eind">Einddatum (inschatting)</label>
          <input type="date" id="f_eind"></div>
        <div class="veld"><label for="f_terugkerend">Terugkerende klant / project</label>
          <div class="keuze"><label><input type="checkbox" id="f_terugkerend"> Ja, terugkerend</label></div></div>
        <div class="veld vol"><label for="f_overig">Overig</label>
          <textarea id="f_overig" placeholder="Bijzonderheden, afwijkende afspraken, locatiegegevens&hellip;"></textarea></div>
      </div>
      <div id="w_datum" class="waarschuwing verborgen">De omgeving moet minimaal 2 werkdagen v&oacute;&oacute;r de startdatum beschikbaar zijn, zodat er getest kan worden (zie procedure).</div>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">2</span> Opzet omgeving</h2>
    <div class="body">
      <p class="hint">Kies zelf een subdomein of laat Go-Workforce dit invullen.</p>
      <div class="keuze" style="margin-bottom:14px">
        <label><input type="radio" name="subkeuze" value="eigen" checked> Eigen keuze</label>
        <label><input type="radio" name="subkeuze" value="geen"> Geen voorkeur (vrij in te vullen door Go-Workforce)</label>
      </div>
      <div class="veld" id="subveld">
        <label for="f_subdomein">Gewenst subdomein</label>
        <div class="subdomein">
          <input type="text" id="f_subdomein" placeholder="bijv. boels-shell-moerdijk">
          <span class="achtervoegsel">.go-workforce.com</span>
        </div>
      </div>
      <div class="veld" style="margin-top:16px">
        <label>Data verwijderen 30 dagen na project? <span class="ster">*</span></label>
        <div class="keuze">
          <label><input type="radio" name="dataverw" value="JA" checked> JA (standaard)</label>
          <label><input type="radio" name="dataverw" value="NEE"> NEE</label>
        </div>
      </div>
      <div id="w_data" class="waarschuwing verborgen">Bij NEE moet je eerst contact opnemen met Site Security om hier afspraken over te maken (<a href="mailto:sitesecurity@boels.com">sitesecurity@boels.com</a>).</div>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">3</span> Contactpersoon bedrijf</h2>
    <div class="body">
      <p class="hint">Dit is de hoofdaannemer (client entity).</p>
      <div class="grid">
        <div class="veld vol" id="klantzoekVeld">
          <label for="f_klantzoek">Klant zoeken in Boels CORE</label>
          <input type="text" id="f_klantzoek" autocomplete="off" placeholder="Typ klantnaam of klantnummer&hellip;">
          <div id="klantSuggesties" class="klant-suggesties" hidden></div>
          <p class="hint" id="klantGekozen" style="margin:6px 0 0">Kies een klant uit het CORE-klantenbestand; bedrijfsnaam, Insphire klantnummer, BTW-nummer en plaats worden dan ingevuld. Handmatig invullen kan ook.</p>
        </div>
        <div class="veld"><label for="f_bedrijf">Bedrijfsnaam <span class="ster">*</span></label>
          <input type="text" id="f_bedrijf" data-verplicht></div>
        <div class="veld"><label for="f_klantnummer">Insphire klantnummer</label>
          <input type="text" id="f_klantnummer" placeholder="Wordt ingevuld bij keuze uit CORE"></div>
        <div class="veld"><label for="f_btw">BTW-nummer</label>
          <input type="text" id="f_btw" placeholder="NL123456789B01 (optioneel)"></div>
        <div class="veld"><label for="f_cp_voor">Voornaam <span class="ster">*</span></label>
          <input type="text" id="f_cp_voor" data-verplicht></div>
        <div class="veld"><label for="f_cp_achter">Achternaam <span class="ster">*</span></label>
          <input type="text" id="f_cp_achter" data-verplicht></div>
        <div class="veld"><label for="f_cp_mail">E-mailadres <span class="ster">*</span></label>
          <input type="email" id="f_cp_mail" data-verplicht></div>
        <div class="veld"><label for="f_cp_tel">Telefoonnummer <span class="ster">*</span></label>
          <input type="tel" id="f_cp_tel" data-verplicht></div>
        <div class="veld vol"><label for="f_cp_functie">Functie</label>
          <input type="text" id="f_cp_functie"></div>
      </div>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">4</span> Gebruikers Go-Assets</h2>
    <div class="body">
      <p class="hint">Voeg de gebruiker(s) toe die de software gaat bedienen. Zij ontvangen de inloggegevens van Site Security.</p>
      <div id="gebruikers"></div>
      <button type="button" class="knop klein" id="btnGebruiker">+ Gebruiker toevoegen</button>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">5</span> Insphire</h2>
    <div class="body">
      <div class="grid">
        <div class="veld"><label>Koppeling naar Insphire?</label>
          <div class="keuze">
            <label><input type="radio" name="insphire" value="JA"> Ja</label>
            <label><input type="radio" name="insphire" value="NEE" checked> Nee</label>
          </div></div>
        <div class="veld"><label for="f_projectcode">Insphire projectcode</label>
          <input type="text" id="f_projectcode" placeholder="alleen invullen bij koppeling"></div>
        <div class="veld vol"><label for="f_contract">Contractnummer <span class="ster">*</span></label>
          <input type="text" id="f_contract" data-verplicht placeholder="contractnummer uit Insphire">
          <span class="hint" style="margin:0">Volgens de procedure maak je eerst het Insphire-project aan; het contractnummer gaat mee met deze aanvraag.</span></div>
      </div>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">6</span> Hardware</h2>
    <div class="body">
      <p class="hint">Vink aan wat Site Security gereed moet maken en geef het aantal op.</p>
      <table class="hw"><thead><tr><th style="width:44px">&nbsp;</th><th>Artikel</th><th style="width:110px">Code</th><th>Aantal</th></tr></thead>
        <tbody id="hardware"></tbody></table>
    </div>
  </div>

  <div class="kaart">
    <h2><span class="nr">7</span> Aanvraag versturen</h2>
    <div class="body">
      <div class="mailkop">
        <span>Aan: <b>sitesecurity@boels.com</b></span>
        <span>Onderwerp: <b id="mailOnderwerp">&mdash;</b></span>
      </div>
      <div class="mailvoorbeeld" id="mailTekst">Vul het formulier in om de mail samen te stellen&hellip;</div>
      <div id="fouten" class="waarschuwing verborgen"></div>
      <div class="acties">
        <button type="button" class="knop primair" id="btnVersturen">Registreren &amp; mail opstellen</button>
        <button type="button" class="knop" id="btnKopieer">Mailtekst kopi&euml;ren</button>
        <div class="rechts">
          <button type="button" class="knop klein" id="btnConcept">Concept opslaan</button>
          <button type="button" class="knop klein" id="btnLeeg">Formulier legen</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======================= TAB: PROJECTEN ======================= -->
<section id="tab-projecten" class="verborgen">
  <div class="kaart">
    <h2>Lopende projecten</h2>
    <div class="body">
      <div class="filterbalk">
        <input type="text" id="zoek" placeholder="Zoek op klant, plaats, referentie of aanvrager&hellip;">
        <select id="filterStatus">
          <option value="lopend">Alleen lopend</option>
          <option value="">Alle statussen</option>
          <option value="aangevraagd">Aangevraagd</option>
          <option value="omgeving_gereed">Omgeving gereed</option>
          <option value="getest">Getest &amp; akkoord</option>
          <option value="actief">Actief</option>
          <option value="afgemeld">Afgemeld</option>
          <option value="beeindigd">Be&euml;indigd</option>
        </select>
        <button type="button" class="knop klein" id="btnVernieuw">Vernieuwen</button>
        <button type="button" class="knop klein" id="btnExportProj">Export CSV</button>
      </div>
      <div id="projectenlijst"></div>
    </div>
  </div>
</section>

<!-- ======================= TAB: LOG ======================= -->
<section id="tab-log" class="verborgen">
  <div class="kaart">
    <h2>Log</h2>
    <div class="body">
      <p class="hint">Volledige registratie van wie wat heeft aangevraagd, gewijzigd of afgemeld.</p>
      <div class="filterbalk">
        <input type="text" id="zoekLog" placeholder="Zoek in log (referentie, klant, wie, actie)&hellip;">
        <button type="button" class="knop klein" id="btnExportLog">Export CSV</button>
      </div>
      <div class="logwrap">
        <table class="log"><thead><tr>
          <th style="width:150px">Tijdstip</th><th style="width:110px">Referentie</th><th style="width:170px">Klant</th>
          <th style="width:160px">Wie</th><th style="width:150px">Actie</th><th>Details</th>
        </tr></thead><tbody id="logtabel"></tbody></table>
      </div>
    </div>
  </div>
</section>

<!-- ======================= TAB: PROCEDURE ======================= -->
<section id="tab-procedure" class="verborgen">
  <div class="kaart">
    <h2>Procedure Go-Assets aanvraag</h2>
    <div class="body">
      <div class="legenda">
        <span><i style="background:var(--industrial)"></i>Industrial</span>
        <span><i style="background:var(--security)"></i>Site Security</span>
      </div>
      <div class="stroom" id="stroom"></div>
    </div>
  </div>
  <div class="kaart">
    <h2>Contact</h2>
    <div class="body">
      <div class="contactkaart">
        <div><b>Site Security (Boels)</b>
          Aanvragen, wijzigingen en afmelden van omgevingen.<br>
          <a href="mailto:sitesecurity@boels.com">sitesecurity@boels.com</a></div>
        <div><b>Support Go-Workforce / IQ-Pass</b>
          Testbevindingen en 2e-lijns storingen.<br>
          <a href="mailto:support@iq-pass.com">support@iq-pass.com</a><br>
          <a href="tel:+31888008140">088 800 8140</a></div>
      </div>
    </div>
  </div>
</section>

</div><!-- /wrap -->

<div id="modaalhouder"></div>
<div id="toast" class="verborgen"></div>

<script>
"use strict";
/* =====================================================================
   Go-Assets aanvraagtool — Boels Industrial
   Frontend werkt met api.php (MySQL). Zonder bereikbare API valt de tool
   automatisch terug op localStorage ("demo modus").
   ===================================================================== */

@endverbatim
/* CORE-koppeling: ingelogde gebruiker = aanvrager; backend + CSRF via CORE */
window.CORE_USER = {!! json_encode(['naam' => auth()->user()->name, 'email' => auth()->user()->email], JSON_UNESCAPED_UNICODE) !!};
window.CORE_CSRF = "{{ csrf_token() }}";
var CONFIG = {
  api: "{{ route('tools.go-assets.api') }}",
@verbatim
  mailSiteSecurity: "sitesecurity@boels.com",
  mailSupport: "support@iq-pass.com",
  telSupport: "088 8008140",
  suffix: ".go-workforce.com"
};

var HARDWARE = [
  { code:"15910",  naam:"Laptop incl. voeding (NL)" },
  { code:"51901",  naam:"Toetsenbord QWERTY" },
  { code:"51900",  naam:"USB Muis" },
  { code:"51902",  naam:"Computermonitor 24 inch" },
  { code:"15933",  naam:"Router 4G" },
  { code:"51910",  naam:"Simkaart 4G incl. data" },
  { code:"800822", naam:"Labelwriter DYMO 450 Turbo" },
  { code:"801553", naam:"Barcodescanner Zebra DS3678" }
];

var STATUSSEN = {
  aangevraagd:      { label:"Aangevraagd",        volgende:"omgeving_gereed", knop:"Omgeving ontvangen" },
  omgeving_gereed:  { label:"Omgeving gereed",    volgende:"getest",          knop:"Test akkoord" },
  getest:           { label:"Getest & akkoord",   volgende:"actief",          knop:"Nu al starten" },
  actief:           { label:"Actief",             volgende:"afgemeld",        knop:"Project afmelden" },
  afgemeld:         { label:"Afgemeld",           volgende:"beeindigd",       knop:"Omgeving uit (bevestigd)" },
  beeindigd:        { label:"Beëindigd",     volgende:null,              knop:null }
};

var PROCES = [
  { lane:"ind", titel:"Aanvraag", sub:"Behoefte vanuit project of klant." },
  { lane:"ind", titel:"Aanvraagformulier invullen", sub:"Deze tool, tabblad 'Nieuwe aanvraag'." },
  { lane:"ind", titel:"Insphire-project aanmaken", sub:"Contractnummer vastleggen." },
  { lane:"ind", titel:"Aanvraagformulier & contractnummer doorgeven", sub:"Mail naar sitesecurity@boels.com." },
  { lane:"sec", titel:"Trellokaart aanmaken", sub:"Site Security registreert de aanvraag." },
  { lane:"sec", titel:"Go-Assets omgeving aanmaken & hardware gereed maken", sub:"Volgens opgegeven opzet en hardwarelijst." },
  { lane:"sec", titel:"Go-Assets gegevens doorgeven aan gebruiker", sub:"Inloggegevens naar de opgegeven gebruiker(s)." },
  { lane:"ind", titel:"Testen omgeving", sub:"Minimaal 2 dagen vóór de startdatum." },
  { lane:"sec", titel:"Test niet akkoord → contact opnemen met Support", sub:"support@iq-pass.com — 088 8008140", uitz:true },
  { lane:"ind", titel:"Test akkoord", sub:"Omgeving is gereed voor gebruik." },
  { lane:"ind", titel:"Start project", sub:"Go-Assets in gebruik bij de klant." },
  { lane:"ind", titel:"1e-lijns storingen via Industrial", sub:"Eerste opvang door Industrial zelf." },
  { lane:"sec", titel:"2e-lijns storingen → contact opnemen met Support", sub:"support@iq-pass.com — 088 8008140", uitz:true },
  { lane:"ind", titel:"Einde project doorgeven", sub:"Mail naar sitesecurity@boels.com." },
  { lane:"sec", titel:"Go-Assets omgeving uitzetten", sub:"Data verwijderd volgens gemaakte afspraak." }
];

/* ------------------------------ helpers ------------------------------ */
var $  = function(s,r){ return (r||document).querySelector(s); };
var $$ = function(s,r){ return Array.prototype.slice.call((r||document).querySelectorAll(s)); };
function esc(s){ return String(s==null?"":s).replace(/[&<>"']/g, function(c){
  return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]; }); }
function toast(m,fout){
  var t=$("#toast"); t.textContent=m; t.className=fout?"fout":""; t.classList.remove("verborgen");
  clearTimeout(toast._t); toast._t=setTimeout(function(){ t.classList.add("verborgen"); },3200);
}
function nu(){ return new Date().toISOString(); }
function datumNL(s){
  if(!s) return "—";
  var d=new Date(s); if(isNaN(d)) return s;
  return d.toLocaleDateString("nl-NL",{day:"2-digit",month:"2-digit",year:"numeric"});
}
function tijdNL(s){
  if(!s) return "";
  var d=new Date(s); if(isNaN(d)) return s;
  return d.toLocaleString("nl-NL",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit"});
}
function werkdagenTussen(a,b){
  var d1=new Date(a), d2=new Date(b); if(isNaN(d1)||isNaN(d2)) return null;
  var n=0, d=new Date(d1);
  while(d<d2){ d.setDate(d.getDate()+1); var w=d.getDay(); if(w!==0&&w!==6) n++; }
  return n;
}

/* --------------------------- gebruiker (CORE) ------------------------- */
/* CORE-koppeling — twee manieren, de eerste die iets oplevert wint:
   1) Zet in de CORE-pagina waar deze tool in staat, boven dit bestand,
      een klein script-blok met:
          window.CORE_USER = { naam: "...", email: "..." };
      (in PHP bijvoorbeeld gevuld met de ingelogde gebruiker)
   2) Of laat api.php?action=me de ingelogde gebruiker teruggeven
      (zie de functie huidige_gebruiker() in api.php).
   Lukt geen van beide, dan vraagt de tool zelf om naam + e-mail. */
var GEBRUIKER = { naam:"", email:"" };

function bepaalGebruiker(){
  if (window.CORE_USER && window.CORE_USER.naam){
    GEBRUIKER = { naam: window.CORE_USER.naam, email: window.CORE_USER.email || "" };
    return Promise.resolve(true);
  }
  return fetch(CONFIG.api + "?action=me", { credentials:"same-origin" })
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(j){
      if (j && j.ok && j.gebruiker && j.gebruiker.naam){
        GEBRUIKER = { naam:j.gebruiker.naam, email:j.gebruiker.email||"" };
        return true;
      }
      return false;
    })
    .catch(function(){ return false; })
    .then(function(gevonden){
      if(!gevonden){
        try{
          var opg = JSON.parse(localStorage.getItem("ga_gebruiker")||"null");
          if(opg && opg.naam){ GEBRUIKER = opg; gevonden = true; }
        }catch(e){}
      }
      return gevonden;
    });
}

function toonGebruiker(){
  var p=$("#pillGebruiker");
  p.innerHTML = GEBRUIKER.naam ? ("Aanvrager: <b>"+esc(GEBRUIKER.naam)+"</b>") : "<b>Naam invullen</b>";
}

function vraagGebruiker(){
  modaal("Wie ben je?", ''
    + '<div class="veld"><label for="m_naam">Naam <span class="ster">*</span></label>'
    + '<input type="text" id="m_naam" value="'+esc(GEBRUIKER.naam)+'"></div>'
    + '<div class="veld"><label for="m_mail">E-mailadres</label>'
    + '<input type="email" id="m_mail" value="'+esc(GEBRUIKER.email)+'"></div>'
    + '<p class="hint" style="margin:0">Wordt vastgelegd in de log bij elke aanvraag en afmelding.</p>',
    "Opslaan", function(){
      var n=$("#m_naam").value.trim();
      if(!n){ toast("Vul je naam in.",true); return false; }
      GEBRUIKER={naam:n,email:$("#m_mail").value.trim()};
      try{ localStorage.setItem("ga_gebruiker",JSON.stringify(GEBRUIKER)); }catch(e){}
      toonGebruiker(); bouwMail();
      return true;
    });
}

/* ------------------------------- opslag ------------------------------- */
var DEMO = false;         // true = geen backend bereikbaar, localStorage gebruiken
var PROJECTEN = [];
var LOGREGELS = [];

function lokaalLees(sleutel,standaard){
  try{ return JSON.parse(localStorage.getItem(sleutel)) || standaard; }catch(e){ return standaard; }
}
function lokaalSchrijf(sleutel,waarde){
  try{ localStorage.setItem(sleutel,JSON.stringify(waarde)); }catch(e){}
}

function api(actie, data){
  if (DEMO) return Promise.reject(new Error("demo"));
  var opties = { credentials:"same-origin", headers:{"Content-Type":"application/json", "X-CSRF-TOKEN":(window.CORE_CSRF||""), "X-Requested-With":"XMLHttpRequest"} };
  if (data){ opties.method="POST"; opties.body=JSON.stringify(data); }
  return fetch(CONFIG.api + "?action=" + encodeURIComponent(actie), opties)
    .then(function(r){
      if(!r.ok) throw new Error("HTTP "+r.status);
      return r.json();
    })
    .then(function(j){
      if(!j || j.ok===false) throw new Error((j&&j.fout)||"onbekende fout");
      return j;
    });
}

function zetVerbinding(){
  var p=$("#pillVerbinding");
  p.className = "pill " + (DEMO?"demo":"live");
  p.textContent = DEMO ? "Demo modus (lokaal)" : "Verbonden met CORE";
  p.title = DEMO
    ? "api.php is niet bereikbaar. Aanvragen en log worden alleen in deze browser bewaard."
    : "Aanvragen en log staan in de gedeelde database.";
}

/* --------------------------- testmodus --------------------------- */
var TESTMODUS = false;
try { TESTMODUS = localStorage.getItem("ga_testmodus") === "1"; } catch(e){}

function zetTestmodus(aan){
  TESTMODUS = !!aan;
  try { localStorage.setItem("ga_testmodus", TESTMODUS ? "1" : "0"); } catch(e){}
  var p=$("#pillTest");
  if(p){ p.className = "pill " + (TESTMODUS ? "demo" : ""); p.textContent = "Testmodus: " + (TESTMODUS ? "AAN" : "uit"); }
  var b=$("#btnOpruimTest"); if(b) b.hidden = !TESTMODUS;
  document.body.classList.toggle("testmodus", TESTMODUS);
}

function opruimTest(){
  var n = PROJECTEN.filter(function(p){ return p.test; }).length;
  if(!n){ toast("Er zijn geen testaanvragen."); return; }
  modaal("Testaanvragen opruimen",
    "<p>Alle <b>"+n+"</b> testaanvra"+(n===1?"ag":"gen")+" (TEST-nummers) en hun logregels worden definitief verwijderd. Echte aanvragen blijven staan.</p>",
    "Verwijderen", function(){
      api("opruim_test", {}).then(function(j){
        toast((j.verwijderd||0)+" testaanvra"+(j.verwijderd===1?"ag":"gen")+" opgeruimd.");
              laadAlles();
      }).catch(function(e){ toast("Opruimen mislukt: "+e.message, true); });
      return true;
    });
}

function laadAlles(){
  return api("list").then(function(j){
    DEMO=false;
    PROJECTEN = j.projecten || [];
    LOGREGELS = j.log || [];
  }).catch(function(){
    DEMO=true;
    PROJECTEN = lokaalLees("ga_projecten",[]);
    LOGREGELS = lokaalLees("ga_log",[]);
  }).then(function(){
    zetVerbinding(); tekenProjecten(); tekenLog();
  });
}

function volgnummer(){
  var jaar=new Date().getFullYear(), max=0;
  PROJECTEN.forEach(function(p){
    var m=/^GA-(\d{4})-(\d+)$/.exec(p.ref||"");
    if(m && +m[1]===jaar) max=Math.max(max,+m[2]);
  });
  return "GA-"+jaar+"-"+String(max+1).padStart(3,"0");
}

function bewaarProject(project){
  return api("create",project).then(function(j){
    return j.project || project;
  }).catch(function(){
    DEMO=true; zetVerbinding();
    PROJECTEN.unshift(project); lokaalSchrijf("ga_projecten",PROJECTEN);
    return project;
  });
}

function bewaarStatus(id,status,extra){
  var payload = Object.assign({ id:id, status:status, door:GEBRUIKER.naam, door_email:GEBRUIKER.email }, extra||{});
  return api("status",payload).catch(function(){
    DEMO=true; zetVerbinding();
    PROJECTEN.forEach(function(p){
      if(p.id===id){
        p.status=status;
        if(extra && extra.afmeld_reden) p.afmeld_reden=extra.afmeld_reden;
        if(status==="afgemeld") p.afgemeld_op=nu();
        p.gewijzigd_op=nu();
      }
    });
    lokaalSchrijf("ga_projecten",PROJECTEN);
  });
}

function schrijfLog(ref,actie,details,project_id){
  var regel = { id:"L"+Date.now()+Math.random().toString(16).slice(2,6), project_id:project_id||null,
                ref:ref, actie:actie, details:details||"",
                door:GEBRUIKER.naam||"onbekend", door_email:GEBRUIKER.email||"", op:nu() };
  LOGREGELS.unshift(regel);
  tekenLog();
  return api("log",regel).catch(function(){
    DEMO=true; zetVerbinding(); lokaalSchrijf("ga_log",LOGREGELS);
  });
}

/* ------------------------------ formulier ------------------------------ */
function bouwHardware(){
  var tb=$("#hardware");
  tb.innerHTML = HARDWARE.map(function(h,i){
    return '<tr data-rij="'+i+'">'
      + '<td><input type="checkbox" class="hw-aan" data-i="'+i+'"></td>'
      + '<td>'+esc(h.naam)+'</td>'
      + '<td class="code">'+esc(h.code)+'</td>'
      + '<td><input type="number" class="hw-aantal" data-i="'+i+'" min="0" step="1" value="0" disabled></td>'
      + '</tr>';
  }).join("");
}

function bouwGebruikerRij(index){
  return '<div class="gebruiker" data-g="'+index+'">'
    + '<div class="kop"><span>Gebruiker '+(index+1)+'</span>'
    + (index>0?'<button type="button" class="knop klein gevaar g-weg" data-g="'+index+'">Verwijderen</button>':'')
    + '</div><div class="grid">'
    + '<div class="veld vol"><label>Bedrijfsnaam (indien niet Boels)</label><input type="text" class="g-bedrijf"></div>'
    + '<div class="veld"><label>Voornaam <span class="ster">*</span></label><input type="text" class="g-voor" data-verplicht></div>'
    + '<div class="veld"><label>Achternaam <span class="ster">*</span></label><input type="text" class="g-achter" data-verplicht></div>'
    + '<div class="veld vol"><label>E-mailadres <span class="ster">*</span></label><input type="email" class="g-mail" data-verplicht></div>'
    + '</div></div>';
}

function voegGebruikerToe(){
  var n=$$("#gebruikers .gebruiker").length;
  $("#gebruikers").insertAdjacentHTML("beforeend", bouwGebruikerRij(n));
  bouwMail();
}

function hernummerGebruikers(){
  $$("#gebruikers .gebruiker").forEach(function(el,i){
    el.dataset.g=i;
    $(".kop span",el).textContent="Gebruiker "+(i+1);
    var weg=$(".g-weg",el); if(weg) weg.dataset.g=i;
  });
}

function leesFormulier(){
  var subkeuze = ($('input[name="subkeuze"]:checked')||{}).value || "eigen";
  return {
    land: $("#f_land").value,
    plaats: $("#f_plaats").value.trim(),
    gewenste_datum: $("#f_gewenst").value,
    startdatum: $("#f_start").value,
    einddatum: $("#f_eind").value,
    terugkerend: $("#f_terugkerend").checked,
    overig: $("#f_overig").value.trim(),
    subdomein_keuze: subkeuze,
    subdomein: subkeuze==="eigen" ? $("#f_subdomein").value.trim() : "",
    data_verwijderen: ($('input[name="dataverw"]:checked')||{}).value || "JA",
    klant: {
      bedrijfsnaam: $("#f_bedrijf").value.trim(),
      klantnummer: $("#f_klantnummer").value.trim() || (window.KLANT_CORE && window.KLANT_CORE.nummer) || "",
      btw: $("#f_btw").value.trim(),
      voornaam: $("#f_cp_voor").value.trim(),
      achternaam: $("#f_cp_achter").value.trim(),
      email: $("#f_cp_mail").value.trim(),
      telefoon: $("#f_cp_tel").value.trim(),
      functie: $("#f_cp_functie").value.trim()
    },
    gebruikers: $$("#gebruikers .gebruiker").map(function(el){
      return {
        bedrijfsnaam: $(".g-bedrijf",el).value.trim(),
        voornaam: $(".g-voor",el).value.trim(),
        achternaam: $(".g-achter",el).value.trim(),
        email: $(".g-mail",el).value.trim()
      };
    }),
    insphire: ($('input[name="insphire"]:checked')||{}).value || "NEE",
    projectcode: $("#f_projectcode").value.trim(),
    contractnummer: $("#f_contract").value.trim(),
    hardware: HARDWARE.map(function(h,i){
      var aan=$('.hw-aan[data-i="'+i+'"]').checked;
      var n=parseInt($('.hw-aantal[data-i="'+i+'"]').value,10)||0;
      return aan && n>0 ? { code:h.code, naam:h.naam, aantal:n } : null;
    }).filter(Boolean)
  };
}

function vulFormulier(d){
  if(!d) return;
  $("#f_land").value=d.land||"Nederland";
  $("#f_plaats").value=d.plaats||"";
  $("#f_gewenst").value=d.gewenste_datum||"";
  $("#f_start").value=d.startdatum||"";
  $("#f_eind").value=d.einddatum||"";
  $("#f_terugkerend").checked=!!d.terugkerend;
  $("#f_overig").value=d.overig||"";
  var r=$('input[name="subkeuze"][value="'+(d.subdomein_keuze||"eigen")+'"]'); if(r) r.checked=true;
  $("#f_subdomein").value=d.subdomein||"";
  var dv=$('input[name="dataverw"][value="'+(d.data_verwijderen||"JA")+'"]'); if(dv) dv.checked=true;
  var k=d.klant||{};
  $("#f_bedrijf").value=k.bedrijfsnaam||""; $("#f_btw").value=k.btw||"";
  $("#f_klantnummer").value=k.klantnummer||"";
  window.KLANT_CORE = k.klantnummer ? { nummer:k.klantnummer, naam:k.bedrijfsnaam||"" } : null;
  if (typeof toonKlantKeuze === "function") toonKlantKeuze();
  $("#f_cp_voor").value=k.voornaam||""; $("#f_cp_achter").value=k.achternaam||"";
  $("#f_cp_mail").value=k.email||""; $("#f_cp_tel").value=k.telefoon||"";
  $("#f_cp_functie").value=k.functie||"";
  var ins=$('input[name="insphire"][value="'+(d.insphire||"NEE")+'"]'); if(ins) ins.checked=true;
  $("#f_projectcode").value=d.projectcode||"";
  $("#f_contract").value=d.contractnummer||"";
  $("#gebruikers").innerHTML="";
  var lijst = (d.gebruikers&&d.gebruikers.length)?d.gebruikers:[{}];
  lijst.forEach(function(g,i){
    $("#gebruikers").insertAdjacentHTML("beforeend",bouwGebruikerRij(i));
    var el=$('#gebruikers .gebruiker[data-g="'+i+'"]');
    $(".g-bedrijf",el).value=g.bedrijfsnaam||""; $(".g-voor",el).value=g.voornaam||"";
    $(".g-achter",el).value=g.achternaam||""; $(".g-mail",el).value=g.email||"";
  });
  HARDWARE.forEach(function(h,i){
    var gevonden=(d.hardware||[]).filter(function(x){return x.code===h.code;})[0];
    $('.hw-aan[data-i="'+i+'"]').checked=!!gevonden;
    var inp=$('.hw-aantal[data-i="'+i+'"]');
    inp.disabled=!gevonden; inp.value=gevonden?gevonden.aantal:0;
    $('#hardware tr[data-rij="'+i+'"]').classList.toggle("aan",!!gevonden);
  });
  werkWaarschuwingenBij(); bouwMail();
}

function valideer(d){
  var fouten=[];
  $$("[data-verplicht]").forEach(function(el){ el.classList.remove("fout"); });
  function eis(sel,tekst){ var el=$(sel); if(el && !el.value.trim()){ el.classList.add("fout"); fouten.push(tekst); } }
  eis("#f_plaats","Plaats"); eis("#f_gewenst","Gewenste datum omgeving beschikbaar");
  eis("#f_start","Startdatum project"); eis("#f_bedrijf","Bedrijfsnaam klant"); eis("#f_cp_voor","Voornaam contactpersoon");
  eis("#f_cp_achter","Achternaam contactpersoon"); eis("#f_cp_mail","E-mailadres contactpersoon");
  eis("#f_cp_tel","Telefoonnummer contactpersoon"); eis("#f_contract","Contractnummer");
  if(d.klant.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(d.klant.email)){
    $("#f_cp_mail").classList.add("fout"); fouten.push("E-mailadres contactpersoon is ongeldig");
  }
  $$("#gebruikers .gebruiker").forEach(function(el,i){
    [[".g-voor","Voornaam"],[".g-achter","Achternaam"],[".g-mail","E-mailadres"]].forEach(function(p){
      var inp=$(p[0],el); inp.classList.remove("fout");
      if(!inp.value.trim()){ inp.classList.add("fout"); fouten.push(p[1]+" gebruiker "+(i+1)); }
    });
    var m=$(".g-mail",el);
    if(m.value.trim() && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(m.value.trim())){
      m.classList.add("fout"); fouten.push("E-mailadres gebruiker "+(i+1)+" is ongeldig");
    }
  });
  if(d.subdomein_keuze==="eigen" && !d.subdomein){
    $("#f_subdomein").classList.add("fout"); fouten.push("Gewenst subdomein (of kies 'geen voorkeur')");
  }
  if(d.insphire==="JA" && !d.projectcode){
    $("#f_projectcode").classList.add("fout"); fouten.push("Insphire projectcode");
  }
  if(!GEBRUIKER.naam) fouten.push("Je eigen naam als aanvrager (rechtsboven)");
  return fouten;
}

function werkWaarschuwingenBij(){
  var g=$("#f_gewenst").value, s=$("#f_start").value;
  var wd = (g&&s)?werkdagenTussen(g,s):null;
  $("#w_datum").classList.toggle("verborgen", !(wd!==null && wd<2));
  $("#w_data").classList.toggle("verborgen", ($('input[name="dataverw"]:checked')||{}).value!=="NEE");
  $("#subveld").classList.toggle("verborgen", ($('input[name="subkeuze"]:checked')||{}).value!=="eigen");
}

/* -------------------------------- mail -------------------------------- */
function mailOnderwerp(d,ref){
  return "Go-Assets aanvraag " + (ref?ref+" ":"") + "– " + (d.klant.bedrijfsnaam||"[klant]")
       + " – " + (d.plaats||"[plaats]") + " – start " + (datumNL(d.startdatum)||"?");
}

function mailTekst(d,ref){
  var L=[];
  function kop(t){ L.push("",t.toUpperCase()); }
  function rij(k,v){ L.push(k+": "+(v||"—")); }

  L.push("Beste Site Security,");
  L.push("");
  L.push("Graag ontvangen wij een Go-Assets omgeving voor onderstaand project.");
  L.push("Aanvraag conform de procedure Go-Assets (Industrial → Site Security).");
  if(ref) { L.push(""); L.push("Referentie aanvraag: "+ref); }

  kop("PROJECTGEGEVENS");
  rij("Land",d.land); rij("Plaats",d.plaats);
  rij("Gewenste datum omgeving beschikbaar",datumNL(d.gewenste_datum));
  rij("Startdatum project (bij klant)",datumNL(d.startdatum));
  rij("Einddatum (inschatting)",datumNL(d.einddatum));
  rij("Terugkerende klant / project", d.terugkerend?"Ja":"Nee");
  if(d.overig) rij("Overig",d.overig);

  kop("OPZET OMGEVING");
  rij("Subdomein", d.subdomein_keuze==="eigen"
      ? d.subdomein+CONFIG.suffix
      : "Geen voorkeur – vrij in te vullen door Go-Workforce");
  rij("Data verwijderen 30 dagen na project", d.data_verwijderen==="JA"
      ? "JA (standaard)" : "NEE – graag afspraken maken");

  kop("CONTACTPERSOON BEDRIJF (HOOFDAANNEMER / CLIENT ENTITY)");
  rij("Bedrijfsnaam",d.klant.bedrijfsnaam); rij("Insphire klantnummer",d.klant.klantnummer); rij("BTW-nummer",d.klant.btw);
  rij("Naam",(d.klant.voornaam+" "+d.klant.achternaam).trim());
  rij("E-mailadres",d.klant.email); rij("Telefoonnummer",d.klant.telefoon);
  rij("Functie",d.klant.functie);

  kop("GEBRUIKERS GO-ASSETS");
  d.gebruikers.forEach(function(g,i){
    L.push((d.gebruikers.length>1?("Gebruiker "+(i+1)+":"):"Gebruiker:"));
    rij("  Bedrijfsnaam", g.bedrijfsnaam||"Boels");
    rij("  Naam",(g.voornaam+" "+g.achternaam).trim());
    rij("  E-mailadres",g.email);
  });

  kop("INSPHIRE");
  rij("Koppeling naar Insphire", d.insphire==="JA"?"Ja":"Nee");
  rij("Insphire klantnummer", d.klant.klantnummer);
  rij("Insphire projectcode", d.projectcode);
  rij("Contractnummer", d.contractnummer);

  kop("HARDWARE");
  if(d.hardware.length){
    d.hardware.forEach(function(h){ L.push("  "+String(h.aantal).padStart(3," ")+" x  "+h.code+" - "+h.naam); });
  } else { L.push("  Geen hardware nodig."); }

  L.push("");
  L.push("De omgeving graag minimaal 2 werkdagen vóór de startdatum gereed,");
  L.push("zodat wij de omgeving kunnen testen vóór aanvang van het project.");
  L.push("");
  L.push("Met vriendelijke groet,");
  L.push(GEBRUIKER.naam || "[naam aanvrager]");
  if(GEBRUIKER.email) L.push(GEBRUIKER.email);
  L.push("Boels Industrial");
  return L.join("\n");
}

function afmeldTekst(p,reden,einddatum){
  var L=[];
  L.push("Beste Site Security,");
  L.push("");
  L.push("Bij dezen melden wij onderstaand Go-Assets project af. Graag de omgeving uitzetten.");
  L.push("");
  L.push("Referentie: "+p.ref);
  L.push("Klant: "+(p.klant?p.klant.bedrijfsnaam:"")+(p.klant&&p.klant.klantnummer?" (Insphire klantnr "+p.klant.klantnummer+")":""));
  L.push("Plaats: "+(p.plaats||""));
  L.push("Subdomein: "+(p.subdomein? p.subdomein+CONFIG.suffix : "door Go-Workforce ingevuld"));
  L.push("Contractnummer: "+(p.contractnummer||"—"));
  L.push("Insphire projectcode: "+(p.projectcode||"—"));
  L.push("Einddatum project: "+datumNL(einddatum||p.einddatum));
  L.push("Data verwijderen: "+(p.data_verwijderen==="JA"
        ? "JA – 30 dagen na project (standaard)" : "NEE – conform gemaakte afspraak"));
  if(reden){ L.push(""); L.push("Toelichting: "+reden); }
  L.push("");
  L.push("Graag ontvangen wij een korte bevestiging van deze afmelding zodra de omgeving is uitgezet.");
  L.push("Beantwoorden van deze mail is voldoende; het antwoord komt rechtstreeks bij de aanvrager terecht.");
  L.push("");
  L.push("Met vriendelijke groet,");
  L.push(GEBRUIKER.naam || "[naam aanvrager]");
  if(GEBRUIKER.email) L.push(GEBRUIKER.email);
  L.push("Boels Industrial");
  return L.join("\n");
}

function openMail(aan,onderwerp,tekst,ctx){
  // Mailto-links met de volledige tekst zijn te lang voor Windows/Outlook
  // (grens ± 2000 tekens, daarboven gebeurt er stil niets). Daarom: mail
  // tonen en laten kiezen — versturen via CORE, of Outlook openen met de
  // tekst op het klembord. In testmodus gaat versturen alleen naar jezelf.
  ctx = ctx || {};
  var ref = ctx.ref || (/GA-\d{4}-\d{3}|TEST-\d{4}-\d{3}/.exec(onderwerp)||[""])[0] || "";
  // Uitgesteld: als openMail vanuit een bevestig-modaal wordt aangeroepen,
  // sluit dat modaal direct daarna — dit venster moet daar ná komen.
  setTimeout(function(){ toonMailModaal(aan,onderwerp,tekst,ref,ctx); }, 0);
}

function toonMailModaal(aan,onderwerp,tekst,ref,ctx){
  modaal((TESTMODUS ? "Testmodus — " : "") + "Mail versturen",
    '<div class="mailkop"><span>Aan: <b>'+esc(aan)+'</b></span></div>'
    + '<div class="mailkop"><span>Onderwerp: <b>'+esc(onderwerp)+'</b></span></div>'
    + '<textarea id="om_tekst" style="width:100%;min-height:260px;max-height:50vh;font:12.5px/1.45 ui-monospace,Menlo,monospace;white-space:pre;overflow:auto;border:1px solid var(--lijn,#cfd6dc);border-radius:8px;padding:10px;box-sizing:border-box">'+esc(tekst)+'</textarea>'
    + '<p class="hint" style="margin:8px 0 10px">Je kunt de tekst hierboven nog aanpassen. '
    + (TESTMODUS ? '<b>Testmodus:</b> versturen gaat alleen naar je eigen adres, niet naar '+esc(aan)+'.' : 'Je krijgt zelf altijd een kopie (cc) in je mailbox en antwoorden komen bij jou binnen.')
    + '</p>'
    + '<div style="display:flex;gap:8px;flex-wrap:wrap">'
    + '<button type="button" class="knop primair" id="om_verstuur">'+(TESTMODUS?'Testmail naar mezelf sturen':'Versturen via CORE')+'</button>'
    + '<button type="button" class="knop" id="om_kopie">Tekst kopi&euml;ren</button>'
    + '</div>');
  var ta = $("#om_tekst");
  $("#om_kopie").onclick = function(){ kopieer(ta.value, "Mailtekst gekopieerd."); };
  $("#om_verstuur").onclick = function(){
    var knop = this; knop.disabled = true; knop.textContent = "Bezig…";
    api("mail", { aan:aan, onderwerp:onderwerp, tekst:ta.value, test:TESTMODUS, ref:ref, project_id:ctx.project_id||null })
      .then(function(j){
        toast(TESTMODUS ? "Testmail verstuurd naar "+j.naar+"." : "Mail verstuurd naar "+j.naar+" (kopie naar jezelf).");
        var a=$("#m_annuleer"); if(a) a.click();
        laadAlles();
      })
      .catch(function(e){ toast("Versturen mislukt: "+e.message+" — kopieer de tekst en mail hem handmatig.", true); knop.disabled=false; knop.textContent = TESTMODUS?'Testmail naar mezelf sturen':'Versturen via CORE'; });
  };
}

function kopieer(tekst,melding){
  function terugval(){
    var ta=document.createElement("textarea");
    ta.value=tekst; ta.style.position="fixed"; ta.style.opacity="0";
    document.body.appendChild(ta); ta.select();
    try{ document.execCommand("copy"); toast(melding||"Gekopieerd naar klembord."); }
    catch(e){ toast("Kopiëren lukt niet in deze browser.",true); }
    document.body.removeChild(ta);
  }
  if(navigator.clipboard && navigator.clipboard.writeText){
    navigator.clipboard.writeText(tekst).then(function(){ toast(melding||"Gekopieerd naar klembord."); }, terugval);
  } else terugval();
}

function bouwMail(){
  var d=leesFormulier();
  $("#mailOnderwerp").textContent = mailOnderwerp(d,"");
  $("#mailTekst").textContent = mailTekst(d,"");
}

/* ------------------------------ projecten ------------------------------ */
function lopend(p){ return p.status!=="beeindigd"; }

function tekenProjecten(){
  var zoek=($("#zoek").value||"").toLowerCase();
  var filter=$("#filterStatus").value;
  var lijst=PROJECTEN.filter(function(p){
    if(filter==="lopend" && !lopend(p)) return false;
    if(filter && filter!=="lopend" && p.status!==filter) return false;
    if(!zoek) return true;
    var hooi=[p.ref,p.plaats,p.contractnummer,p.projectcode,p.aanvrager_naam,
              p.klant&&p.klant.bedrijfsnaam, p.subdomein].join(" ").toLowerCase();
    return hooi.indexOf(zoek)>-1;
  });
  $("#telProjecten").textContent = PROJECTEN.filter(lopend).length;

  if(!lijst.length){
    $("#projectenlijst").innerHTML='<div class="leeg">Geen projecten gevonden.'
      + (PROJECTEN.length?"":" Dien eerst een aanvraag in via het tabblad 'Nieuwe aanvraag'.")+'</div>';
    return;
  }
  $("#projectenlijst").innerHTML = lijst.map(function(p){
    var st=STATUSSEN[p.status]||STATUSSEN.aangevraagd;
    var knoppen='';
    if(st.volgende){
      knoppen += '<button type="button" class="knop klein '+(p.status==="actief"?"gevaar":"")+'" '
              + 'data-actie="status" data-id="'+esc(p.id)+'" data-naar="'+st.volgende+'">'+esc(st.knop)+'</button>';
    }
    if(p.status!=="beeindigd" && p.status!=="afgemeld"){
      knoppen += '<button type="button" class="knop klein" data-actie="support" data-id="'+esc(p.id)+'">Melding naar Support</button>';
    }
    if(p.status==="afgemeld"){
      knoppen += '<button type="button" class="knop klein" data-actie="afmeldmail" data-id="'+esc(p.id)+'">Afmeldmail opnieuw</button>';
      if(!p.afmelding_bevestigd_op){
        knoppen += '<button type="button" class="knop klein" data-actie="bevestigd" data-id="'+esc(p.id)+'" title="Site Security heeft de afmelding per mail bevestigd">Bevestiging ontvangen</button>';
      }
    }
    knoppen += '<button type="button" class="knop klein" data-actie="details" data-id="'+esc(p.id)+'">Details &amp; mail</button>';

    return '<div class="proj">'
      + '<div class="kop">'
      +   '<span class="ref">'+esc(p.ref)+'</span>'
      +   (p.test ? '<span class="testbadge" title="Testaanvraag — telt niet mee, op te ruimen via Testmodus">TEST</span>' : '')
      +   '<div style="flex:1;min-width:180px">'
      +     '<div class="titel">'+esc((p.klant&&p.klant.bedrijfsnaam)||"onbekende klant")+' – '+esc(p.plaats||"")+'</div>'
      +     '<div class="meta">'
      +       '<span>Start: '+datumNL(p.startdatum)+'</span>'
      +       '<span>Eind: '+datumNL(p.einddatum)+'</span>'
      +       '<span>Contract: '+esc(p.contractnummer||"—")+'</span>'
      +       '<span>Aangevraagd door: '+esc(p.aanvrager_naam||"—")+'</span>'
      +       (p.gestart_automatisch ? '<span title="Op de startdatum automatisch op Actief gezet">Automatisch gestart</span>' : '')
      +       (p.afmelding_bevestigd_op ? '<span title="Bevestiging van Site Security ontvangen op '+tijdNL(p.afmelding_bevestigd_op)+'">&#10003; Afmelding bevestigd</span>' : '')
      +     '</div>'
      +   '</div>'
      +   '<span class="status s-'+esc(p.status)+'">'+esc(st.label)+'</span>'
      + '</div>'
      + '<div class="knoppen">'+knoppen+'</div>'
      + '</div>';
  }).join("");
}

function projectOpId(id){ return PROJECTEN.filter(function(p){ return p.id===id; })[0]; }

function statusActie(id,naar){
  var p=projectOpId(id); if(!p) return;
  if(naar==="afgemeld"){ afmeldModaal(p); return; }
  bewaarStatus(id,naar).then(function(){
    p.status=naar;
    schrijfLog(p.ref,"Status → "+(STATUSSEN[naar]||{}).label, "", p.id);
    tekenProjecten();
    toast("Status bijgewerkt: "+(STATUSSEN[naar]||{}).label);
  });
}

function afmeldModaal(p){
  modaal("Project afmelden – "+esc(p.ref), ''
    + '<p class="hint" style="margin:0">Volgens de procedure geef je het einde van het project door aan Site Security. '
    + 'Zij zetten de Go-Assets omgeving uit.</p>'
    + '<div class="veld"><label for="a_eind">Werkelijke einddatum</label>'
    + '<input type="date" id="a_eind" value="'+esc(p.einddatum||"")+'"></div>'
    + '<div class="veld"><label for="a_reden">Toelichting (optioneel)</label>'
    + '<textarea id="a_reden" placeholder="bijv. project eerder afgerond, hardware retour via&hellip;"></textarea></div>'
    + '<div class="veld"><label style="display:flex;align-items:center;gap:8px;font-weight:500">'
    + '<input type="checkbox" id="a_mail" checked> Afmeldmail naar Site Security openen</label></div>',
    "Afmelden", function(){
      var eind=$("#a_eind").value, reden=$("#a_reden").value.trim(), mailen=$("#a_mail").checked;
      bewaarStatus(p.id,"afgemeld",{ afmeld_reden:reden, einddatum:eind }).then(function(){
        p.status="afgemeld"; if(eind) p.einddatum=eind; p.afmeld_reden=reden;
        schrijfLog(p.ref,"Project afgemeld", (eind?("einddatum "+datumNL(eind)):"")+(reden?" – "+reden:""), p.id);
        tekenProjecten();
        if(mailen) openMail(CONFIG.mailSiteSecurity,
          "Go-Assets afmelding "+p.ref+" – "+((p.klant&&p.klant.bedrijfsnaam)||""),
          afmeldTekst(p,reden,eind), {ref:p.ref, project_id:p.id});
        toast("Project afgemeld en vastgelegd in de log.");
      });
      return true;
    });
}

function supportModaal(p){
  modaal("Melding naar Support – "+esc(p.ref), ''
    + '<p class="hint" style="margin:0">2e-lijns storingen en testbevindingen gaan naar Go-Workforce Support ('
    + esc(CONFIG.mailSupport)+' – '+esc(CONFIG.telSupport)+').</p>'
    + '<div class="veld"><label for="s_soort">Soort melding</label><select id="s_soort">'
    + '<option>Test niet akkoord</option><option>2e-lijns storing</option><option>Vraag over de omgeving</option></select></div>'
    + '<div class="veld"><label for="s_om">Omschrijving <span class="ster">*</span></label>'
    + '<textarea id="s_om" placeholder="Wat is er aan de hand, sinds wanneer, welke gebruiker?"></textarea></div>',
    "Mail opstellen", function(){
      var soort=$("#s_soort").value, om=$("#s_om").value.trim();
      if(!om){ toast("Vul een omschrijving in.",true); return false; }
      var tekst=["Beste Support,","",
        soort+" voor onderstaande Go-Assets omgeving.","",
        "Referentie: "+p.ref,
        "Klant: "+((p.klant&&p.klant.bedrijfsnaam)||"")+(p.klant&&p.klant.klantnummer?" (Insphire klantnr "+p.klant.klantnummer+")":""),
        "Plaats: "+(p.plaats||""),
        "Subdomein: "+(p.subdomein? p.subdomein+CONFIG.suffix : "door Go-Workforce ingevuld"),
        "Startdatum: "+datumNL(p.startdatum),"",
        "Omschrijving :",om,"",
        "Met vriendelijke groet,",
        GEBRUIKER.naam||"[naam]", GEBRUIKER.email||"", "Boels Industrial"].join("\n");
      openMail(CONFIG.mailSupport, soort+" – Go-Assets "+p.ref, tekst, {ref:p.ref, project_id:p.id});
      schrijfLog(p.ref,"Melding naar Support",soort+": "+om,p.id);
      return true;
    });
}

function detailsModaal(p){
  var tekst=mailTekst(p,p.ref);
  modaal("Details – "+esc(p.ref), ''
    + '<div class="mailkop"><span>Aan: <b>'+esc(CONFIG.mailSiteSecurity)+'</b></span></div>'
    + '<div class="mailvoorbeeld">'+esc(tekst)+'</div>'
    + '<div style="display:flex;gap:10px;flex-wrap:wrap">'
    + '<button type="button" class="knop klein" id="d_mail">Mail opnieuw opstellen</button>'
    + '<button type="button" class="knop klein" id="d_kopie">Tekst kopiëren</button>'
    + '<button type="button" class="knop klein" id="d_kopieform">Als nieuwe aanvraag overnemen</button>'
    + '</div>'
    + '<h4 style="margin:16px 0 6px">Verstuurde mails</h4>'
    + '<div id="d_mails" class="hint">Laden&hellip;</div>', null, null);
  fetch(CONFIG.api + "?action=mails&project_id=" + encodeURIComponent(p.id) + "&ref=" + encodeURIComponent(p.ref||""), { credentials:"same-origin", headers:{"X-Requested-With":"XMLHttpRequest"} })
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(j){
      var el=$("#d_mails"); if(!el) return;
      var ms=(j && j.mails)||[];
      if(!ms.length){ el.textContent="Nog geen mails verstuurd voor dit project."; return; }
      el.className="";
      el.innerHTML = '<table class="log" style="width:100%"><thead><tr><th style="width:150px">Tijdstip</th><th style="width:160px">Wie</th><th>Onderwerp</th><th style="width:90px">Via</th><th style="width:90px"></th></tr></thead><tbody>'
        + ms.map(function(m,i){ return '<tr><td class="tijd">'+tijdNL(m.op)+'</td><td>'+esc(m.door||"")+'</td><td>'+esc(m.onderwerp)+(m.test?' <span class="testbadge">TEST</span>':'')+'</td><td>'+(m.via==="core"?"CORE":"Outlook")+'</td><td><button type="button" class="knop klein" data-mail="'+i+'">Bekijken</button></td></tr>'; }).join("")
        + '</tbody></table>';
      Array.prototype.forEach.call(el.querySelectorAll("button[data-mail]"), function(b){
        b.onclick=function(){
          var m=ms[+b.dataset.mail];
          modaal("Mail van "+tijdNL(m.op), '<div class="mailkop"><span>Aan: <b>'+esc(m.aan)+'</b></span></div><div class="mailkop"><span>Onderwerp: <b>'+esc(m.onderwerp)+'</b></span></div><div class="mailvoorbeeld" style="white-space:pre-wrap;max-height:55vh;overflow:auto">'+esc(m.tekst)+'</div>');
        };
      });
    }).catch(function(){ var el=$("#d_mails"); if(el) el.textContent="Mails konden niet geladen worden."; });
  $("#d_mail").onclick=function(){ openMail(CONFIG.mailSiteSecurity,mailOnderwerp(p,p.ref),tekst,{ref:p.ref,project_id:p.id}); };
  $("#d_kopie").onclick=function(){ kopieer(tekst); };
  $("#d_kopieform").onclick=function(){
    var kopie=JSON.parse(JSON.stringify(p));
    kopie.contractnummer=""; kopie.projectcode=""; kopie.gewenste_datum=""; kopie.startdatum=""; kopie.einddatum="";
    vulFormulier(kopie); sluitModaal(); toonTab("aanvraag");
    toast("Gegevens overgenomen — vul nieuwe datums en contractnummer in.");
  };
}

/* --------------------------------- log --------------------------------- */
function klantVanLog(r){
  var p = PROJECTEN.find(function(x){ return (r.project_id && x.id===r.project_id) || (r.ref && x.ref===r.ref); });
  return p && p.klant ? (p.klant.bedrijfsnaam||"") : "";
}
function tekenLog(){
  var zoek=($("#zoekLog").value||"").toLowerCase();
  var lijst=LOGREGELS.filter(function(r){
    if(!zoek) return true;
    return [r.ref,klantVanLog(r),r.actie,r.details,r.door].join(" ").toLowerCase().indexOf(zoek)>-1;
  });
  $("#telLog").textContent=LOGREGELS.length;
  if(!lijst.length){
    $("#logtabel").innerHTML='<tr><td colspan="6"><div class="leeg">Nog geen logregels.</div></td></tr>';
    return;
  }
  $("#logtabel").innerHTML=lijst.slice(0,500).map(function(r){
    return '<tr><td class="tijd">'+tijdNL(r.op)+'</td>'
      + '<td class="ref">'+esc(r.ref||"—")+'</td>'
      + '<td>'+esc(klantVanLog(r)||"—")+'</td>'
      + '<td>'+esc(r.door||"—")+'</td>'
      + '<td>'+esc(r.actie||"")+'</td>'
      + '<td>'+esc(r.details||"")+'</td></tr>';
  }).join("");
}

function csv(rijen){
  return rijen.map(function(r){
    return r.map(function(c){
      c=(c==null?"":String(c));
      return /[";\n]/.test(c) ? '"'+c.replace(/"/g,'""')+'"' : c;
    }).join(";");
  }).join("\r\n");
}
function download(naam,inhoud){
  var blob=new Blob(["﻿"+inhoud],{type:"text/csv;charset=utf-8"});
  var a=document.createElement("a");
  a.href=URL.createObjectURL(blob); a.download=naam;
  document.body.appendChild(a); a.click();
  setTimeout(function(){ URL.revokeObjectURL(a.href); a.remove(); },500);
}

/* -------------------------------- modaal -------------------------------- */
function modaal(titel,inhoud,knopTekst,bevestig){
  $("#modaalhouder").innerHTML='<div class="overlay"><div class="modaal" role="dialog" aria-modal="true">'
    + '<h3>'+titel+'</h3><div class="inhoud">'+inhoud+'</div>'
    + '<div class="voet"><button type="button" class="knop" id="m_annuleer">'+(knopTekst?"Annuleren":"Sluiten")+'</button>'
    + (knopTekst?'<button type="button" class="knop primair" id="m_ok">'+esc(knopTekst)+'</button>':'')
    + '</div></div></div>';
  $("#m_annuleer").onclick=sluitModaal;
  $(".overlay").onclick=function(e){ if(e.target===this) sluitModaal(); };
  if(knopTekst) $("#m_ok").onclick=function(){ if(bevestig()!==false) sluitModaal(); };
  var eerste=$(".modaal input,.modaal textarea,.modaal select"); if(eerste) eerste.focus();
}
function sluitModaal(){ $("#modaalhouder").innerHTML=""; }

/* -------------------------------- tabs ---------------------------------- */
function toonTab(naam){
  $$("nav.tabs button").forEach(function(b){ b.setAttribute("aria-selected", String(b.dataset.tab===naam)); });
  ["aanvraag","projecten","log","procedure"].forEach(function(t){
    $("#tab-"+t).classList.toggle("verborgen", t!==naam);
  });
  window.scrollTo({top:0,behavior:"instant"});
}

/* ------------------------------- procedure ------------------------------ */
function tekenProces(){
  $("#stroom").innerHTML = PROCES.map(function(s,i){
    return '<div class="stap">'
      + '<div class="lane '+s.lane+'">'+(s.lane==="ind"?"Industrial":"Site Security")+'</div>'
      + '<div class="doos '+s.lane+(s.uitz?" uitz":"")+'"><b>'+esc(s.titel)+'</b><small>'+esc(s.sub)+'</small></div>'
      + '</div>' + (i<PROCES.length-1?'<div class="stap"><div></div><div class="pijl">&#9660;</div></div>':'');
  }).join("");
}

/* ------------------------------ versturen ------------------------------- */
function versturen(){
  var d=leesFormulier();
  var fouten=valideer(d);
  var fbox=$("#fouten");
  if(fouten.length){
    fbox.classList.remove("verborgen");
    fbox.innerHTML="<b>Nog invullen:</b> "+esc(fouten.join(", "));
    fbox.scrollIntoView({behavior:"smooth",block:"center"});
    return;
  }
  fbox.classList.add("verborgen");

  var project = Object.assign({}, d, {
    id: "P"+Date.now()+Math.random().toString(16).slice(2,6),
    ref: volgnummer(),
    status: "aangevraagd",
    test: TESTMODUS,
    aanvrager_naam: GEBRUIKER.naam,
    aanvrager_email: GEBRUIKER.email,
    aangemaakt_op: nu()
  });

  $("#btnVersturen").disabled=true;
  bewaarProject(project).then(function(opgeslagen){
    if(!PROJECTEN.some(function(p){ return p.id===opgeslagen.id; })) PROJECTEN.unshift(opgeslagen);
    schrijfLog(opgeslagen.ref,"Aanvraag ingediend",
      (d.klant.bedrijfsnaam||"")+" – "+(d.plaats||"")+" – start "+datumNL(d.startdatum), opgeslagen.id);
    tekenProjecten();
    openMail(CONFIG.mailSiteSecurity, mailOnderwerp(d,opgeslagen.ref), mailTekst(d,opgeslagen.ref), {ref:opgeslagen.ref, project_id:opgeslagen.id});
    try{ localStorage.removeItem("ga_concept"); }catch(e){}
    toast("Aanvraag "+opgeslagen.ref+" geregistreerd — kies hoe je de mail verstuurt.");
  }).catch(function(e){
    toast("Opslaan mislukt: "+e.message,true);
  }).then(function(){
    $("#btnVersturen").disabled=false;
  });
}

/* -------------------------------- start --------------------------------- */
function koppelGebeurtenissen(){
  $$("nav.tabs button").forEach(function(b){ b.onclick=function(){ toonTab(b.dataset.tab); }; });

  document.addEventListener("input", function(e){
    if(e.target.closest("#tab-aanvraag")){ werkWaarschuwingenBij(); bouwMail(); }
    if(e.target.id==="zoek") tekenProjecten();
    if(e.target.id==="zoekLog") tekenLog();
  });
  document.addEventListener("change", function(e){
    if(e.target.classList.contains("hw-aan")){
      var i=e.target.dataset.i, inp=$('.hw-aantal[data-i="'+i+'"]');
      inp.disabled=!e.target.checked;
      if(e.target.checked && (!inp.value || inp.value==="0")) inp.value="1";
      if(!e.target.checked) inp.value="0";
      $('#hardware tr[data-rij="'+i+'"]').classList.toggle("aan",e.target.checked);
      bouwMail();
    }
    if(e.target.closest("#tab-aanvraag")){ werkWaarschuwingenBij(); bouwMail(); }
    if(e.target.id==="filterStatus") tekenProjecten();
  });

  $("#btnGebruiker").onclick=voegGebruikerToe;
  document.addEventListener("click", function(e){
    var weg=e.target.closest(".g-weg");
    if(weg){ weg.closest(".gebruiker").remove(); hernummerGebruikers(); bouwMail(); return; }
    var knop=e.target.closest("[data-actie]");
    if(knop){
      var p=projectOpId(knop.dataset.id); if(!p) return;
      if(knop.dataset.actie==="status")     statusActie(p.id,knop.dataset.naar);
      if(knop.dataset.actie==="support")    supportModaal(p);
      if(knop.dataset.actie==="details")    detailsModaal(p);
      if(knop.dataset.actie==="bevestigd"){
        modaal("Bevestiging ontvangen", "<p>Site Security heeft de afmelding van <b>"+esc(p.ref)+"</b> bevestigd? Dit wordt vastgelegd in het log.</p>", "Vastleggen", function(){
          bewaarStatus(p.id, "afgemeld", { afmelding_bevestigd_op: nu() }).then(function(){
            p.afmelding_bevestigd_op = nu();
            schrijfLog(p.ref, "Bevestiging afmelding ontvangen", "Site Security heeft de afmelding bevestigd", p.id);
            tekenProjecten(); toast("Bevestiging vastgelegd.");
          });
          return true;
        });
      }
      if(knop.dataset.actie==="afmeldmail") openMail(CONFIG.mailSiteSecurity,
          "Go-Assets afmelding "+p.ref+" – "+((p.klant&&p.klant.bedrijfsnaam)||""),
          afmeldTekst(p,p.afmeld_reden,p.einddatum), {ref:p.ref, project_id:p.id});
    }
  });

  $("#pillGebruiker").onclick=vraagGebruiker;
  $("#btnVersturen").onclick=versturen;
  $("#btnKopieer").onclick=function(){ kopieer($("#mailTekst").textContent); };
  $("#btnConcept").onclick=function(){
    try{ localStorage.setItem("ga_concept",JSON.stringify(leesFormulier())); toast("Concept opgeslagen in deze browser."); }
    catch(e){ toast("Opslaan concept mislukt.",true); }
  };
  $("#btnLeeg").onclick=function(){
    modaal("Formulier legen?","<p style='margin:0'>Alle ingevulde gegevens in het formulier gaan verloren.</p>",
      "Legen",function(){
        try{ localStorage.removeItem("ga_concept"); }catch(e){}
        vulFormulier({gebruikers:[{}]}); $("#fouten").classList.add("verborgen");
        toast("Formulier geleegd."); return true;
      });
  };
  $("#btnVernieuw").onclick=function(){ laadAlles().then(function(){ toast("Bijgewerkt."); }); };
  $("#btnExportProj").onclick=function(){
    var rijen=[["Referentie","Status","Klant","Plaats","Land","Subdomein","Contractnummer","Insphire projectcode",
      "Gewenste datum","Startdatum","Einddatum","Data verwijderen","Aanvrager","Aangevraagd op","Hardware"]];
    PROJECTEN.forEach(function(p){
      rijen.push([p.ref,(STATUSSEN[p.status]||{}).label,(p.klant&&p.klant.bedrijfsnaam)||"",p.plaats,p.land,
        p.subdomein?p.subdomein+CONFIG.suffix:"geen voorkeur",p.contractnummer,p.projectcode,
        p.gewenste_datum,p.startdatum,p.einddatum,p.data_verwijderen,p.aanvrager_naam,p.aangemaakt_op,
        (p.hardware||[]).map(function(h){return h.aantal+"x "+h.code;}).join(" / ")]);
    });
    download("go-assets-projecten.csv",csv(rijen));
  };
  $("#btnExportLog").onclick=function(){
    var rijen=[["Tijdstip","Referentie","Klant","Wie","E-mail","Actie","Details"]];
    LOGREGELS.forEach(function(r){ rijen.push([r.op,r.ref,klantVanLog(r),r.door,r.door_email,r.actie,r.details]); });
    download("go-assets-log.csv",csv(rijen));
  };
  document.addEventListener("keydown", function(e){ if(e.key==="Escape") sluitModaal(); });
}

function start(){
  bouwHardware();
  tekenProces();
  koppelGebeurtenissen();
  var concept=null;
  try{ concept=JSON.parse(localStorage.getItem("ga_concept")||"null"); }catch(e){}
  vulFormulier(concept || { gebruikers:[{}] });
  if(concept) toast("Opgeslagen concept teruggezet.");

  bepaalGebruiker().then(function(gevonden){
    toonGebruiker(); bouwMail();
    if(!gevonden) vraagGebruiker();
  });
  zetTestmodus(TESTMODUS);
  var pt=$("#pillTest"); if(pt) pt.onclick=function(){ zetTestmodus(!TESTMODUS); toast(TESTMODUS ? "Testmodus AAN: TEST-nummers, geen Outlook." : "Testmodus uit."); };
  var bo=$("#btnOpruimTest"); if(bo) bo.onclick=opruimTest;
  laadAlles();
}

document.addEventListener("DOMContentLoaded", start);
</script>
<script>
/* ---- Klant zoeken in Boels CORE (klantenbestand) ---- */
(function(){
  var invoer = document.getElementById("f_klantzoek");
  var lijst  = document.getElementById("klantSuggesties");
  if(!invoer || !lijst) return;
  window.KLANT_CORE = window.KLANT_CORE || null;
  var timer = null, laatste = "", actief = -1, items = [];
  var LANDEN = { NL:"Nederland", BE:"Belgi\u00eb", DE:"Duitsland", NEDERLAND:"Nederland", BELGIE:"Belgi\u00eb", "BELGI\u00cb":"Belgi\u00eb", DUITSLAND:"Duitsland" };

  function esc(s){ return String(s==null?"":s).replace(/[&<>"']/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c];}); }

  window.toonKlantKeuze = function(){
    var el = document.getElementById("klantGekozen");
    if(!el) return;
    if(window.KLANT_CORE && window.KLANT_CORE.nummer){
      el.innerHTML = "Gekozen uit CORE: <b>"+esc(window.KLANT_CORE.naam)+"</b> (klantnummer "+esc(window.KLANT_CORE.nummer)+") &mdash; <a href=\"#\" id=\"klantLos\">loskoppelen</a>";
      var los = document.getElementById("klantLos");
      if(los) los.onclick = function(e){ e.preventDefault(); window.KLANT_CORE=null; invoer.value=""; window.toonKlantKeuze(); };
    } else {
      el.textContent = "Kies een klant uit het CORE-klantenbestand; bedrijfsnaam, Insphire klantnummer, BTW-nummer en plaats worden dan ingevuld. Handmatig invullen kan ook.";
    }
  };

  function sluit(){ lijst.hidden = true; lijst.innerHTML=""; actief=-1; items=[]; }

  function zetVeld(id, waarde, alleenAlsLeeg){
    var el = document.getElementById(id);
    if(!el || waarde==null || waarde==="") return;
    if(alleenAlsLeeg && el.value.trim()!=="") return;
    el.value = waarde;
  }

  function kies(k){
    window.KLANT_CORE = { nummer:k.nummer, naam:k.naam };
    zetVeld("f_bedrijf", k.naam, false);
    zetVeld("f_klantnummer", k.nummer, false);
    zetVeld("f_btw", k.btw, false);
    zetVeld("f_plaats", k.plaats, true);
    zetVeld("f_cp_mail", k.email, true);
    zetVeld("f_cp_tel", k.telefoon, true);
    var land = LANDEN[String(k.land||"").toUpperCase()] || "";
    var sel = document.getElementById("f_land");
    if(land && sel){ for(var i=0;i<sel.options.length;i++){ if(sel.options[i].value===land || sel.options[i].text===land){ sel.selectedIndex=i; break; } } }
    invoer.value = k.naam + " (" + k.nummer + ")";
    sluit(); window.toonKlantKeuze();
    if(typeof bouwMail === "function") try{ bouwMail(); }catch(e){}
  }

  function teken(){
    if(!items.length){ lijst.innerHTML='<button type="button" disabled>Geen klant gevonden</button>'; lijst.hidden=false; return; }
    lijst.innerHTML = items.map(function(k,i){
      return '<button type="button" data-i="'+i+'"'+(i===actief?' class="actief"':'')+'>'
        + '<span class="nr">'+esc(k.nummer)+'</span>'+esc(k.naam)
        + '<span class="sub">'+esc([k.adres, k.postcode, k.plaats].filter(Boolean).join(", "))+(k.concern && k.concern!==k.naam ? " &middot; concern: "+esc(k.concern) : "")+'</span>'
        + '</button>';
    }).join("");
    lijst.hidden = false;
    Array.prototype.forEach.call(lijst.querySelectorAll("button[data-i]"), function(b){
      b.addEventListener("mousedown", function(e){ e.preventDefault(); kies(items[+b.dataset.i]); });
    });
  }

  function zoek(q){
    fetch(CONFIG.api + "?action=klanten&q=" + encodeURIComponent(q), { credentials:"same-origin", headers:{"X-Requested-With":"XMLHttpRequest"} })
      .then(function(r){ return r.ok ? r.json() : null; })
      .then(function(j){ if(q!==laatste) return; items = (j && j.klanten) || []; actief=-1; teken(); })
      .catch(function(){ items=[]; teken(); });
  }

  invoer.addEventListener("input", function(){
    var q = invoer.value.trim(); laatste = q;
    if(window.KLANT_CORE){ window.KLANT_CORE=null; window.toonKlantKeuze(); }
    clearTimeout(timer);
    if(q.length < 2){ sluit(); return; }
    timer = setTimeout(function(){ zoek(q); }, 250);
  });
  invoer.addEventListener("keydown", function(e){
    if(lijst.hidden) return;
    if(e.key==="ArrowDown"){ e.preventDefault(); actief=Math.min(actief+1, items.length-1); teken(); }
    else if(e.key==="ArrowUp"){ e.preventDefault(); actief=Math.max(actief-1, 0); teken(); }
    else if(e.key==="Enter"){ if(actief>=0 && items[actief]){ e.preventDefault(); kies(items[actief]); } }
    else if(e.key==="Escape"){ sluit(); }
  });
  invoer.addEventListener("blur", function(){ setTimeout(sluit, 150); });
  window.toonKlantKeuze();
})();
</script>
</body>
</html>

@endverbatim
