<?php

namespace App\Providers;

use App\Helpers\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerActivityLogging();
    }

    /**
     * Fitur "History/Activity" admin: dengar SEMUA event create/update/delete
     * Eloquent di aplikasi ini lewat listener wildcard ('eloquent.created: *'
     * dkk, format bawaan Laravel untuk "semua model apa pun jenisnya"), lalu
     * teruskan ke App\Helpers\ActivityLogger untuk difilter & dicatat.
     *
     * SENGAJA didaftarkan di sini (bukan per-controller/per-model) supaya
     * menutup SELURUH modul aplikasi tanpa kecuali — termasuk yang menyimpan
     * data langsung lewat Eloquent (bukan lewat App\Helpers\AdminCrud), dan
     * modul baru yang ditambah di kemudian hari otomatis ikut tercatat tanpa
     * perlu ingat menambah kode logging di tempat lain.
     *
     * $data pada payload wildcard Eloquent selalu berupa array 1 elemen berisi
     * instance model yang memicu event tsb — lihat
     * Illuminate\Database\Eloquent\Concerns\HasEvents::fireModelEvent().
     */
    private function registerActivityLogging(): void
    {
        Event::listen('eloquent.created: *', function (string $eventName, array $data): void {
            if ($data[0] instanceof Model) {
                ActivityLogger::recordCreated($data[0]);
            }
        });

        Event::listen('eloquent.updated: *', function (string $eventName, array $data): void {
            if ($data[0] instanceof Model) {
                ActivityLogger::recordUpdated($data[0]);
            }
        });

        Event::listen('eloquent.deleted: *', function (string $eventName, array $data): void {
            if ($data[0] instanceof Model) {
                ActivityLogger::recordDeleted($data[0]);
            }
        });
    }
}
