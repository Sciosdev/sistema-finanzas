<?php

use App\Support\FinanceMonth;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('parses finance months without inheriting the current day', function (string $today, string $selectedMonth, string $expectedDate) {
    Carbon::setTestNow($today);

    expect(FinanceMonth::parse($selectedMonth)->toDateString())->toBe($expectedDate);
})->with([
    'septiembre desde un dia 31' => ['2026-08-31 12:00:00', '2026-09', '2026-09-01'],
    'febrero desde un dia 31' => ['2026-03-31 12:00:00', '2026-02', '2026-02-01'],
    'febrero desde un dia 30' => ['2026-01-30 12:00:00', '2026-02', '2026-02-01'],
]);
