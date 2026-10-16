<?php

namespace App\Support;

/** Normalisasi nomor HP Indonesia ke format internasional tanpa plus (628xxxxxxxxx), seperti yang dipakai WhatsApp. */
class NomorHp
{
    /** @return string|null null bila bukan nomor HP Indonesia yang masuk akal */
    public static function normalisasi(?string $nomor): ?string
    {
        if ($nomor === null) {
            return null;
        }

        $digit = preg_replace('/\D+/', '', $nomor);
        if ($digit === '' || $digit === null) {
            return null;
        }

        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        } elseif (str_starts_with($digit, '8')) {
            $digit = '62'.$digit;
        }

        // Nomor seluler Indonesia: 62 8xx + 8-11 digit lagi.
        return preg_match('/^628\d{8,11}$/', $digit) ? $digit : null;
    }
}
