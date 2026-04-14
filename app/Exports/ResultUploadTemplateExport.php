<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ResultUploadTemplateExport implements WithMultipleSheets
{
    public function __construct(protected Collection $courses)
    {
    }

    public function sheets(): array
    {
        $usedTitles = [];

        return $this->courses->values()->map(function ($course, $index) use (&$usedTitles) {
            $baseTitle = trim($course->code ?: ('Course ' . ($index + 1)));
            $title = mb_substr($baseTitle, 0, 31);
            $suffix = 2;

            while (in_array(mb_strtolower($title), $usedTitles, true)) {
                $candidate = $baseTitle . ' ' . $suffix;
                $title = mb_substr($candidate, 0, 31);
                $suffix++;
            }

            $usedTitles[] = mb_strtolower($title);

            return new ResultUploadTemplateSheet($course, $title);
        })->all();
    }
}
