<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * The response of the "Extract" endpoints (procedures.summaries, studies.summaries): a patient's
 * study summaries for another form (the discharge summary) to copy in, each with its date,
 * oldest first. Studies with nothing in their summary are left out.
 */
final class StudySummaries
{
    /**
     * @param  iterable<array{id: int, date: string|null, created_at: Carbon, status: string, summary: string, url: string}>  $studies
     */
    public static function response(iterable $studies): JsonResponse
    {
        $rows = collect($studies)
            ->filter(fn (array $study) => trim($study['summary']) !== '')
            ->map(function (array $study) {
                $recorded = JalaliDate::format($study['created_at'], 'yyyy/MM/dd');
                $date = $study['date'] ?: $recorded;
                // Jalali dates as entered ("1405/7/9") sort by their numbers, then by when recorded.
                $day = preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $date, $m) || preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $recorded, $m)
                    ? sprintf('%04d%02d%02d', $m[1], $m[2], $m[3]) : '00000000';

                return [
                    'id' => $study['id'],
                    'date' => $date,
                    'status' => $study['status'],
                    'summary' => trim($study['summary']),
                    'url' => $study['url'],
                    'order' => sprintf('%s-%012d-%012d', $day, $study['created_at']->getTimestamp(), $study['id']),
                ];
            })
            ->sortBy('order')
            ->values();

        return response()->json(['studies' => $rows->map(fn (array $study) => collect($study)->except('order')->all())->all()]);
    }
}
