@php
    $calendarProjects = collect($dashboardProjects ?? [])->filter(fn ($project) => $project->due_date && $project->progress < 100)
        ->map(fn ($project) => ['date' => $project->due_date->format('Y-m-d'), 'title' => $project->name])->values();
@endphp
<section class="dashboard-calendar" data-dashboard-calendar data-today="{{ now(config('app.timezone'))->format('Y-m-d') }}" aria-label="Calendar">
    <div class="dashboard-calendar-heading">
        <div><h2>Calendar</h2><p>Plan your day, stay on track.</p></div>
        <button type="button" class="calendar-today" data-calendar-today>Today</button>
    </div>
    <div class="calendar-navigation">
        <button type="button" data-calendar-prev aria-label="Previous month"><span aria-hidden="true">&lsaquo;</span></button>
        <h3 data-calendar-month aria-live="polite"></h3>
        <button type="button" data-calendar-next aria-label="Next month"><span aria-hidden="true">&rsaquo;</span></button>
    </div>
    <div class="calendar-weekdays" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
    <div class="calendar-days" data-calendar-days role="group" aria-label="Choose a date"></div>
    <div class="calendar-legend"><span></span> Project due date</div>
    <div class="calendar-agenda" aria-live="polite" aria-atomic="true">
        <h4 data-calendar-selected></h4>
        <ul data-calendar-agenda></ul>
    </div>
    <noscript><p>Enable JavaScript to browse the calendar. Project due dates are also listed under Project Status.</p></noscript>
    <script type="application/json" data-calendar-events>@json($calendarProjects)</script>
</section>
