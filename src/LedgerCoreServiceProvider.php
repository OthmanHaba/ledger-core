<?php

declare(strict_types=1);

namespace LedgerCore;

use Illuminate\Support\ServiceProvider;
use LedgerCore\Contracts\AccountResolverContract;
use LedgerCore\Contracts\CurrencyConverterContract;
use LedgerCore\Contracts\PostingValidatorContract;
use LedgerCore\Services\LedgerManager;
use LedgerCore\Support\DefaultAccountResolver;
use LedgerCore\Support\NoopCurrencyConverter;
use LedgerCore\Support\NullPostingValidator;

final class LedgerCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ledger.php', 'ledger');

        $this->app->singleton(AccountResolverContract::class, DefaultAccountResolver::class);
        $this->app->singleton(PostingValidatorContract::class, NullPostingValidator::class);
        $this->app->singleton(CurrencyConverterContract::class, NoopCurrencyConverter::class);
        $this->app->singleton('ledger-core', LedgerManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ledger.php' => config_path('ledger.php'),
        ], 'ledger-core-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'ledger-core-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/ledger-core'),
        ], 'ledger-core-views');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ledger-core');
    }
}
