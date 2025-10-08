<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangExport extends Model
{
    protected $fillable = [
        'kode',
        'user_id',
        'exported_at',
        'jumlah_item',
        'total_ongkir',
        'total_lunas',
        'total_belum_bayar',
        'total_transfer',
        'keterangan'
    ];

    public function items()
    {
        return $this->hasMany(BarangExportItem::class, 'export_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
