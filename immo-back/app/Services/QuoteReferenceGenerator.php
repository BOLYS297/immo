<?php

namespace App\Services;

use App\Models\QuoteRequest;

class QuoteReferenceGenerator
{
    /**
     * Generate a unique reference in the format DEV-YYYYMM-XXXX
     */
    public static function generate(): string
    {
        $prefix = 'DEV-'.date('Ym').'-';

        // Find the highest existing sequence number for this month
        $latest = QuoteRequest::where('reference', 'LIKE', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && preg_match('/'.preg_quote($prefix, '/').'(\d+)/', $latest->reference, $matches)) {
            $nextSequence = (int) $matches[1] + 1;
        } else {
            $countThisMonth = QuoteRequest::where('reference', 'LIKE', $prefix.'%')->count();
            $nextSequence = $countThisMonth + 1;
        }

        $reference = $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness defensively
        while (QuoteRequest::where('reference', $reference)->exists()) {
            $nextSequence++;
            $reference = $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
        }

        return $reference;
    }
}
