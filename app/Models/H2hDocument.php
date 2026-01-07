<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class H2hDocument extends Model
{
    protected $table = 'h2h_documents';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'service_id',
        'environment',
        'contrato',
        'convenio',
        'netsuite_id',
        'filename',
        'filepath',
        'status',
        'encoded',
        'raw',
        'decoded',
        'string_date',
        'invoice',
        'sended_at',
        'documentable_id', 
        'documentable_type',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}
