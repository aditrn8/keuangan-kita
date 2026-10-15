---
name: secure-crud-sanitizer
description: Security standards, input sanitization, and data integrity practices for financial CRUD operations in Laravel. Use when creating or refactoring models, controllers, form requests, database transactions, and money calculations.
allowed-tools: ["*"]
metadata:
  author: Keuangan Kita Team
  version: "1.0"
---

# Secure CRUD Sanitizer Skill (Laravel Financial Security)

Skill ini menjamin seluruh operasi CRUD finansial di aplikasi **Keuangan Kita** mematuhi standar keamanan tingkat tinggi, bebas dari kerentanan umum (IDOR, Mass Assignment, SQL Injection, Race Conditions, dan Floating Point Rounding Error).

---

## 1. Aturan Emas Integritas Data Keuangan

### ❌ Dilarang: Floating Point (`float`, `double`)
```php
// SALAH: Rawan rounding error (0.1 + 0.2 != 0.3)
$balance = 100000.50; // Float
```

### ✅ Wajib: Integer (Satuan Rupiah) atau Decimal(15,2)
```php
// BENAR: Menggunakan integer langsung (Rupiah utuh tanpa sen) atau sen (cents)
$table->unsignedBigInteger('amount'); // contoh: 50000 untuk Rp 50.000
// ATAU jika mendukung pecahan sen:
$table->decimal('amount', 15, 2);
```

### ✅ Wajib: Atomic Transactions & Concurrency Locking
Saat mengubah saldo dompet (pengurangan/penambahan saldo), WAJIB menggunakan `DB::transaction()` dan pessimistic locking (`lockForUpdate`):

```php
use Illuminate\Support\Facades\DB;

public function recordExpense(User $user, int $walletId, int $amount, int $categoryId, string $notes): Transaction
{
    return DB::transaction(function () use ($user, $walletId, $amount, $categoryId, $notes) {
        // Kunci baris wallet untuk mencegah race condition
        $wallet = $user->wallets()->lockForUpdate()->findOrFail($walletId);

        if ($wallet->balance < $amount) {
            throw new InsufficientBalanceException("Saldo dompet {$wallet->name} tidak mencukupi.");
        }

        // Potong saldo
        $wallet->decrement('balance', $amount);

        // Buat record transaksi
        return $wallet->transactions()->create([
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'type' => 'expense',
            'amount' => $amount,
            'notes' => strip_tags(trim($notes)),
            'transacted_at' => now(),
        ]);
    });
}
```

---

## 2. Pencegahan IDOR (Insecure Direct Object Reference)

Pengguna **TIDAK BOLEH** bisa melihat, mengedit, atau menghapus transaksi/dompet milik pengguna lain.

### Aturan 1: Selalu Scope Query Lewat Relasi User
```php
// SALAH: Membuka celah IDOR jika ID ditebak oleh penyerang
$transaction = Transaction::findOrFail($id);
$transaction->delete();

// BENAR: Selalu query melalui user yang terautentikasi
$transaction = $request->user()->transactions()->findOrFail($id);
$transaction->delete();
```

### Aturan 2: Gunakan Laravel Policy untuk Resource Controllers
Setiap model finansial (`Wallet`, `Category`, `Transaction`, `Budget`) wajib memiliki Policy:

```php
// app/Policies/TransactionPolicy.php
public function update(User $user, Transaction $transaction): bool
{
    return $user->id === $transaction->user_id;
}

public function delete(User $user, Transaction $transaction): bool
{
    return $user->id === $transaction->user_id;
}

// Di Controller:
public function destroy(Request $request, Transaction $transaction)
{
    $this->authorize('delete', $transaction);
    $transaction->delete();
    return response()->json(['message' => 'Transaksi berhasil dihapus']);
}
```

---

## 3. Strict FormRequest Validation & Sanitasi Input

Semua input HTTP dan Telegram Webhook wajib melalui validasi ketat.

### Template: `StoreTransactionRequest.php`

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorize ditangani oleh auth middleware / policies
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'type' => ['required', 'string', Rule::in(['income', 'expense', 'transfer'])],
            'wallet_id' => [
                'required',
                'integer',
                // Pastikan wallet milik user yang bersangkutan
                Rule::exists('wallets', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'required',
                'integer',
                // Pastikan kategori milik user atau kategori bawaan sistem
                Rule::exists('categories', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)->orWhereNull('user_id');
                }),
            ],
            'amount' => ['required', 'integer', 'min:100', 'max:1000000000000'], // Batas nominal masuk akal
            'notes' => ['nullable', 'string', 'max:255'],
            'transacted_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Sanitasi data sebelum atau sesudah validasi
     */
    protected function passedValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags(trim($this->input('notes'))),
            ]);
        }
    }
}
```

---

## 4. Proteksi Mass Assignment

Jangan pernah menggunakan `$guarded = [];` pada model finansial. Selalu definisikan `$fillable` secara eksplisit:

```php
// app/Models/Transaction.php
class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'notes',
        'transacted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transacted_at' => 'datetime',
    ];
}
```

---

## 5. Rate Limiting & Anti-Bruteforce

Terapkan rate limiting pada route API dan Webhook untuk mencegah spam atau serangan DoS:

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    Route::post('/transactions', [TransactionController::class, 'store']);
});

// Telegram Webhook Rate Limiting
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:60,1'); // Max 60 request per menit per IP
```

---

## 6. Checklist Verifikasi Keamanan Sebelum Commit

- [ ] Seluruh kalkulasi saldo menggunakan `integer` / `decimal(15,2)` (no float).
- [ ] Mutasi saldo dibungkus `DB::transaction()` dan `lockForUpdate()`.
- [ ] Foreign keys (`wallet_id`, `category_id`) divalidasi kepemilikannya ke `auth()->id()`.
- [ ] Query penghapusan & update menggunakan Policy atau scoping `$user->transactions()`.
- [ ] `$fillable` didefinisikan secara eksplisit.
- [ ] String input disanitasi (`strip_tags` / `htmlspecialchars`).
- [ ] Rate limiting aktif pada routes sensitif.
