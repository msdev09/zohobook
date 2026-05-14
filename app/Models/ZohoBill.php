<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZohoBill extends Model
{
    protected $guarded = [];
    protected $primaryKey = 'bill_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function attachments()
    {
        return $this->morphMany(ZohoAttachment::class, 'attachable');
    }
}
