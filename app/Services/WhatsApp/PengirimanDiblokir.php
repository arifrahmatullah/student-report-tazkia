<?php

namespace App\Services\WhatsApp;

use RuntimeException;

/** Pengiriman ditolak oleh pengaman lingkungan uji (bukan galat dari WhatsApp). */
class PengirimanDiblokir extends RuntimeException {}
