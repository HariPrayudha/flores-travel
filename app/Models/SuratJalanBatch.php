<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalanBatch extends Model
{
    protected $fillable = [
        'user_id',
        'items_count',
        'total_ongkir',
        'total_lunas',
        'total_belum_bayar',
        'total_transfer',
        'items',
        'pdf_path'
    ];
    protected $casts = [
        'items' => 'array',
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'surat_jalan_batch_id');
    }
}
