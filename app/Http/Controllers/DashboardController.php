<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ambil user yang dipilih via query parameter, atau prioritaskan akun Telegram yang baru aktif
        $userId = $request->query('user_id');
        $user = null;

        if ($userId) {
            $user = User::find($userId);
        }

        if (!$user) {
            $user = User::where('email', 'like', 'tg_%')->has('transactions')->latest('id')->first()
                ?? User::has('transactions')->latest('id')->first()
                ?? User::first();
        }

        // Jika belum ada user, buatkan demo user dan data awal
        if (!$user) {
            $user = $this->seedDemoUser();
        }

        $allUsers = User::withCount('transactions')->get();

        $wallets = $user->wallets()->get();
        $totalBalance = $wallets->sum('balance');

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Transaksi Bulan Ini
        $thisMonthTrx = $user->transactions()
            ->whereBetween('transacted_at', [$startOfMonth, $endOfMonth])
            ->get();

        $monthIncome = $thisMonthTrx->where('type', 'income')->sum('amount');
        $monthExpense = $thisMonthTrx->where('type', 'expense')->sum('amount');
        $netSavings = $monthIncome - $monthExpense;

        // 1. DATA TREN HARIAN (7 Hari Terakhir)
        $dailyLabels = [];
        $dailyIncome = [];
        $dailyExpense = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dailyLabels[] = $date->translatedFormat('D, d M');

            $dayTrx = $user->transactions()
                ->whereDate('transacted_at', $dateStr)
                ->get();

            $dailyIncome[] = (int) $dayTrx->where('type', 'income')->sum('amount');
            $dailyExpense[] = (int) $dayTrx->where('type', 'expense')->sum('amount');
        }

        // 2. DATA TREN MINGGUAN (4 Minggu Terakhir)
        $weeklyLabels = [];
        $weeklyIncome = [];
        $weeklyExpense = [];
        for ($i = 3; $i >= 0; $i--) {
            $startWeek = $now->copy()->subWeeks($i)->startOfWeek();
            $endWeek = $now->copy()->subWeeks($i)->endOfWeek();
            $weeklyLabels[] = 'Mgg ' . $startWeek->format('d/m') . ' - ' . $endWeek->format('d/m');

            $weekTrx = $user->transactions()
                ->whereBetween('transacted_at', [$startWeek, $endWeek])
                ->get();

            $weeklyIncome[] = (int) $weekTrx->where('type', 'income')->sum('amount');
            $weeklyExpense[] = (int) $weekTrx->where('type', 'expense')->sum('amount');
        }

        // 3. DATA TREN BULANAN (6 Bulan Terakhir)
        $monthlyLabels = [];
        $monthlyIncome = [];
        $monthlyExpense = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $monthlyLabels[] = $monthDate->translatedFormat('M Y');

            $mTrx = $user->transactions()
                ->whereYear('transacted_at', $monthDate->year)
                ->whereMonth('transacted_at', $monthDate->month)
                ->get();

            $monthlyIncome[] = (int) $mTrx->where('type', 'income')->sum('amount');
            $monthlyExpense[] = (int) $mTrx->where('type', 'expense')->sum('amount');
        }

        // 4. DATA KOMPOSISI KATEGORI (Bulan Ini)
        $categoryBreakdown = $thisMonthTrx->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->category?->name ?? 'Lain-lain',
                    'icon' => $items->first()->category?->icon ?? '🏷',
                    'color' => $items->first()->category?->color ?? '#94a3b8',
                    'amount' => (int) $items->sum('amount'),
                ];
            })
            ->values();

        // Transaksi Terbaru
        $recentTransactions = $user->transactions()
            ->with(['wallet', 'category'])
            ->orderBy('transacted_at', 'desc')
            ->take(15)
            ->get();

        return view('dashboard', compact(
            'user',
            'wallets',
            'totalBalance',
            'monthIncome',
            'monthExpense',
            'netSavings',
            'dailyLabels',
            'dailyIncome',
            'dailyExpense',
            'weeklyLabels',
            'weeklyIncome',
            'weeklyExpense',
            'monthlyLabels',
            'monthlyIncome',
            'monthlyExpense',
            'categoryBreakdown',
            'recentTransactions',
            'allUsers'
        ));
    }

    protected function seedDemoUser(): User
    {
        $user = User::create([
            'name' => 'Adit Pratama',
            'email' => 'adit@keuangankita.id',
            'password' => bcrypt('password123'),
            'telegram_chat_id' => 12345678,
            'telegram_username' => 'aditpratama',
        ]);

        $bca = $user->wallets()->create([
            'name' => 'BCA Utama',
            'type' => 'bank',
            'balance' => 4500000,
            'is_default' => true,
        ]);

        $gopay = $user->wallets()->create([
            'name' => 'GoPay',
            'type' => 'ewallet',
            'balance' => 350000,
            'is_default' => false,
        ]);

        $cash = $user->wallets()->create([
            'name' => 'Kas Tunai',
            'type' => 'cash',
            'balance' => 150000,
            'is_default' => false,
        ]);

        $catMakan = $user->categories()->create(['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => '🍔', 'color' => '#f59e0b']);
        $catTransport = $user->categories()->create(['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🛵', 'color' => '#3b82f6']);
        $catBelanja = $user->categories()->create(['name' => 'Belanja Harian', 'type' => 'expense', 'icon' => '🛒', 'color' => '#ec4899']);
        $catGaji = $user->categories()->create(['name' => 'Gaji & Bonus', 'type' => 'income', 'icon' => '💵', 'color' => '#10b981']);

        // Data awal transaksi
        $user->transactions()->create([
            'wallet_id' => $bca->id,
            'category_id' => $catGaji->id,
            'type' => 'income',
            'amount' => 8500000,
            'notes' => 'Gaji Bulanan',
            'source' => 'web_dashboard',
            'transacted_at' => now()->startOfMonth(),
        ]);

        $user->transactions()->create([
            'wallet_id' => $bca->id,
            'category_id' => $catMakan->id,
            'type' => 'expense',
            'amount' => 50000,
            'notes' => 'Nasi Padang + Es Teh',
            'source' => 'telegram_bot',
            'transacted_at' => now()->subHours(3),
        ]);

        $user->transactions()->create([
            'wallet_id' => $gopay->id,
            'category_id' => $catTransport->id,
            'type' => 'expense',
            'amount' => 24000,
            'notes' => 'Gojek ke Kantor',
            'source' => 'telegram_bot',
            'transacted_at' => now()->subHours(8),
        ]);

        $user->transactions()->create([
            'wallet_id' => $cash->id,
            'category_id' => $catBelanja->id,
            'type' => 'expense',
            'amount' => 125000,
            'notes' => 'Sabun & Keperluan Kost',
            'source' => 'web_dashboard',
            'transacted_at' => now()->subDays(1),
        ]);

        return $user;
    }
}
