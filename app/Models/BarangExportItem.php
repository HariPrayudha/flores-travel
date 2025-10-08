<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangExportItem extends Model
{
    protected $fillable = [
        'export_id',
        'barang_id',
        'kode_barang',
        'nama_penerima',
        'paket_antar',
        'tujuan_text',
        'deskripsi_barang',
        'ongkos_kirim',
        'status_bayar'
    ];

    public function export()
    {
        return $this->belongsTo(BarangExport::class, 'export_id');
    }
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
