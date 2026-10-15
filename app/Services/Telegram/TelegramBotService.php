<?php

namespace App\Services\Telegram;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    public function __construct(
        protected TelegramClient $client
    ) {}

    /**
     * Entry point untuk memproses pesan Telegram yang masuk.
     */
    public function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        $messageId = $message['message_id'] ?? null;

        if (!$chatId || empty($text)) {
            return;
        }

        $user = $this->resolveUser($message);

        // Command Routing
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($user, $chatId, $text);
        } elseif (str_starts_with($text, '/saldo')) {
            $this->handleSaldoCommand($user, $chatId);
        } elseif (str_starts_with($text, '/catat')) {
            $this->handleCatatCommand($user, $chatId, $text, $messageId, 'catat');
        } elseif (str_starts_with($text, '/keluar')) {
            $this->handleCatatCommand($user, $chatId, $text, $messageId, 'expense');
        } elseif (str_starts_with($text, '/masuk')) {
            $this->handleCatatCommand($user, $chatId, $text, $messageId, 'income');
        } elseif (str_starts_with($text, '/laporan') || str_starts_with($text, '/rekap')) {
            $this->handleLaporanCommand($user, $chatId);
        } elseif (str_starts_with($text, '/help')) {
            $this->handleHelpCommand($chatId);
        } else {
            // Jika pesan dimulai dengan angka/nominal langsung, misal "50000 makan"
            if (preg_match('/^(\+?[0-9]+[a-zA-Z\.,]*)\s*(.*)/', $text)) {
                $this->handleCatatCommand($user, $chatId, '/catat ' . $text, $messageId, 'catat');
            } else {
                $this->client->sendMessage(
                    $chatId,
                    "❓ <i>Perintah tidak dikenali.</i>\n\nKetik /help untuk melihat panduan cara mencatat keuangan kilat."
                );
            }
        }
    }

    /**
     * Memproses callback query dari inline button (Undo / Ubah Kategori).
     */
    public function handleCallbackQuery(array $callbackQuery): void
    {
        $queryId = $callbackQuery['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        if (!$queryId || !$chatId) {
            return;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->client->answerCallbackQuery($queryId, 'User tidak ditemukan.', true);
            return;
        }

        if (str_starts_with($data, 'undo_trx_')) {
            $trxId = (int) substr($data, 9);
            $this->handleUndoTransaction($user, $chatId, $messageId, $queryId, $trxId);
        }
    }

    /**
     * Resolve atau inisialisasi akun user berdasarkan telegram_chat_id.
     */
    protected function resolveUser(array $message): User
    {
        $chatId = $message['chat']['id'];
        $username = $message['from']['username'] ?? null;
        $firstName = $message['from']['first_name'] ?? 'User';

        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $user = User::create([
                'name' => $firstName,
                'email' => "tg_{$chatId}@keuangankita.local",
                'password' => bcrypt(str()->random(16)),
                'telegram_chat_id' => $chatId,
                'telegram_username' => $username,
            ]);

            // Inisialisasi Dompet Utama
            $user->wallets()->create([
                'name' => 'Dompet Utama',
                'type' => 'cash',
                'balance' => 0,
                'is_default' => true,
            ]);

            // Inisialisasi Kategori Dasar
            $defaultCategories = [
                ['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => '🍔', 'color' => '#f59e0b'],
                ['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🛵', 'color' => '#3b82f6'],
                ['name' => 'Belanja Harian', 'type' => 'expense', 'icon' => '🛒', 'color' => '#ec4899'],
                ['name' => 'Gaji & Bonus', 'type' => 'income', 'icon' => '💵', 'color' => '#10b981'],
            ];

            foreach ($defaultCategories as $cat) {
                $user->categories()->create($cat);
            }
        } elseif ($username && $user->telegram_username !== $username) {
            $user->update(['telegram_username' => $username]);
        }

        return $user;
    }

    /**
     * Handler perintah /start
     */
    protected function handleStartCommand(User $user, int $chatId, string $text): void
    {
        // Cek jika terdapat parameter token pairing, e.g. /start link_token_abc
        $parts = explode(' ', $text);
        if (isset($parts[1]) && !empty($parts[1])) {
            $token = trim($parts[1]);
            $targetUser = User::where('link_token', $token)
                ->where('link_token_expires_at', '>', now())
                ->first();

            if ($targetUser) {
                $targetUser->update([
                    'telegram_chat_id' => $chatId,
                    'link_token' => null,
                    'link_token_expires_at' => null,
                ]);

                $this->client->sendMessage(
                    $chatId,
                    "🎉 <b>Akun Berhasil Ditautkan!</b>\n\nSelamat datang, <b>" . htmlspecialchars($targetUser->name) . "</b>! Akun web Anda kini telah terhubung dengan Telegram Bot Keuangan Kita."
                );
                return;
            }
        }

        $msg = "👋 <b>Halo, " . htmlspecialchars($user->name) . "!</b>\n\n"
             . "Selamat datang di <b>Keuangan Kita</b> — Asisten pencatat keuangan kilat Anda!\n\n"
             . "⚡ <b>Cara Penggunaan Cepat:</b>\n"
             . "• <code>/catat 50000 makan siang</code> (Pengeluaran)\n"
             . "• <code>/keluar 25k kopi susu</code> (Pengeluaran)\n"
             . "• <code>/masuk 1.5jt bonus proyek</code> (Pemasukan)\n"
             . "• <code>/saldo</code> (Lihat sisa uang di semua dompet)\n"
             . "• <code>/laporan</code> (Rekapitulasi keuangan bulan ini)\n\n"
             . "Setiap transaksi otomatis tercatat dengan tanggal & jam saat ini.";

        $this->client->sendMessage($chatId, $msg);
    }

    /**
     * Handler pencatatan transaksi (/catat, /keluar, /masuk)
     */
    protected function handleCatatCommand(User $user, int $chatId, string $text, ?int $messageId, string $forcedType = 'catat'): void
    {
        // Pisahkan command dari argumen
        $args = trim(preg_replace('/^\/(catat|keluar|masuk)\s*/i', '', $text));

        if (empty($args)) {
            $this->client->sendMessage(
                $chatId,
                "⚠️ <b>Format Belum Lengkap!</b>\n\nContoh penggunaan:\n<code>/catat 50000 makan sate</code>\natau\n<code>/keluar 25k kopi susu</code>"
            );
            return;
        }

        // Pisahkan nominal dan keterangan (misal: "50000 makan sate")
        if (!preg_match('/^(\+?[^\s]+)\s*(.*)$/s', $args, $matches)) {
            $this->client->sendMessage($chatId, "⚠️ Gagal membaca nominal. Gunakan format: <code>/catat 50000 makan</code>");
            return;
        }

        $rawAmount = $matches[1];
        $notes = trim($matches[2] ?? '');

        // Tentukan tipe transaksi
        $type = 'expense';
        if ($forcedType === 'income' || str_starts_with($rawAmount, '+')) {
            $type = 'income';
        } elseif ($forcedType === 'expense') {
            $type = 'expense';
        }

        $amount = AmountParser::parse($rawAmount);
        if (!$amount || $amount <= 0) {
            $this->client->sendMessage($chatId, "❌ Nominal tidak valid. Contoh format: <code>50000</code>, <code>50k</code>, <code>50rb</code>, <code>1.5jt</code>.");
            return;
        }

        if (empty($notes)) {
            $notes = $type === 'income' ? 'Pemasukan' : 'Pengeluaran';
        }

        // Cari kategori yang cocok secara cerdas berdasarkan kata kunci
        $category = $this->guessCategory($user, $notes, $type);

        // Eksekusi transaksi atomik dengan locking
        $transaction = DB::transaction(function () use ($user, $amount, $type, $notes, $category, $messageId) {
            // Ambil dompet utama user dengan pessimistic lock
            $wallet = $user->wallets()
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = $user->wallets()->lockForUpdate()->first();
            }

            if ($type === 'expense') {
                $wallet->decrement('balance', $amount);
            } else {
                $wallet->increment('balance', $amount);
            }

            return $user->transactions()->create([
                'wallet_id' => $wallet->id,
                'category_id' => $category?->id,
                'type' => $type,
                'amount' => $amount,
                'notes' => strip_tags($notes),
                'source' => 'telegram_bot',
                'external_message_id' => $messageId ? (string) $messageId : null,
                'transacted_at' => now(),
            ]);
        });

        // Ambil saldo dompet terbaru setelah mutasi
        $updatedWallet = $transaction->wallet()->first();

        $badge = $type === 'income' ? '🟢 <b>Pemasukan Dicatat!</b>' : '🔴 <b>Pengeluaran Dicatat!</b>';
        $formattedAmount = 'Rp ' . number_format($amount, 0, ',', '.');
        $formattedBalance = 'Rp ' . number_format($updatedWallet->balance, 0, ',', '.');
        $catName = $category ? ($category->icon ? "{$category->icon} " : '') . $category->name : 'Umum';

        $receipt = "{$badge}\n\n"
                 . "💵 <b>Nominal:</b> {$formattedAmount}\n"
                 . "🏷 <b>Kategori:</b> " . htmlspecialchars($catName) . "\n"
                 . "👛 <b>Dompet:</b> " . htmlspecialchars($updatedWallet->name) . "\n"
                 . "📝 <b>Catatan:</b> " . htmlspecialchars($transaction->notes) . "\n"
                 . "📅 <b>Waktu:</b> " . $transaction->transacted_at->translatedFormat('d M Y, H:i') . " WIB\n"
                 . "━━━━━━━━━━━━━━━━━━━\n"
                 . "💳 <b>Sisa Saldo:</b> {$formattedBalance}";

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '🗑 Batalkan Transaksi', 'callback_data' => 'undo_trx_' . $transaction->id],
                ]
            ]
        ];

        $this->client->sendMessage($chatId, $receipt, $replyMarkup);
    }

    /**
     * Handler pembatalan transaksi (Undo)
     */
    protected function handleUndoTransaction(User $user, int $chatId, ?int $messageId, string $queryId, int $trxId): void
    {
        $result = DB::transaction(function () use ($user, $trxId) {
            $transaction = $user->transactions()
                ->where('id', $trxId)
                ->first();

            if (!$transaction) {
                return false;
            }

            $wallet = $transaction->wallet()->lockForUpdate()->first();

            // Kembalikan saldo
            if ($transaction->type === 'expense') {
                $wallet->increment('balance', $transaction->amount);
            } else {
                $wallet->decrement('balance', $transaction->amount);
            }

            $amount = $transaction->amount;
            $transaction->delete();

            return $amount;
        });

        if ($result === false) {
            $this->client->answerCallbackQuery($queryId, 'Transaksi tidak ditemukan atau sudah dibatalkan.', true);
            return;
        }

        $this->client->answerCallbackQuery($queryId, '✅ Transaksi berhasil dibatalkan!');

        if ($messageId) {
            $this->client->editMessageText(
                $chatId,
                $messageId,
                "<s>[Transaksi Dibatalkan]</s>\n\n✅ <b>Transaksi senilai Rp " . number_format($result, 0, ',', '.') . " telah dibatalkan</b> dan saldo dompet Anda telah dikembalikan."
            );
        }
    }

    /**
     * Handler cek saldo (/saldo)
     */
    protected function handleSaldoCommand(User $user, int $chatId): void
    {
        $wallets = $user->wallets()->orderBy('is_default', 'desc')->get();
        $total = $wallets->sum('balance');

        $text = "💼 <b>Ringkasan Saldo Anda</b>\n\n";

        foreach ($wallets as $wallet) {
            $defaultBadge = $wallet->is_default ? ' ⭐ (Utama)' : '';
            $icon = match ($wallet->type) {
                'bank' => '🏦',
                'ewallet' => '📱',
                default => '💵',
            };
            $text .= "{$icon} <b>" . htmlspecialchars($wallet->name) . "{$defaultBadge}:</b> Rp " . number_format($wallet->balance, 0, ',', '.') . "\n";
        }

        $text .= "━━━━━━━━━━━━━━━━━━━\n"
               . "💰 <b>Total Seluruh Dompet:</b> <b>Rp " . number_format($total, 0, ',', '.') . "</b>\n\n"
               . "<i>Ketik <code>/catat [nominal] [ket]</code> untuk mencatat transaksi baru.</i>";

        $this->client->sendMessage($chatId, $text);
    }

    /**
     * Handler laporan & rekap bulanan (/laporan atau /rekap)
     */
    protected function handleLaporanCommand(User $user, int $chatId): void
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $transactions = $user->transactions()
            ->whereBetween('transacted_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $netSavings = $totalIncome - $totalExpense;

        // Top 3 Expense Categories
        $topCategories = $transactions->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'category' => $items->first()->category?->name ?? 'Lain-lain',
                    'icon' => $items->first()->category?->icon ?? '🏷',
                    'amount' => $items->sum('amount'),
                ];
            })
            ->sortByDesc('amount')
            ->take(3);

        $monthName = $now->translatedFormat('F Y');
        $savingsSign = $netSavings >= 0 ? '+Rp ' : '-Rp ';
        $savingsStatus = $netSavings >= 0 ? '(Surplus)' : '(Defisit)';

        $report = "📊 <b>Rekapitulasi Keuangan — {$monthName}</b>\n\n"
                . "📈 <b>Total Pemasukan:</b> Rp " . number_format($totalIncome, 0, ',', '.') . "\n"
                . "📉 <b>Total Pengeluaran:</b> Rp " . number_format($totalExpense, 0, ',', '.') . "\n"
                . "━━━━━━━━━━━━━━━━━━━\n"
                . "💵 <b>Net Tabungan:</b> <b>{$savingsSign}" . number_format(abs($netSavings), 0, ',', '.') . "</b> {$savingsStatus}\n\n";

        if ($topCategories->isNotEmpty()) {
            $report .= "🏷 <b>Pengeluaran Terbesar Bulan Ini:</b>\n";
            $rank = 1;
            foreach ($topCategories as $item) {
                $pct = $totalExpense > 0 ? round(($item['amount'] / $totalExpense) * 100) : 0;
                $report .= "{$rank}. {$item['icon']} {$item['category']}: Rp " . number_format($item['amount'], 0, ',', '.') . " ({$pct}%)\n";
                $rank++;
            }
            $report .= "\n";
        }

        $report .= "🌐 <i>Lihat grafik visual detail di Web Dashboard Keuangan Kita.</i>";

        $this->client->sendMessage($chatId, $report);
    }

    /**
     * Handler bantuan (/help)
     */
    protected function handleHelpCommand(int $chatId): void
    {
        $help = "📖 <b>Panduan Penggunaan Bot Keuangan Kita:</b>\n\n"
              . "<b>1. Mencatat Pengeluaran:</b>\n"
              . "• <code>/catat 50000 makan siang</code>\n"
              . "• <code>/keluar 25k boba tea</code>\n"
              . "• <code>50000 bensin</code> (bisa tanpa slash)\n\n"
              . "<b>2. Mencatat Pemasukan:</b>\n"
              . "• <code>/masuk 5000000 gaji bulanan</code>\n"
              . "• <code>/catat +1.5jt bonus proyek</code>\n\n"
              . "<b>3. Cek Saldo & Laporan:</b>\n"
              . "• <code>/saldo</code> : Cek sisa saldo seluruh dompet\n"
              . "• <code>/laporan</code> : Rekap pemasukan & pengeluaran bulan ini\n\n"
              . "<i>Semua transaksi otomatis tersimpan dengan waktu saat Anda mengirimkan pesan.</i>";

        $this->client->sendMessage($chatId, $help);
    }

    /**
     * Tebak kategori berdasarkan kata kunci di catatan transaksi.
     */
    protected function guessCategory(User $user, string $notes, string $type): ?Category
    {
        $lowerNotes = strtolower($notes);

        if ($type === 'income') {
            return $user->categories()->where('type', 'income')->first();
        }

        // Keyword mapping
        $keywords = [
            'makan' => ['makan', 'nasi', 'kopi', 'minum', 'sate', 'bakso', 'ayam', 'mie', 'snack', 'jajan', 'resto', 'cafe'],
            'transport' => ['bensin', 'pertalite', 'pertamax', 'ojol', 'grab', 'gojek', 'parkir', 'tol', 'kereta', 'krl', 'mrt'],
            'belanja' => ['belanja', 'indomaret', 'alfamart', 'supermarket', 'shopee', 'tokopedia', 'lazada', 'baju', 'sabun'],
        ];

        foreach ($keywords as $categoryType => $words) {
            foreach ($words as $word) {
                if (str_contains($lowerNotes, $word)) {
                    $matched = $user->categories()
                        ->where('type', 'expense')
                        ->where('name', 'like', "%{$categoryType}%")
                        ->first();
                    if ($matched) {
                        return $matched;
                    }
                }
            }
        }

        // Default: ambil kategori pengeluaran pertama milik user
        return $user->categories()->where('type', 'expense')->first();
    }
}
