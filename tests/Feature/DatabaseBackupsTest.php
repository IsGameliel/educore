<?php

use App\Models\User;
use App\Services\Backups\DatabaseBackups;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

beforeEach(function () {
    // Tests must not read or remove the real portal's maintenance marker.
    $maintenance = Mockery::mock(MaintenanceMode::class);
    $maintenance->shouldReceive('active')->andReturn(false);
    $this->app->instance(MaintenanceMode::class, $maintenance);
    $this->backupDirectory = storage_path('framework/testing/backups-'.Str::uuid());
    File::ensureDirectoryExists($this->backupDirectory.'/older');
    config(['backups.directory' => $this->backupDirectory, 'app.maintenance.driver' => 'file', 'backups.dump_binary' => PHP_BINARY, 'backups.mysql_binary' => PHP_BINARY]);
    $this->dump = "-- MySQL dump\n-- Host: localhost    Database: :memory:\nCREATE TABLE `users` (`id` int);\nINSERT INTO `users` VALUES (1);\n-- Dump completed on 2026-09-21 12:00:00\n";
    File::put($this->backupDirectory.'/older/saved.sql', $this->dump);
    $this->backup = app(DatabaseBackups::class)->listing()[0];
});

afterEach(function () {
    File::deleteDirectory($this->backupDirectory);
});

it('restricts every backup and restore endpoint to administrators', function () {
    foreach (['student', 'lecturer', 'exam_officer'] as $role) {
        $this->actingAs(User::factory()->create(['usertype' => $role]));
        $this->get(route('admin.backups.index'))->assertForbidden();
        $this->post(route('admin.backups.store'))->assertForbidden();
        $this->get(route('admin.backups.download', $this->backup['id']))->assertForbidden();
        $this->get(route('admin.backups.confirm', $this->backup['id']))->assertForbidden();
        $this->post(route('admin.backups.restore', $this->backup['id']))->assertForbidden();
    }
});

it('lists nested backups and allows administrators to preview and download them', function () {
    $this->actingAs(User::factory()->create(['usertype' => 'admin']));
    $this->get(route('dashboard'))->assertOk()->assertSee(route('admin.backups.index'), false);
    $this->get(route('admin.backups.index'))->assertOk()->assertSee('saved.sql')->assertSee('Create backup now');
    $this->get(route('admin.backups.confirm', $this->backup['id']))->assertOk()->assertSee('RESTORE saved.sql')->assertSee('users')->assertSee('current admin password');
    $this->get(route('admin.backups.download', $this->backup['id']))->assertDownload('saved.sql');
    $this->get(route('admin.backups.download', str_repeat('a', 64)))->assertNotFound();
});

it('never lists partial files or symlinks to files outside the backup directory', function () {
    File::put($this->backupDirectory.'/unfinished.sql.partial', 'unfinished');
    symlink(base_path('phpunit.xml'), $this->backupDirectory.'/outside.sql');
    expect(app(DatabaseBackups::class)->listing())->toHaveCount(1);
});

it('disables restoration of incomplete or incompatible dumps', function () {
    $manager = app(DatabaseBackups::class);
    File::put($this->backup['path'], str_replace('-- Dump completed on', '-- Interrupted on', $this->dump));
    expect($manager->inspect($manager->find($this->backup['id']))['complete'])->toBeFalse();
    File::put($this->backup['path'], str_replace('Database: :memory:', 'Database: another_database', $this->dump));
    expect($manager->inspect($manager->find($this->backup['id']))['compatible'])->toBeFalse();
    File::put($this->backup['path'], $this->dump."USE other_database;\n");
    expect($manager->inspect($manager->find($this->backup['id']))['compatible'])->toBeFalse();
});

it('requires the current admin password explicit confirmation and acknowledgement before restoring', function () {
    $admin = User::factory()->create(['usertype' => 'admin', 'password' => bcrypt('correct-password')]);
    $manager = Mockery::mock(DatabaseBackups::class)->makePartial();
    $manager->shouldNotReceive('restore');
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->actingAs($admin)->post(route('admin.backups.restore', $this->backup['id']), [
        'password' => 'incorrect', 'confirmation' => 'RESTORE', 'checksum' => hash('sha256', $this->dump),
    ])->assertSessionHasErrors(['password', 'confirmation', 'understand']);
});

it('creates a backup through the admin action', function () {
    $manager = Mockery::mock(DatabaseBackups::class)->makePartial();
    $manager->shouldReceive('create')->once()->andReturn($this->backup);
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))->post(route('admin.backups.store'))
        ->assertRedirect()->assertSessionHas('success', 'Backup created: saved.sql');
});

class RestoreSequenceBackups extends DatabaseBackups
{
    public array $steps = [];

    public bool $failSnapshot = false;

    public bool $failImport = false;

    protected function connection(): array
    {
        return ['driver' => 'mysql', 'database' => ':memory:', 'username' => 'test', 'password' => 'secret-not-in-command'];
    }

    protected function preflight($input): void
    {
        $this->steps[] = 'preflight';
    }

    protected function snapshot(string $folder): array
    {
        $this->steps[] = 'safety';
        if ($this->failSnapshot) {
            throw new RuntimeException('Broken tablespace');
        }

        return ['name' => 'safety.sql'];
    }

    protected function clearTables(): void
    {
        $this->steps[] = 'clear';
    }

    protected function run(Process $process): void
    {
        $this->steps[] = 'import';
        expect($process->getCommandLine())->not->toContain('secret-not-in-command');
        expect(is_resource($process->getInput()))->toBeTrue();
        if ($this->failImport) {
            throw new RuntimeException('Failed import');
        }
    }
}

it('takes a safety backup before clearing tables and importing and upgrades the restored schema', function () {
    $manager = new RestoreSequenceBackups;
    Artisan::shouldReceive('call')->with('down', ['--retry' => 60])->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('up')->once()->andReturn(0);
    DB::shouldReceive('purge')->once();
    $manager->restore($this->backup['id'], hash('sha256', $this->dump), 1);
    expect($manager->steps)->toBe(['preflight', 'safety', 'clear', 'import']);
});

it('does not clear or import anything when the safety backup fails', function () {
    $manager = new RestoreSequenceBackups;
    $manager->failSnapshot = true;
    Artisan::shouldReceive('call')->with('down', ['--retry' => 60])->once()->andReturn(0);
    Artisan::shouldReceive('call')->with('up')->once()->andReturn(0);
    expect(fn () => $manager->restore($this->backup['id'], hash('sha256', $this->dump), 1))->toThrow(RuntimeException::class, 'before changing any tables');
    expect($manager->steps)->toBe(['preflight', 'safety']);
});

it('keeps maintenance mode on after a failed import', function () {
    $manager = new RestoreSequenceBackups;
    $manager->failImport = true;
    Artisan::shouldReceive('call')->with('down', ['--retry' => 60])->once()->andReturn(0);
    Artisan::shouldNotReceive('call')->with('up');
    expect(fn () => $manager->restore($this->backup['id'], hash('sha256', $this->dump), 1))->toThrow(RuntimeException::class, 'remains in maintenance mode');
    expect($manager->steps)->toBe(['preflight', 'safety', 'clear', 'import']);
});

it('rejects changed backup files before entering maintenance or changing the database', function () {
    $manager = new RestoreSequenceBackups;
    Artisan::shouldReceive('call')->never();
    expect(fn () => $manager->restore($this->backup['id'], str_repeat('0', 64), 1))->toThrow(RuntimeException::class, 'changed since confirmation');
    expect($manager->steps)->toBe([]);
});

it('reports a backup creation failure without a success message', function () {
    $manager = Mockery::mock(DatabaseBackups::class)->makePartial();
    $manager->shouldReceive('create')->once()->andThrow(new RuntimeException('Missing tablespace'));
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->actingAs(User::factory()->create(['usertype' => 'admin']))->post(route('admin.backups.store'))
        ->assertSessionHasErrors('backup')->assertSessionMissing('success');
});

it('rejects overlapping operations using a filesystem lock', function () {
    $lock = fopen($this->backupDirectory.'/.operations.lock', 'c');
    flock($lock, LOCK_EX | LOCK_NB);
    try {
        expect(fn () => (new RestoreSequenceBackups)->create())->toThrow(RuntimeException::class, 'Another dashboard backup or restore');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

it('publishes a completed dump with a unique filename and removes incomplete dumps', function () {
    $manager = new class extends DatabaseBackups
    {
        public bool $incomplete = false;

        protected function connection(): array
        {
            return ['driver' => 'mysql', 'database' => ':memory:', 'username' => 'test', 'password' => 'private-password'];
        }

        protected function run(Process $process): void
        {
            expect($process->getCommandLine())->toContain('--single-transaction')->not->toContain('private-password');
            preg_match("/'--result-file=([^']+)'/", $process->getCommandLine(), $matches);
            File::put($matches[1], "-- Host: localhost    Database: :memory:\n".($this->incomplete ? '' : "-- Dump completed on 2026-09-21\n"));
        }
    };
    $one = $manager->create();
    $two = $manager->create();
    expect($one['id'])->not->toBe($two['id'])->and(is_file($one['path']))->toBeTrue();
    $manager->incomplete = true;
    expect(fn () => $manager->create())->toThrow(RuntimeException::class, 'incomplete');
    expect(glob($this->backupDirectory.'/manual/*.partial'))->toBe([]);
});

it('passes a fully confirmed restore to the service and signs the administrator out', function () {
    $admin = User::factory()->create(['usertype' => 'admin', 'password' => bcrypt('correct-password')]);
    $manager = Mockery::mock(DatabaseBackups::class)->makePartial();
    $manager->shouldReceive('restore')->once()->with($this->backup['id'], hash('sha256', $this->dump), $admin->id)->andReturn(['name' => 'safety.sql']);
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->actingAs($admin)->post(route('admin.backups.restore', $this->backup['id']), [
        'password' => 'correct-password', 'confirmation' => 'RESTORE saved.sql', 'understand' => '1', 'checksum' => hash('sha256', $this->dump),
    ])->assertOk()->assertSee('Database restored');
    $this->assertGuest();
});

it('returns a standalone failure response when restoration removes the database sessions table', function () {
    config(['session.driver' => 'database']);
    app('session')->forgetDrivers();
    $this->app->forgetInstance('session.store');
    $admin = User::factory()->create(['usertype' => 'admin', 'password' => bcrypt('correct-password')]);
    $manager = Mockery::mock(DatabaseBackups::class)->makePartial();
    $manager->shouldReceive('restore')->once()->andReturnUsing(function () {
        Schema::drop('sessions');
        throw new RuntimeException('Restore failed; safety backup retained.');
    });
    $this->app->instance(DatabaseBackups::class, $manager);
    $this->actingAs($admin)->post(route('admin.backups.restore', $this->backup['id']), [
        'password' => 'correct-password', 'confirmation' => 'RESTORE saved.sql', 'understand' => '1', 'checksum' => hash('sha256', $this->dump),
    ])->assertStatus(503)->assertSee('safety backup retained')->assertDontSee('SQLSTATE');
});

it('strips dump replication state without removing application schema or records', function () {
    File::put($this->backup['path'], "SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;\nSET @@SESSION.SQL_LOG_BIN= 0;\nSET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/\n 'example:1-10';\n".$this->dump."SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;\n");
    $input = app(DatabaseBackups::class)->restoreInput($this->backup['path']);
    try {
        $sql = stream_get_contents($input);
        expect($sql)->toBe($this->dump)->not->toContain('GTID_PURGED')->not->toContain('SQL_LOG_BIN');
    } finally {
        fclose($input);
    }
});

it('does not enter maintenance or take down live tables when preflight fails', function () {
    $manager = new class extends RestoreSequenceBackups
    {
        protected function preflight($input): void
        {
            throw new RuntimeException('Import rejected in temporary database');
        }
    };
    Artisan::shouldReceive('call')->never();
    expect(fn () => $manager->restore($this->backup['id'], hash('sha256', $this->dump), 1))->toThrow(RuntimeException::class, 'before changing the portal database');
    expect($manager->steps)->toBe([]);
});
