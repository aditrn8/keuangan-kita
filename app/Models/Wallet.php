<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance',
        'is_default',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Relasi ke pemilik dompet (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke transaksi yang dicatat di dompet ini.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
