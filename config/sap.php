<?php

/*
|--------------------------------------------------------------------------
| Smartview-exports (Boels Industrial) — centraal in CORE
|--------------------------------------------------------------------------
| Vier Excel-rapporten uit Smartview landen via Beheer → Uploads Smartview in CORE en
| worden via /api/hire/*, /api/planning/* en /api/sap/* aan de child-apps
| (Dashboard, Shell/BP/Damen/RMW, Projectschade, Tankapp) aangeboden.
| Zie docs/sap-uploads.md voor de kolomregels per bestand.
*/

return [
    // Alleen contracten van dit depot meenemen (eerste 3 cijfers van het contractnummer).
    // Leeg = alles (voor een Industrial-brede export later).
    'depot' => env('SAP_DEPOT', '759'),

    // Hoeveel uploads per bestandssoort de REGELS bewaard blijven (de upload-rij met
    // samenvatting blijft altijd staan, voor trends).
    'bewaar_snapshots' => (int) env('SAP_BEWAAR_SNAPSHOTS', 30),

    // Bestandssoorten: sleutel => label, verwachte bestandsnaam, frequentie (dagen), kopherkenning
    'soorten' => [
        'vloot' => [
            'label' => 'Vlootvoorraad (Items With Every Status)',
            'bestand' => 'Items With Every Status.xlsx',
            'frequentie_dagen' => 0,          // 0 = meerdere keren per dag
            'omschrijving' => 'Momentopname van de hele Industrial-vloot: per machine de status (beschikbaar, verhuurd, onderhoud, reparatie, onderweg) en het depot waar hij nu staat. Basis voor bezettingsmeters en de voorraadcheck.',
            'kop' => ['ITEMNO', 'Item Description', 'GRPCODE', 'STATUS', 'CURRDEPOT'],
            'alle_depots' => true,
            'bewaar_snapshots' => 6,          // ~60.000 regels per upload: alleen de laatste uploads volledig bewaren
        ],
        'wow' => [
            'label' => 'In huur (What\'s Out Where)',
            'bestand' => 'boels_whatsoutwhere_incllimitedmode.xls',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Alles wat nu in huur staat: contracten, artikelen, weekhuur, status (ook "To Collect" = op te halen). Basis voor In huur, Ophaallijst en AVI.',
            'kop' => ['Contract Number', 'Item Number', 'Weekly Hire Rate', 'EstRtDate'],
        ],
        'factuurrun' => [
            'label' => 'Uit factuurrun',
            'bestand' => '0.07 Contracts and items not part of invoice run.xlsx',
            'frequentie_dagen' => 7,
            'omschrijving' => 'Contracten die niet in de facturatierun zitten en gecontroleerd moeten worden; met redenveld per contract.',
            'kop' => ['CONTRACT NUMBER', 'DATE LAST INVOICE', 'CREATED BY'],
        ],
        'planning' => [
            'label' => 'Planning + pickstand (8.7)',
            'bestand' => '8.7 INDUSTRIAL HOP, SOP and DT View.xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Eén rij per order: huur, verkoop, transfers; met gepickt/totaal, leverdatum en verkoper. Let op: de platte "View" met kop PickUpDate … DelDate.',
            'kop' => ['PickUpDate', 'Items', 'contno', 'SalesPerson'],
        ],
        'contract_items' => [
            'label' => 'Contractregels te leveren (3.4)',
            'bestand' => '3.4 Contract Items due for delivery today and the future.xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Per artikelregel de echte leverdatum: toekomstige contracten, offertes én nieuwe regels op lopende contracten (bijhuur). Vervangt 2.6.',
            'kop' => ['ITEM DELIVERY DATE', 'CONTRACT NUMBER', 'ITEM STATUS', 'TOTAL GOODS VALUE'],
            'opslaan_als' => 'reserveringen',   // zelfde tabel/snapshotreeks als 2.6
        ],
        'planning_regels' => [
            'label' => 'Projectnummers (8.A Schedule Hire logistic)',
            'bestand' => '8.A INDUSTRIAL Schedule Hire logistic.xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Per contract de regels met PROJECTNUMBER. De voorraadcheck gebruikt dit om op projectnummer te zoeken (alle contracten van een project samen).',
            'kop' => ['COLLECTION DATE/TIME', 'ITEM NUMBER', 'CONTRACTS.CONTNO'],
            'alias_van' => 'planning',
            // 17-11-2026 weer nodig: projectnummer per contract (voorraadcheck); 'Toegekend' hierin ≠ gepickt
        ],
        'reserveringen' => [
            'label' => 'Reserveringen (offertes + toekomstige contracten)',
            'bestand' => '2.6 Reservations with items (today and future).xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Offertes en contracten in de toekomst met artikelregels en orderwaarde; kolom H bepaalt contract (Ingegeven) of offerte.',
            'kop' => ['CONTRACT NUMBER', 'OFFERTE STATUS', 'TOTAL GOODS VALUE'],
            'verborgen' => true,      // vervangen door 3.4; wordt nog wel herkend en verwerkt
        ],
    ],
];
