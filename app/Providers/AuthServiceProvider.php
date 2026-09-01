<?php

namespace App\Providers;

use App\Models\User;
use App\Services\DisposisiRuleService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Otorisasi terpusat: apakah $user boleh mengirim disposisi ke $penerima.
        Gate::define('kirim-disposisi', function (User $user, User $penerima) {
            return app(DisposisiRuleService::class)->bolehDisposisi($user, $penerima);
        });

        // Otorisasi terpusat: apakah $user boleh menandai disposisi "selesai".
        Gate::define('selesaikan-disposisi', function (User $user) {
            return app(DisposisiRuleService::class)->bolehMenyelesaikan($user);
        });
    }
}
