<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

it('moves legacy and orphaned uploads without changing paths and can safely run again', function () {
    Storage::disk('public')->put('admission-documents/jamb.pdf', 'confidential JAMB document');
    Storage::disk('public')->put('admission-documents/orphan.pdf', 'confidential old upload');
    Storage::disk('public')->put('course_materials/book.pdf', 'public course book');
    $this->artisan('admissions:privatize-documents')->assertSuccessful();
    expect(Storage::disk('local')->get('admission-documents/jamb.pdf'))->toBe('confidential JAMB document');
    Storage::disk('public')->assertMissing('admission-documents/jamb.pdf');
    Storage::disk('public')->assertMissing('admission-documents/orphan.pdf');
    Storage::disk('public')->assertExists('course_materials/book.pdf');
    $this->artisan('admissions:privatize-documents')->assertSuccessful();
});

it('resumes an interrupted move when the verified private copy already exists', function () {
    foreach (['public', 'local'] as $disk) {
        Storage::disk($disk)->put('admission-documents/jamb.pdf', 'same document');
    }
    $this->artisan('admissions:privatize-documents')->assertSuccessful();
    Storage::disk('public')->assertMissing('admission-documents/jamb.pdf');
    expect(Storage::disk('local')->get('admission-documents/jamb.pdf'))->toBe('same document');
});

it('retains both files when the destination conflicts rather than overwriting records', function () {
    Storage::disk('public')->put('admission-documents/jamb.pdf', 'source document');
    Storage::disk('local')->put('admission-documents/jamb.pdf', 'different document');
    expect(fn () => $this->artisan('admissions:privatize-documents')->run())->toThrow(RuntimeException::class);
    expect(Storage::disk('public')->get('admission-documents/jamb.pdf'))->toBe('source document')
        ->and(Storage::disk('local')->get('admission-documents/jamb.pdf'))->toBe('different document');
});
