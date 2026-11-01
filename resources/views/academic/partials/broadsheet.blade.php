@if($broadsheetRows)
<p class="text-muted small">E: Elective; R: Required; C: Compulsory. TCC: credits counted; TCE: credits earned; TGP: weighted grade points. Summaries use published results only.</p>
@php
    $studentCount = $broadsheet->count();
    $sectionHeight = $studentCount + 6;
@endphp
@foreach(['Scores', 'Letter Grade', 'Grade Point'] as $section => $title)
@php
    $start = 5 + $section * $sectionHeight;
    $groupRow = $broadsheetRows[$start + 1];
    $courseRow = $broadsheetRows[$start + 2];
    $unitRow = $broadsheetRows[$start + 3];
@endphp
<h4 class="h6 mt-4">{{ $title }}</h4>
<div class="table-responsive"><table class="table table-sm table-bordered text-center align-middle" style="font-size: .8rem; white-space: nowrap;">
<thead>
<tr><th colspan="3"></th>
@for($column = 3; $column < count($groupRow); $column++)
    @if($groupRow[$column] !== '')
        @php
            $span = 1;
            while ($column + $span < count($groupRow) && $groupRow[$column + $span] === '') { $span++; }
        @endphp
        <th colspan="{{ $span }}">{{ $groupRow[$column] }}</th>
    @endif
@endfor
</tr>
<tr><th colspan="3"></th>@foreach(array_slice($courseRow, 3) as $cell)<th>{{ $cell }}</th>@endforeach</tr>
<tr>@foreach($unitRow as $cell)<th>{{ $cell }}</th>@endforeach</tr>
</thead>
<tbody>
@foreach(array_slice($broadsheetRows, $start + 4, $studentCount) as $row)
<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
@endforeach
</tbody></table></div>
@endforeach
@endif
