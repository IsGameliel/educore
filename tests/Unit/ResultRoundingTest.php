<?php

use App\Models\GradingPolicy;
use App\Models\Result;
use App\Services\Academic\Grading;

it('rounds result totals half up before assigning grades', function ($input, $expected, $grade) {
    $result = Grading::calculate(['score' => $input], GradingPolicy::defaults(40));

    expect($result['score'])->toBe($expected)
        ->and($result['grade'])->toBe($grade);
})->with([
    [32.4, 32, 'F'],
    [32.5, 33, 'F'],
    [39.4, 39, 'F'],
    [39.5, 40, 'E'],
    [69.5, 70, 'A'],
]);

it('rounds the combined assessment total once', function () {
    $result = Grading::calculate(['ca_score' => 20.4, 'exam_score' => 49.4], GradingPolicy::defaults(40));

    expect($result['score'])->toBe(70)->and($result['grade'])->toBe('A');
});

it('exposes whole number scores for existing records and broadsheets', function () {
    $result = new Result;
    $result->setRawAttributes(['score' => '32.50']);

    expect($result->score)->toBe(33)
        ->and($result->toArray()['score'])->toBe(33)
        ->and(Result::resolveScore(null))->toBeNull();
});
