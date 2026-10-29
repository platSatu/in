<?php

use App\Services\Backup\DatabaseBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/*
| Test fitur Settings > Backup Database (App\Services\Backup\DatabaseBackup).
| Sengaja di folder Unit TANPA RefreshDatabase: migration ina ditulis khusus MySQL
| (ALTER TABLE ... MODIFY ENUM) sehingga tidak bisa dijalankan di SQLite test.
| mysqldump dipalsukan, jadi test ini tidak menyentuh database sungguhan.
*/
uses(TestCase::class);

/** mysqldump palsu: menulis isi dump ke --result-file seperti aslinya. */
function fakeBackupDump(string $sql = "CREATE TABLE students (id int);\nINSERT INTO students VALUES (1);\n"): void
{
    Process::fake(function ($process) use ($sql) {
        $file = collect($process->command)->first(fn ($arg) => str_starts_with($arg, '--result-file='));
        File::put(substr($file, strlen('--result-file=')), $sql);

        return Process::result();
    });
}

beforeEach(function () {
    $this->backup = app(DatabaseBackup::class);
    File::deleteDirectory($this->backup->dir());
    config([
        'services.backup.connection' => 'mysql',
        'database.connections.mysql.database' => 'inagroup_test',
        'database.connections.mysql.password' => 'rahasia',
    ]);
});

afterEach(fn () => File::deleteDirectory(app(DatabaseBackup::class)->dir()));

it('membuat backup .sql.gz dari mysqldump, password tidak terlihat di perintah', function () {
    fakeBackupDump();

    $name = $this->backup->create();

    Process::assertRan(fn ($process) => in_array('inagroup_test', $process->command, true)
        && in_array('--single-transaction', $process->command, true)
        && ! str_contains(implode(' ', $process->command), 'rahasia')
        && $process->environment['MYSQL_PWD'] === 'rahasia');

    expect($name)->toMatch(DatabaseBackup::PATTERN)
        ->and(gzdecode(File::get($this->backup->dir().'/'.$name)))->toContain('INSERT INTO students')
        ->and($this->backup->list()->first()['name'])->toBe($name)
        ->and($this->backup->find($name))->not->toBeNull();
});

it('perintah db:backup hanya menyimpan 14 backup terbaru', function () {
    fakeBackupDump();
    File::ensureDirectoryExists($this->backup->dir());
    foreach (range(1, 15) as $day) {
        File::put($this->backup->dir().sprintf('/inagroup-2026-01-%02d_010000.sql.gz', $day), 'x');
    }

    $this->artisan('db:backup')->assertSuccessful();

    $names = $this->backup->list()->pluck('name');
    expect($names)->toHaveCount(14)->not->toContain('inagroup-2026-01-02_010000.sql.gz')->toContain('inagroup-2026-01-15_010000.sql.gz');
});

it('mysqldump gagal: pesan jelas dan tidak ada file setengah jadi', function () {
    Process::fake(['*' => Process::result(errorOutput: 'Access denied for user', exitCode: 2)]);

    expect(fn () => $this->backup->create())->toThrow(RuntimeException::class, 'Access denied');
    expect(File::allFiles($this->backup->dir()))->toBeEmpty();
});

it('hanya file backup yang bisa diunduh, nama file lain atau path aneh ditolak', function () {
    File::ensureDirectoryExists($this->backup->dir());
    File::put($this->backup->dir().'/catatan.txt', 'x');

    expect($this->backup->find('catatan.txt'))->toBeNull()
        ->and($this->backup->find('../../.env'))->toBeNull()
        ->and($this->backup->find('inagroup-2026-01-01_010000.sql.gz'))->toBeNull(); // tidak ada filenya
});

it('route menu Backup Database dilindungi permission settings.backup', function () {
    $routes = app('router')->getRoutes();

    expect($routes->getByName('settings.backup.index')->gatherMiddleware())->toContain('permission:settings.backup')
        ->and($routes->getByName('settings.backup.download')->gatherMiddleware())->toContain('permission:settings.backup')
        ->and($routes->getByName('settings.backup.store')->gatherMiddleware())->toContain('permission:settings.backup,edit')
        ->and(collect(config('menu'))->firstWhere('key', 'settings.backup')['group'])->toBe('Settings');
});
