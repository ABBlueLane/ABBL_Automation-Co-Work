<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessSeeder extends Seeder
{
    /**
     * Seed the application's business data.
     */
    public function run(): void
    {
        $business = Business::query()->firstOrCreate(
            ['business_code' => 'ABBL'],
            ['id' => (string) Str::uuid()]
        );

        $business->update([
            'business_type' => 1,
            'business_tax_no' => '0100000000000',
            'business_vat_status' => 1,
            'business_vat_file' => null,
            'business_name' => 'ABBL Automation Co-Work',
            'business_address1' => 'Bangkok',
            'business_address2' => null,
            'business_branch_status' => 1,
            'business_branch_no' => 0,
            'business_branch_name' => 'สำนักงานใหญ่',
            'business_en_status' => 1,
            'business_name_en' => 'ABBL Automation Co-Work',
            'business_address1_en' => 'Bangkok',
            'business_address2_en' => null,
            'business_branch_no_en' => 0,
            'business_branch_name_en' => 'Head Office',
            'business_account_finance_year' => 12,
            'business_business_finance_year' => 12,
            'business_tel' => '020000000',
            'business_phone' => '020000000',
            'business_fax' => null,
            'business_website' => null,
            'business_logo' => null,
            'business_stamp' => null,
            'attach_qt' => null,
            'attach_qt_en' => null,
            'attach_po' => null,
            'attach_po_en' => null,
            'business_status' => 1,
            'allow_issue' => true,
            'business_payment_date' => null,
            'business_payment_day' => null,
            'business_payment_month' => null,
            'sales_target_amount' => 1000000,
        ]);
    }
}
