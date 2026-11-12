<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAP-exports centraal in CORE (zie config/sap.php en docs/sap-uploads.md).
 * Patroon: één rij in sap_uploads per upload (snapshot) + de regels van die
 * snapshot; handmatige velden staan los op contract-/ordernummer, zodat ze
 * elke nieuwe upload overleven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_uploads', function (Blueprint $t) {
            $t->id();
            $t->string('soort', 30)->index();            // wow | factuurrun | planning | reserveringen
            $t->string('bestandsnaam');
            $t->date('rapportdatum')->nullable();        // afgeleid uit de data (zie importer)
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('regels')->default(0);
            $t->unsignedInteger('overgeslagen')->default(0);
            $t->unsignedInteger('contracten')->default(0);
            $t->string('hash', 64)->index();
            $t->json('samenvatting')->nullable();        // totalen voor trends, blijft staan als regels zijn opgeruimd
            $t->boolean('regels_opgeruimd')->default(false);
            $t->timestamps();
        });

        // WOW — alles wat in huur staat, één regel per artikel per contract
        Schema::create('hire_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('depot', 10)->index();
            $t->string('contract_number', 20)->index();
            $t->string('customer_number', 30)->nullable()->index();
            $t->string('customer_name')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->string('project_number', 30)->nullable()->index();
            $t->string('project_description')->nullable();
            $t->string('item_number', 30)->index();
            $t->unsignedBigInteger('machine_id')->nullable()->index();
            $t->string('item_description')->nullable();
            $t->decimal('qty_out', 10, 2)->default(1);
            $t->date('hire_date')->nullable();
            $t->date('delivery_date')->nullable();
            $t->date('est_return_date')->nullable();     // NULL = geen einddatum bekend
            $t->date('coll_date')->nullable();
            $t->string('coll_time', 10)->nullable();
            $t->boolean('coll_doc_printed')->default(false);
            $t->string('status', 40)->index();           // On-Hire/Delivered | On-Hire - To Collect | Off-Hire Not Confirmed
            $t->decimal('week_rate', 10, 2)->default(0);
            $t->decimal('discount', 6, 2)->default(0);
            $t->decimal('weekly_hire_rate', 10, 2)->default(0);
            $t->decimal('waiver', 8, 2)->default(0);
            $t->string('currency', 5)->nullable();
            $t->string('your_reference')->nullable();
            $t->string('delivery_name')->nullable();
            $t->string('delivery_address')->nullable();
            $t->string('delivery_postal_code', 20)->nullable();
            $t->text('notes')->nullable();
            $t->string('exdp', 10)->nullable();
            $t->string('cid', 10)->nullable();
            $t->index(['upload_id', 'contract_number']);
            $t->index(['upload_id', 'status']);
        });

        // Factuurrun — contracten/regels die niet in de facturatierun zitten
        Schema::create('invoice_run_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('contract_number', 20)->index();
            $t->string('customer_number', 30)->nullable()->index();
            $t->string('customer_name')->nullable();
            $t->string('item_number', 30)->nullable();
            $t->string('item_description')->nullable();
            $t->decimal('quantity', 10, 2)->default(1);
            $t->string('item_status', 40)->nullable();
            $t->date('delivery_date')->nullable();
            $t->date('hire_start_date')->nullable();
            $t->date('hire_end_date')->nullable();        // NULL = geen einde bekend
            $t->date('item_hire_start_date')->nullable();
            $t->date('coll_date')->nullable();
            $t->string('type', 30)->nullable();
            $t->date('last_invoice_date')->nullable();   // NULL = nog nooit gefactureerd
            $t->string('delivery_name')->nullable();
            $t->string('street')->nullable();
            $t->string('city')->nullable();
            $t->string('ordered_by')->nullable();
            $t->string('customer_reference')->nullable();
            $t->string('created_by_code', 20)->nullable()->index();
            $t->index(['upload_id', 'contract_number']);
        });

        // Handmatig per contract: reden + status, overleeft uploads
        Schema::create('invoice_run_notes', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number', 20)->unique();
            $t->text('reden')->nullable();
            $t->string('status', 20)->default('te_controleren'); // te_controleren | verklaard | opgelost
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('eerst_gezien')->nullable();
            $t->date('laatst_gezien')->nullable();
            $t->date('verdwenen_op')->nullable();        // niet meer in de laatste upload
            $t->timestamps();
        });

        // Planning (8.7 View) — één rij per order
        Schema::create('planning_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('order_number', 20)->index();
            $t->string('type_raw', 40)->nullable();
            $t->string('soort', 20)->index();            // huur | verkoop | transfer_in | transfer_uit | retour | overig
            $t->string('customer_name')->nullable();
            $t->string('contract_status', 40)->nullable();
            $t->decimal('picked', 10, 2)->default(0);
            $t->decimal('total', 10, 2)->default(0);
            $t->string('location')->nullable();
            $t->string('del_memo')->nullable();
            $t->string('depot', 10)->nullable();
            $t->string('hiring_depot')->nullable();
            $t->string('reference')->nullable();
            $t->string('sales_person')->nullable();
            $t->unsignedBigInteger('employee_id')->nullable()->index();
            $t->date('delivery_date')->nullable()->index();
            $t->index(['upload_id', 'order_number']);
        });

        // Handmatig per order: expeditiekolom + opmerking
        Schema::create('planning_notes', function (Blueprint $t) {
            $t->id();
            $t->string('order_number', 20)->unique();
            $t->unsignedBigInteger('expeditie_employee_id')->nullable();
            $t->text('opmerking')->nullable();
            $t->boolean('vlag')->default(false);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        // Reserveringen (2.6) — offertes + toekomstige contracten met regels
        Schema::create('reservation_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('upload_id')->constrained('sap_uploads')->cascadeOnDelete();
            $t->string('contract_number', 20)->index();
            $t->string('customer_number', 30)->nullable();
            $t->string('customer_name')->nullable();
            $t->boolean('is_contract')->default(true);   // kolom H: Ingegeven = contract, Offerte = offerte
            $t->string('offerte_status', 30)->nullable(); // kolom G
            $t->string('contract_status', 30)->nullable();
            $t->string('item_number', 30)->nullable();
            $t->string('item_description')->nullable();
            $t->decimal('qty', 10, 2)->default(1);
            $t->date('delivery_date')->nullable();
            $t->date('hire_start_date')->nullable();
            $t->date('hire_end_date')->nullable();
            $t->string('hire_end_time', 10)->nullable();
            $t->boolean('transport')->default(false);
            $t->boolean('unconfirmed_collection')->default(false);
            $t->string('type', 30)->nullable();
            $t->string('delivery_name')->nullable();
            $t->string('street')->nullable();
            $t->string('city')->nullable();
            $t->string('ordered_by')->nullable();
            $t->string('customer_reference')->nullable();
            $t->decimal('goods_value', 12, 2)->default(0);
            $t->string('created_by_code', 20)->nullable()->index();
            $t->index(['upload_id', 'contract_number']);
        });

        // SAP-gebruikerscode (BERGEW) of naam zoals SAP hem schrijft → medewerker
        Schema::create('sap_user_codes', function (Blueprint $t) {
            $t->id();
            $t->string('code', 60)->unique();            // hoofdletters; bij namen: de genormaliseerde naam
            $t->string('weergave')->nullable();          // zoals in het bestand
            $t->unsignedBigInteger('employee_id')->nullable()->index();
            $t->string('bron', 20)->default('auto');     // auto | handmatig | onbekend
            $t->unsignedInteger('gezien')->default(0);   // hoe vaak in uploads voorgekomen
            $t->timestamp('laatst_gezien_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sap_user_codes', 'reservation_lines', 'planning_notes', 'planning_orders', 'invoice_run_notes', 'invoice_run_lines', 'hire_lines', 'sap_uploads'] as $tabel) {
            Schema::dropIfExists($tabel);
        }
    }
};
