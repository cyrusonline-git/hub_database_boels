<?php
$D = sys_get_temp_dir() . '/vraagbaak-fixtures/domains'; foreach (['tankapp.sorai.nl/public_html/database','boels.sorai.nl/public_html/data','projectschade.sorai.nl/public_html','spoedverhuur.sorai.nl/laravel_app/database','scanner.sorai.nl/public_html/data','voorraad.sorai.nl/laravel_app/database'] as $m) { @mkdir("$D/$m", 0777, true); }
$nu = date('Y-m-d');
$vorige = date('Y-m-d', strtotime('-1 month'));
// Tankapp
@unlink("$D/tankapp.sorai.nl/public_html/database/tankapp.db");
$t = new PDO("sqlite:$D/tankapp.sorai.nl/public_html/database/tankapp.db");
$t->exec("CREATE TABLE areas (id INTEGER PRIMARY KEY, name TEXT); CREATE TABLE depots (id INTEGER PRIMARY KEY, name TEXT, area_id INTEGER, email TEXT);
CREATE TABLE aftankingen (id INTEGER PRIMARY KEY, depot_id INTEGER, user_id INTEGER, liters REAL, fuel_type TEXT, machine_number TEXT, contract_number TEXT, status TEXT, has_damage INTEGER DEFAULT 0, archived INTEGER DEFAULT 0, andere_vestiging_id INTEGER, liters_ander_depot REAL, opmerkingen TEXT, created_at TEXT);
INSERT INTO areas VALUES (1,'West'),(2,'Zuid'); INSERT INTO depots VALUES (1,'Rotterdam-Europoort',1,NULL),(2,'Geleen - Chemelot',2,NULL),(3,'BLS',1,NULL);
INSERT INTO aftankingen (depot_id,liters,fuel_type,machine_number,contract_number,status,archived,created_at) VALUES
(1,120.5,'Diesel','7590019510','C1','doorbelast',1,'$nu 08:00:00'),(1,80,'Diesel','7590019511','','niet_doorbelast',0,'$nu 09:00:00'),(1,30,'AdBlue','7590019510','C2','eigen_gebruik',0,'$vorige 09:00:00'),(2,200,'Diesel','8800001','C3','doorbelast',1,'$nu 10:00:00'),(3,999,'Diesel','x','','doorbelast',1,'$nu 10:00:00');");
// Inhuur
@unlink("$D/boels.sorai.nl/public_html/data/boels_inhuur.sqlite");
$i = new PDO("sqlite:$D/boels.sorai.nl/public_html/data/boels_inhuur.sqlite");
$i->exec("CREATE TABLE depots (id INTEGER PRIMARY KEY, name TEXT, area_id INTEGER); CREATE TABLE suppliers (id INTEGER PRIMARY KEY, name TEXT, email TEXT);
CREATE TABLE rentals (id INTEGER PRIMARY KEY, depot_id INTEGER, supplier_id INTEGER, article_name TEXT, subgroup_number TEXT, quantity INTEGER, rental_rate_week REAL, hire_rate_week REAL, start_date TEXT, expected_return_date TEXT, actual_return_date TEXT, status TEXT, archived INTEGER DEFAULT 0, created_at TEXT);
INSERT INTO depots VALUES (1,'Rotterdam-Europoort',1),(2,'Geleen - Chemelot',2); INSERT INTO suppliers VALUES (1,'Riwal',NULL),(2,'Boels Zuid',NULL);
INSERT INTO rentals (depot_id,supplier_id,article_name,subgroup_number,quantity,rental_rate_week,hire_rate_week,start_date,expected_return_date,actual_return_date,status,created_at) VALUES
(1,1,'Hoogwerker 12m','1234',1,500,350,'$vorige',date('now','-3 day'),NULL,'IN HUUR','$vorige'),(1,2,'Compressor 7m3','5678',2,200,150,'$nu',date('now','+10 day'),NULL,'IN HUUR','$nu'),(2,1,'Schaarlift','1234',1,400,300,'$vorige','$nu','$nu','AFGEMELD','$vorige'),(1,1,'Concept ding','9999',1,0,0,'$nu',NULL,NULL,'CONCEPT','$nu');");
// Projectschade
@unlink("$D/projectschade.sorai.nl/public_html/database.sqlite");
$p = new PDO("sqlite:$D/projectschade.sorai.nl/public_html/database.sqlite");
$p->exec("CREATE TABLE areas (id INTEGER PRIMARY KEY, naam TEXT); CREATE TABLE depots (id INTEGER PRIMARY KEY, naam TEXT, area_id INTEGER, actief INTEGER); CREATE TABLE projects (id INTEGER PRIMARY KEY, projectnaam TEXT, klantnaam TEXT);
CREATE TABLE machine_articles (id INTEGER PRIMARY KEY, artikelnummer TEXT, omschrijving TEXT);
CREATE TABLE damage_headers (id INTEGER PRIMARY KEY, user_id INTEGER, project_id INTEGER, contractnummer TEXT, klantnaam TEXT, depot_id INTEGER, created_at TEXT);
CREATE TABLE damage_items (id INTEGER PRIMARY KEY, damage_header_id INTEGER, machine_article_id INTEGER, qr_code TEXT, schade_type TEXT, voorgesteld_bedrag REAL, totaal_schadebedrag REAL, doorbelast_bedrag REAL, arbeidskosten REAL, onderdelenkosten REAL, inspectiekosten REAL, is_inhuur INTEGER DEFAULT 0, inhuur_leverancier TEXT, inhuur_leverancier_bedrag REAL, status TEXT, bd_status TEXT, na_deadline INTEGER DEFAULT 0);
INSERT INTO areas VALUES (1,'West'),(2,'Zuid'); INSERT INTO depots VALUES (1,'Industrial Rotterdam',1,1),(2,'Industrial Chemelot',2,1); INSERT INTO projects VALUES (1,'Shell Pernis TA','Shell');
INSERT INTO damage_headers (user_id,project_id,contractnummer,klantnaam,depot_id,created_at) VALUES (1,1,'K1','Shell',1,'$nu 10:00:00'),(1,NULL,'K2','BP',1,'$nu 11:00:00'),(1,NULL,'K3','Vopak',2,'$vorige 10:00:00');
INSERT INTO damage_items (damage_header_id,qr_code,schade_type,totaal_schadebedrag,doorbelast_bedrag,arbeidskosten,onderdelenkosten,bd_status) VALUES (1,'7590019510','Ruit',500,450,200,300,'Afgehandeld'),(2,'123','Band',300,NULL,100,200,'Nieuwe schade'),(3,'456','Deuk',250,NULL,50,200,'Geen schade');");
// Scanner
@unlink("$D/scanner.sorai.nl/public_html/data/scanner.db");
$s = new PDO("sqlite:$D/scanner.sorai.nl/public_html/data/scanner.db");
$s->exec("CREATE TABLE projects (id INTEGER PRIMARY KEY, project_name TEXT, is_active INTEGER); CREATE TABLE locations (id INTEGER PRIMARY KEY, location_number TEXT, location_name TEXT, project_id INTEGER, project_name TEXT, created_at TEXT);
CREATE TABLE location_items (id INTEGER PRIMARY KEY, location_id INTEGER, machine_number TEXT, description TEXT, quantity INTEGER DEFAULT 1, status TEXT, damaged INTEGER DEFAULT 0, placed_at TEXT, returned_at TEXT);
INSERT INTO projects VALUES (1,'Shell Pernis TA',1); INSERT INTO locations VALUES (1,'12','Poort A',1,'Shell Pernis TA','$nu'),(2,'H1','Tank 4',1,'Shell Pernis TA','$nu');
INSERT INTO location_items (location_id,machine_number,description,status,placed_at,returned_at) VALUES (1,'7590019510','Compressor','uitgezet','$nu 08:00:00',NULL),(1,'111','Lamp','terug','$vorige 08:00:00','$nu 09:00:00'),(2,'222','Pomp','uitgezet','$nu 08:00:00',NULL);");
// Voorraad
@unlink("$D/voorraad.sorai.nl/laravel_app/database/database.sqlite");
$v = new PDO("sqlite:$D/voorraad.sorai.nl/laravel_app/database/database.sqlite");
$v->exec("CREATE TABLE depots (id INTEGER PRIMARY KEY, naam TEXT, depot_nummer TEXT, nummer_core TEXT, area TEXT); CREATE TABLE uploads (id INTEGER PRIMARY KEY, type TEXT, actueel INTEGER, created_at TEXT);
CREATE TABLE materieel (id INTEGER PRIMARY KEY, upload_id INTEGER, uniek_nr TEXT, subgroep_nr TEXT, subgroep_naam TEXT, depot_nummer TEXT, depot_naam TEXT, status_code TEXT, laatste_uithuur TEXT);
CREATE TABLE reserveringen (id INTEGER PRIMARY KEY, upload_id INTEGER, depot_nummer TEXT, depot_naam TEXT, subgroep_nr TEXT, omschrijving TEXT, startdatum TEXT, aantal INTEGER);
CREATE TABLE aanvragen (id INTEGER PRIMARY KEY, depot_naam TEXT, aantal_machines INTEGER, status TEXT, created_at TEXT); CREATE TABLE min_voorraad (id INTEGER PRIMARY KEY, depot_nummer TEXT, subgroep_nr TEXT, subgroep_naam TEXT, minimum INTEGER);
CREATE TABLE order_regels (id INTEGER PRIMARY KEY, upload_id INTEGER, contract_nr TEXT, project_omschrijving TEXT, status_code TEXT, aantal INTEGER, verhuurdatum TEXT);
INSERT INTO depots VALUES (1,'Rotterdam-Europoort','759','759','West'),(2,'Geleen - Chemelot','384','384, 769','Zuid'); INSERT INTO uploads VALUES (1,'materieel',1,'$nu'),(2,'reserveringen',1,'$nu');
INSERT INTO materieel (upload_id,uniek_nr,subgroep_nr,subgroep_naam,depot_nummer,depot_naam,status_code,laatste_uithuur) VALUES (1,'A1','1234','Hoogwerker 12m','759','Rotterdam','available','2025-01-01'),(1,'A2','1234','Hoogwerker 12m','759','Rotterdam','on_hire',NULL),(1,'A3','5678','Compressor','384','Chemelot','available','$nu');
INSERT INTO min_voorraad VALUES (1,'759','5678','Compressor',2);");
echo "fixtures ok\n";
