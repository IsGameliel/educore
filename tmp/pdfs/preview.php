<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['institution.branding.name' => 'Mudiame University Irrua']);
$names = ['Communication in English', 'Introduction to Computer Science', 'Elementary Mathematics I', 'General Physics I', 'General Chemistry I', 'Introduction to Entrepreneurship', 'Practical Physics I', 'Nigerian Peoples and Culture'];
$courses = collect($names)->map(fn($title, $i) => (object)['course' => (object)['code' => ['GST 101','CSC 101','MTH 101','PHY 101','CHM 101','ENT 101','PHY 107','GST 103'][$i], 'title' => $title, 'credit_unit' => $i === 6 ? 1 : 3, 'semester' => 'First']]);
$data = ['user' => (object)['name' => 'Alexandra Ehi Okonkwo', 'matric_number' => 'MU/SCI/CSC/2025/0142', 'email' => 'alexandra.okonkwo@example.edu.ng', 'level' => '100'], 'level' => '100', 'department' => 'Computer Science', 'session' => '2025/2026', 'semester' => 'First', 'courses' => $courses, 'totalCreditUnits' => $courses->sum(fn($r) => $r->course->credit_unit)];
$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('student.coursereg.pdf', $data)->setPaper('a4');
$pdf->save(__DIR__.'/../../output/pdf/course-registration-preview.pdf');
echo 'Sample pages: '.$pdf->getDomPDF()->getCanvas()->get_page_count().PHP_EOL;
$data['courses'] = collect(range(1, 32))->map(fn($i) => (object)['course' => (object)['code' => 'CSC '.(100+$i), 'title' => 'Advanced Topics in Computer Science and Information Systems '.$i, 'credit_unit' => 3, 'semester' => 'First']]);
$data['totalCreditUnits'] = 96;
$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('student.coursereg.pdf', $data)->setPaper('a4');
$pdf->save(__DIR__.'/course-registration-long.pdf');
echo 'Long sample pages: '.$pdf->getDomPDF()->getCanvas()->get_page_count().PHP_EOL;
