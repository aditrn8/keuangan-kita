<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'notes',
        'source',
        'external_message_id',
        'transacted_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'transacted_at' => 'datetime',
    ];

    /**
     * Boot model untuk memastikan auto-timestamping jika tidak diinput manual.
     */
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->transacted_at)) {
                $transaction->transacted_at = now();
            }
        });
    }

    /**
     * Relasi ke User pemilik transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Wallet sumber/tujuan transaksi.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Relasi ke Category pengeluaran/pemasukan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
