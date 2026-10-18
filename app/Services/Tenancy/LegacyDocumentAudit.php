<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;

class LegacyDocumentAudit
{
    private const FIELDS = [
        'users' => ['profile_photo_path' => ['public']],
        'admission_applications' => ['jamb_result_path' => ['private'], 'diploma_result_path' => ['private'], 'transcript_path' => ['private']],
        'course_materials' => ['file_path' => ['public'], 'cover_photo' => ['public']],
        'qms' => ['file_path' => ['private', 'public']],
        'name_change_requests' => ['document_path' => ['private']],
        'result_appeals' => ['evidence_path' => ['private']],
        'transcript_documents' => ['path' => ['private']],
        'results' => ['transcript_path' => ['private', 'public'], 'full_transcript_path' => ['private', 'public']],
    ];

    public function inspect(string $connection, string $filesRoot): array
    {
        $references = 0;
        $missing = [];
        $db = DB::connection($connection);
        foreach (self::FIELDS as $table => $fields) {
            if (! $db->getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            foreach ($db->table($table)->get(['id', ...array_keys($fields)]) as $row) {
                foreach ($fields as $column => $disks) {
                    $raw = $row->$column;
                    if (! $raw) {
                        continue;
                    }$references++;
                    $path = str_replace('\\', '/', $raw);
                    if (preg_match('#(?:/documents/transcripts/|/storage/transcripts/)([^/?\#]+)#', $path, $match)) {
                        $path = 'documents/transcripts/'.rawurldecode($match[1]);
                    } elseif (str_starts_with($path, '/storage/')) {
                        $path = substr($path, 9);
                    }
                    $found = false;
                    foreach ($disks as $disk) {
                        $base = realpath($filesRoot.'/'.$disk);
                        $file = $base ? realpath($base.'/'.$path) : false;
                        if (! $file && $base && str_starts_with($path, 'documents/transcripts/')) {
                            $file = realpath($base.'/transcripts/'.basename($path));
                        }
                        if ($base && $file && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file)) {
                            $found = true;
                            break;
                        }
                    }
                    if (! $found) {
                        $missing[] = ['table' => $table, 'id' => $row->id, 'column' => $column, 'path' => $raw];
                    }
                }
            }
        }

        return ['references' => $references, 'missing_references' => $missing, 'ready' => $missing === []];
    }
}
