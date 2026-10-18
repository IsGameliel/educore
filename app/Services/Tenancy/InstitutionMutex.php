<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InstitutionMutex
{
    private array $held = [];

    public function acquire(string $id): \Closure
    {
        if (! preg_match('/\A[a-zA-Z0-9-]{1,40}\z/D', $id)) {
            throw new \RuntimeException('Invalid institution lock identity.');
        }
        if (isset($this->held[$id])) {
            $this->held[$id]['depth']++;
        } else {
            $connection = DB::connection(config('tenancy.central_connection'));
            if ($connection->getDriverName() === 'mysql') {
                $name = 'educore.institution.'.$id;
                if ((int) $connection->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$name])->acquired !== 1) {
                    throw new HttpException(503, 'This institution is busy. Please retry shortly.');
                }
                $this->held[$id] = ['depth' => 1, 'release' => fn () => $connection->select('SELECT RELEASE_LOCK(?)', [$name])];
            } else {
                File::ensureDirectoryExists(config('tenancy.lock_directory'));
                $handle = fopen(config('tenancy.lock_directory').'/operation-'.$id.'.lock', 'c');
                if (! $handle || ! flock($handle, LOCK_EX | LOCK_NB)) {
                    if ($handle) {
                        fclose($handle);
                    } throw new HttpException(503, 'This institution is busy. Please retry shortly.');
                }
                $this->held[$id] = ['depth' => 1, 'release' => static function () use ($handle) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }];
            }
        }
        $closed = false;

        return function () use ($id, &$closed) {
            if ($closed) {
                return;
            } $closed = true;
            if (--$this->held[$id]['depth'] === 0) {
                $release = $this->held[$id]['release'];
                unset($this->held[$id]);
                $release();
            }
        };
    }

    public function run(string $id, callable $callback): mixed
    {
        $release = $this->acquire($id);
        try {
            return $callback();
        } finally {
            $release();
        }
    }
}
