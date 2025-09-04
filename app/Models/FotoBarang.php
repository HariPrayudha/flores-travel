<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoBarang extends Model
{
    /** @use HasFactory<\Database\Factories\FotoBarangFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function barang(): BelongsTo{
        return $this->belongsTo(Barang::class);
    }
}
