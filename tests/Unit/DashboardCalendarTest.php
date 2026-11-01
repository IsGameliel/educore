<?php

use App\Models\DashboardProject;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class);

it('renders only dated unfinished projects and safely embeds their names', function () {
    $title = '</script><script>alert("test")</script>';
    $html = view('dashboard.calendar', ['dashboardProjects' => collect([
        new DashboardProject(['name' => $title, 'due_date' => '2026-10-31', 'progress' => 20]),
        new DashboardProject(['name' => 'Finished', 'due_date' => '2026-10-31', 'progress' => 100]),
        new DashboardProject(['name' => 'No date', 'progress' => 0]),
    ])])->render();

    preg_match('/data-calendar-events>(.*?)<\/script>/s', $html, $matches);
    expect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR))
        ->toBe([['date' => '2026-10-31', 'title' => $title]])
        ->and($html)->not->toContain($title);
});

it('renders without projects and uses the configured timezone for today', function () {
    config(['app.timezone' => 'America/New_York']);
    Carbon::setTestNow(Carbon::parse('2026-11-01 02:00:00', 'UTC'));
    try {
        $html = view('dashboard.calendar')->render();
        expect($html)->toContain('data-today="2026-10-31"')
            ->toContain('data-calendar-events>[]</script>');
    } finally {
        Carbon::setTestNow();
    }
});
