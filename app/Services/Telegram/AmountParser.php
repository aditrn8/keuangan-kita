<?php

namespace App\Services\Telegram;

class AmountParser
{
    /**
     * Parse nominal uang dari teks input pengguna.
     * Mendukung format: 50k, 50rb, 1.5jt, 50.000, 50000.
     *
     * @param string $input
     * @return int|null
     */
    public static function parse(string $input): ?int
    {
        $cleaned = trim(strtolower($input));

        // Format Ribuan (50k, 50rb, 50ribu, 1.5k)
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\s*(?:k|rb|ribu)$/i', $cleaned, $matches)) {
            $num = (float) str_replace(',', '.', $matches[1]);
            return (int) round($num * 1000);
        }

        // Format Jutaan (1.5jt, 2juta, 10jt, 1m)
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m|mio)$/i', $cleaned, $matches)) {
            $num = (float) str_replace(',', '.', $matches[1]);
            return (int) round($num * 1000000);
        }

        // Format Angka Bertitik atau Berkoma (50.000 atau 100,000 atau 50000)
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleaned);
        if (!empty($digitsOnly)) {
            $val = (int) $digitsOnly;
            return $val > 0 ? $val : null;
        }

        return null;
    }
}
