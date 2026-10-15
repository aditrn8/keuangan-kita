<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Keuangan Kita — Monitoring Pemasukan & Pengeluaran</title>
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          },
          colors: {
            brand: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              500: '#0284c7',
              600: '#0369a1',
              700: '#075985',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col">

  <!-- TOP NAVBAR -->
  <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-cyan-500 flex items-center justify-center text-white shadow-md shadow-sky-500/20 font-extrabold text-xl">
          KK
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 leading-none">Keuangan<span class="text-sky-600">Kita</span></h1>
          <p class="text-xs text-slate-500 font-medium">Telegram Bot & Financial Tracker</p>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <div class="hidden sm:flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
          <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-slate-600 font-medium">Bot Webhook Active</span>
        </div>
      <div class="flex items-center gap-3">
        @if(isset($allUsers) && $allUsers->count() > 1)
          <form method="GET" action="{{ route('dashboard') }}" class="flex items-center">
            <select name="user_id" onchange="this.form.submit()" class="bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 text-xs font-semibold rounded-lg px-2.5 py-1.5 focus:ring-sky-500 focus:border-sky-500 cursor-pointer">
              @foreach($allUsers as $u)
                <option value="{{ $u->id }}" {{ $u->id === $user->id ? 'selected' : '' }}>
                  👤 {{ $u->name }} ({{ $u->transactions_count }} Trx) {{ str_starts_with($u->email, 'tg_') ? '✈ Telegram' : 'Demo' }}
                </option>
              @endforeach
            </select>
          </form>
        @endif

        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xs">
            {{ strtoupper(substr($user->name, 0, 2)) }}
          </div>
          <span class="text-sm font-semibold text-slate-700 hidden md:inline">{{ $user->name }}</span>
        </div>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT CONTAINER -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
    
    <!-- STATS OVERVIEW CARDS -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Saldo -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-sky-50 rounded-full -z-0"></div>
        <div class="relative z-10">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Saldo Aktif</span>
          <div class="text-2xl font-extrabold text-slate-900 mt-1">
            Rp {{ number_format($totalBalance, 0, ',', '.') }}
          </div>
          <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
            <span>{{ $wallets->count() }} Dompet Terdaftar</span>
          </div>
        </div>
      </div>

      <!-- Pemasukan Bulan Ini -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-50 rounded-full -z-0"></div>
        <div class="relative z-10">
          <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Pemasukan Bulan Ini</span>
          <div class="text-2xl font-extrabold text-emerald-600 mt-1">
            +Rp {{ number_format($monthIncome, 0, ',', '.') }}
          </div>
          <div class="mt-2 text-xs text-slate-500">
            Bulan {{ now()->translatedFormat('F Y') }}
          </div>
        </div>
      </div>

      <!-- Pengeluaran Bulan Ini -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-rose-50 rounded-full -z-0"></div>
        <div class="relative z-10">
          <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">Pengeluaran Bulan Ini</span>
          <div class="text-2xl font-extrabold text-rose-600 mt-1">
            -Rp {{ number_format($monthExpense, 0, ',', '.') }}
          </div>
          <div class="mt-2 text-xs text-slate-500">
            Bulan {{ now()->translatedFormat('F Y') }}
          </div>
        </div>
      </div>

      <!-- Net Tabungan -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-50 rounded-full -z-0"></div>
        <div class="relative z-10">
          <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Net Tabungan</span>
          <div class="text-2xl font-extrabold {{ $netSavings >= 0 ? 'text-indigo-600' : 'text-amber-600' }} mt-1">
            {{ $netSavings >= 0 ? '+Rp ' : '-Rp ' }}{{ number_format(abs($netSavings), 0, ',', '.') }}
          </div>
          <div class="mt-2 text-xs font-medium {{ $netSavings >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
            {{ $netSavings >= 0 ? '✓ Surplus Keuangan' : '⚠ Pengeluaran Melebihi Masuk' }}
          </div>
        </div>
      </div>
    </section>

    <!-- WALLETS PILLS -->
    <section class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Dompet & Rekening Aktif</h2>
        <span class="text-xs text-sky-600 font-semibold cursor-pointer hover:underline">Kelola Dompet</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach($wallets as $wallet)
          <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-lg shadow-sm">
                {{ $wallet->type === 'bank' ? '🏦' : ($wallet->type === 'ewallet' ? '📱' : '💵') }}
              </div>
              <div>
                <div class="text-xs font-semibold text-slate-700 flex items-center gap-1">
                  {{ $wallet->name }}
                  @if($wallet->is_default)
                    <span class="text-[10px] bg-sky-100 text-sky-700 px-1.5 py-0.5 rounded font-bold">Utama</span>
                  @endif
                </div>
                <div class="text-base font-extrabold text-slate-900 mt-0.5">
                  Rp {{ number_format($wallet->balance, 0, ',', '.') }}
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    <!-- CHARTS SECTION: TREN HARIAN / MINGGUAN / BULANAN -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Main Trends Chart (2 Columns) -->
      <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
          <div>
            <h2 class="text-base font-bold text-slate-900">Grafik Monitoring Tren Keuangan</h2>
            <p class="text-xs text-slate-500">Komparasi dinamis pemasukan dan pengeluaran berkala</p>
          </div>
          
          <!-- TAB SWITCHER: HARIAN / MINGGUAN / BULANAN -->
          <div class="inline-flex bg-slate-100 p-1 rounded-xl border border-slate-200 self-start sm:self-auto">
            <button onclick="switchView('daily')" id="btn-daily" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-sm transition">
              Harian (7 Hari)
            </button>
            <button onclick="switchView('weekly')" id="btn-weekly" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
              Mingguan (4 Mgg)
            </button>
            <button onclick="switchView('monthly')" id="btn-monthly" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
              Bulanan (6 Bulan)
            </button>
          </div>
        </div>

        <!-- Canvas Chart -->
        <div class="flex-1 w-full min-h-[300px]">
          <canvas id="trendChart"></canvas>
        </div>
      </div>

      <!-- Donut Chart: Komposisi Pengeluaran Kategori (1 Column) -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col">
        <div class="mb-4">
          <h2 class="text-base font-bold text-slate-900">Komposisi Pengeluaran</h2>
          <p class="text-xs text-slate-500">Berdasarkan kategori bulan ini</p>
        </div>

        @if($categoryBreakdown->isEmpty() || $categoryBreakdown->sum('amount') == 0)
          <div class="flex-1 flex flex-col items-center justify-center text-center p-6 text-slate-400">
            <span class="text-3xl mb-2">📊</span>
            <p class="text-xs font-medium">Belum ada data pengeluaran bulan ini.</p>
          </div>
        @else
          <div class="flex-1 flex items-center justify-center min-h-[200px]">
            <canvas id="categoryChart"></canvas>
          </div>
          <div class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs">
            @foreach($categoryBreakdown as $cat)
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-slate-700 font-medium">
                  <span>{{ $cat['icon'] }}</span> {{ $cat['name'] }}
                </span>
                <span class="font-bold text-slate-900">Rp {{ number_format($cat['amount'], 0, ',', '.') }}</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>

    </section>

    <!-- RECENT TRANSACTIONS TABLE & TELEGRAM BOT INSTRUCTIONS -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Transactions Table (2 Columns) -->
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-slate-900">Riwayat Transaksi Terbaru</h2>
            <p class="text-xs text-slate-500">Otomatis tercatat beserta timestamp real-time</p>
          </div>
          <span class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md font-semibold font-mono">
            {{ $recentTransactions->count() }} Data
          </span>
        </div>

        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-100">
              <tr>
                <th class="py-3 px-4">Waktu & Tanggal</th>
                <th class="py-3 px-4">Catatan & Kategori</th>
                <th class="py-3 px-4">Dompet</th>
                <th class="py-3 px-4">Sumber</th>
                <th class="py-3 px-4 text-right">Nominal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($recentTransactions as $trx)
                <tr class="hover:bg-slate-50/80 transition">
                  <td class="py-3 px-4 text-slate-500 font-mono whitespace-nowrap">
                    {{ $trx->transacted_at->translatedFormat('d M Y, H:i') }} WIB
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-800">{{ $trx->notes }}</div>
                    <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                      <span>{{ $trx->category?->icon ?? '🏷' }}</span>
                      <span>{{ $trx->category?->name ?? 'Umum' }}</span>
                    </div>
                  </td>
                  <td class="py-3 px-4 text-slate-600 font-medium">
                    {{ $trx->wallet?->name ?? 'Kas' }}
                  </td>
                  <td class="py-3 px-4">
                    @if($trx->source === 'telegram_bot')
                      <span class="inline-flex items-center gap-1 text-[11px] bg-sky-50 text-sky-700 px-2 py-0.5 rounded-full font-semibold border border-sky-200">
                        ✈ Telegram
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 text-[11px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-semibold border border-slate-200">
                        🌐 Web
                      </span>
                    @endif
                  </td>
                  <td class="py-3 px-4 text-right font-extrabold whitespace-nowrap font-mono text-sm {{ $trx->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $trx->type === 'income' ? '+' : '-' }}Rp {{ number_format($trx->amount, 0, ',', '.') }}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-400">
                    Belum ada riwayat transaksi yang dicatat.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Telegram Bot Command Guide (1 Column) -->
      <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 rounded-2xl shadow-sm flex flex-col justify-between">
        <div>
          <div class="inline-flex items-center gap-2 bg-sky-500/20 text-sky-400 text-xs px-2.5 py-1 rounded-full font-semibold border border-sky-500/30 mb-4">
            <span>✈</span> Telegram Bot Integration
          </div>
          <h2 class="text-lg font-bold">Catat Kilat via Telegram</h2>
          <p class="text-xs text-slate-300 mt-1 leading-relaxed">
            Kirimkan chat langsung ke bot Telegram untuk merekam transaksi dalam hitungan detik tanpa membuka browser.
          </p>

          <div class="mt-6 space-y-3 font-mono text-xs">
            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Pengeluaran:</span>
              <span class="text-emerald-400">/catat 50000 makan sate</span>
              <span class="text-slate-400 block text-[11px] font-sans mt-0.5">atau <code>/keluar 25k kopi</code></span>
            </div>

            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Pemasukan:</span>
              <span class="text-emerald-400">/masuk 1.5jt bonus freelance</span>
            </div>

            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Cek Saldo:</span>
              <span class="text-sky-300">/saldo</span>
            </div>

            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Rekap Bulanan:</span>
              <span class="text-sky-300">/laporan</span>
            </div>
          </div>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-700 text-xs text-slate-400 flex items-center justify-between">
          <span>Otomatis timestamp WIB</span>
          <span class="text-sky-400 font-bold">Vercel Serverless Ready</span>
        </div>
      </div>

    </section>

  </main>

  <!-- JAVASCRIPT CHART.JS CONFIGURATION -->
  <script>
    // Data PHP to JS
    const chartData = {
      daily: {
        labels: {!! json_encode($dailyLabels) !!},
        income: {!! json_encode($dailyIncome) !!},
        expense: {!! json_encode($dailyExpense) !!},
      },
      weekly: {
        labels: {!! json_encode($weeklyLabels) !!},
        income: {!! json_encode($weeklyIncome) !!},
        expense: {!! json_encode($weeklyExpense) !!},
      },
      monthly: {
        labels: {!! json_encode($monthlyLabels) !!},
        income: {!! json_encode($monthlyIncome) !!},
        expense: {!! json_encode($monthlyExpense) !!},
      }
    };

    // Category Data
    const categoryData = {
      labels: {!! json_encode($categoryBreakdown->pluck('name')) !!},
      amounts: {!! json_encode($categoryBreakdown->pluck('amount')) !!},
      colors: {!! json_encode($categoryBreakdown->pluck('color')) !!},
    };

    let mainChart;

    function initTrendChart(type = 'daily') {
      const ctx = document.getElementById('trendChart').getContext('2d');
      const data = chartData[type];

      if (mainChart) {
        mainChart.destroy();
      }

      mainChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.labels,
          datasets: [
            {
              label: 'Pemasukan',
              data: data.income,
              backgroundColor: '#10b981',
              borderRadius: 6,
              barPercentage: 0.6,
            },
            {
              label: 'Pengeluaran',
              data: data.expense,
              backgroundColor: '#f43f5e',
              borderRadius: 6,
              barPercentage: 0.6,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                boxWidth: 12,
                font: { family: '"Plus Jakarta Sans"', size: 12, weight: 600 }
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return 'Rp ' + (value >= 1000000 ? (value / 1000000) + 'jt' : (value / 1000) + 'k');
                },
                font: { family: '"Plus Jakarta Sans"', size: 11 }
              },
              grid: { color: '#f1f5f9' }
            },
            x: {
              grid: { display: false },
              ticks: { font: { family: '"Plus Jakarta Sans"', size: 11 } }
            }
          }
        }
      });
    }

    function switchView(view) {
      ['daily', 'weekly', 'monthly'].forEach(v => {
        const btn = document.getElementById('btn-' + v);
        if (v === view) {
          btn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-900 shadow-sm transition';
        } else {
          btn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition';
        }
      });
      initTrendChart(view);
    }

    // Initialize Category Donut Chart
    function initCategoryChart() {
      const catCanvas = document.getElementById('categoryChart');
      if (!catCanvas || categoryData.amounts.length === 0) return;

      const ctx = catCanvas.getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: categoryData.labels,
          datasets: [{
            data: categoryData.amounts,
            backgroundColor: categoryData.colors.length ? categoryData.colors : ['#f59e0b', '#3b82f6', '#ec4899', '#10b981'],
            borderWidth: 2,
            borderColor: '#ffffff',
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.label + ': Rp ' + context.raw.toLocaleString('id-ID');
                }
              }
            }
          },
          cutout: '70%',
        }
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      initTrendChart('daily');
      initCategoryChart();
    });
  </script>

</body>
</html>
