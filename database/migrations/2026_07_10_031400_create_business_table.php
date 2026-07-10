<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->tinyInteger('business_type')->comment('1=company,2=partnership,3=individual');
            $table->string('business_tax_no', 13)->nullable();
            $table->tinyInteger('business_vat_status');
            $table->string('business_vat_file')->nullable();

            $table->string('business_code')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_address1')->nullable();
            $table->string('business_address2')->nullable();
            $table->tinyInteger('business_branch_status');
            $table->tinyInteger('business_branch_no')->nullable();
            $table->string('business_branch_name')->nullable();

            $table->tinyInteger('business_en_status')->nullable();
            $table->string('business_name_en')->nullable();
            $table->string('business_address1_en')->nullable();
            $table->string('business_address2_en')->nullable();
            $table->tinyInteger('business_branch_no_en')->nullable();
            $table->string('business_branch_name_en')->nullable();

            $table->tinyInteger('business_account_finance_year');
            $table->tinyInteger('business_business_finance_year');

            $table->string('business_tel', 255)->nullable();
            $table->string('business_phone', 255)->nullable();
            $table->string('business_fax')->nullable();
            $table->string('business_website')->nullable();

            $table->string('business_logo')->nullable();
            $table->string('business_stamp')->nullable();

            $table->longText('attach_qt')->nullable();
            $table->longText('attach_qt_en')->nullable();
            $table->longText('attach_po')->nullable();
            $table->longText('attach_po_en')->nullable();

            $table->tinyInteger('business_status')->default(1);
            $table->boolean('allow_issue')->default(false);
            $table->string('business_payment_date')->nullable();
            $table->string('business_payment_day')->nullable();
            $table->string('business_payment_month')->nullable();

            $table->timestamps();

            $table->decimal('sales_target_amount', 14, 2)->default(1000000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business');
    }
};
