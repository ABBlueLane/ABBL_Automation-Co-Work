<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'business_type',
    'business_tax_no',
    'business_vat_status',
    'business_vat_file',
    'business_code',
    'business_name',
    'business_address1',
    'business_address2',
    'business_branch_status',
    'business_branch_no',
    'business_branch_name',
    'business_name_en',
    'business_address1_en',
    'business_address2_en',
    'business_branch_no_en',
    'business_branch_name_en',
    'business_account_finance_year',
    'business_business_finance_year',
    'business_tel',
    'business_phone',
    'business_fax',
    'business_website',
    'business_logo',
    'business_stamp',
    'attach_qt',
    'attach_qt_en',
    'attach_po',
    'attach_po_en',
    'business_status',
    'allow_issue',
    'business_payment_date',
    'business_payment_day',
    'business_payment_month',
    'sales_target_amount',
])]
class Business extends Model
{
    use HasUuids;

    protected $table = 'business';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'allow_issue' => 'boolean',
            'sales_target_amount' => 'decimal:2',
        ];
    }
}
