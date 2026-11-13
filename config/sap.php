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
            'label' => 'Planning + pickstand',
            'bestand' => '8.7 INDUSTRIAL HOP, SOP and DT View.xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Eén rij per order: huur, verkoop, transfers; met gepickt/totaal, leverdatum en verkoper. Let op: de platte "View" met kop PickUpDate … DelDate.',
            'kop' => ['PickUpDate', 'Items', 'contno', 'SalesPerson'],
        ],
        'planning_regels' => [
            'label' => 'Planning: artikelregels (8.7 gegroepeerd)',
            'bestand' => '8.7 INDUSTRIAL HOP, SOP and DT View.xlsx (gegroepeerde variant met artikelregels)',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Optioneel: de gegroepeerde 8.7-export met per order de artikelregels. Vult het orderdetail aan voor orders die niet in 2.6 staan.',
            'kop' => ['COLLECTION DATE/TIME', 'ITEM NUMBER', 'CONTRACTS.CONTNO'],
            'verborgen' => true,      // geen eigen kaart: mag op de kaart "Planning + pickstand" (wordt dan als aanvulling verwerkt)
            'alias_van' => 'planning',
        ],
        'reserveringen' => [
            'label' => 'Reserveringen (offertes + toekomstige contracten)',
            'bestand' => '2.6 Reservations with items (today and future).xlsx',
            'frequentie_dagen' => 1,
            'omschrijving' => 'Offertes en contracten in de toekomst met artikelregels en orderwaarde; kolom H bepaalt contract (Ingegeven) of offerte.',
            'kop' => ['CONTRACT NUMBER', 'OFFERTE STATUS', 'TOTAL GOODS VALUE'],
        ],
    ],
];
