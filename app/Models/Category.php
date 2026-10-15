<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
    ];

    /**
     * Relasi ke pemilik kategori (User), nullable untuk kategori bawaan sistem.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke transaksi yang menggunakan kategori ini.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
