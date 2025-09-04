<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barang extends Model
{
    /** @use HasFactory<\Database\Factories\BarangFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function fotoBarang(): HasMany{
        return $this->hasMany(FotoBarang::class);
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function requestUpdate(): HasOne{
        return $this->hasOne(RequestUpdateBarang::class);
    }
}
