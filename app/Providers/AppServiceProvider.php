<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\SaleBanner;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Cache key for the active sale banners used on the storefront.
     */
    public const SALE_BANNERS_CACHE_KEY = 'sale_banners.active';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // `php artisan serve` sanitises the environment of the child PHP server
        // process, keeping only the names listed in ServeCommand::$passthroughVariables.
        // That list uses upper-case Windows key names ("SYSTEMROOT", "PATH"), but
        // PHP reports them with their native casing ("SystemRoot", "Path") and the
        // in_array() check inside ServeCommand is case-sensitive. As a result,
        // SystemRoot is stripped from the child process on Windows and PHP's
        // built-in server cannot bind the socket, failing with
        // "Failed to listen on 127.0.0.1:8000 (reason: ?)".
        //
        // Add the natively-cased names so the real `php artisan serve` (including
        // its .env reload behaviour) works on Windows.
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique(array_merge(
                ServeCommand::$passthroughVariables,
                ['SystemRoot', 'windir', 'Path'],
            )));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();

        View::composer('maindesign', function ($view) {
            $view->with('activeSaleBanners', $this->cachedActiveBanners());
            $view->with('tickerBanners', $this->cachedTickerBanners());
        });

        // The cart badge is rendered by the shared layout on every page. Scoping
        // this to the layout (rather than a '*' composer) keeps it to a single
        // lightweight query per request instead of running on every nested view.
        View::composer('maindesign', function ($view) {
            $view->with('cartCount', Auth::check()
                ? Cart::where('user_id', Auth::id())->count()
                : 0);
        });
    }

    protected function activeBannersQuery()
    {
        return SaleBanner::active()
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }

    protected function cachedActiveBanners()
    {
        return Cache::remember(self::SALE_BANNERS_CACHE_KEY, now()->addMinutes(15), function () {
            return $this->activeBannersQuery()->take(3)->get();
        });
    }

    protected function cachedTickerBanners()
    {
        return Cache::remember(self::SALE_BANNERS_CACHE_KEY.'.ticker', now()->addMinutes(15), function () {
            return $this->activeBannersQuery()
                ->where('show_in_ticker', true)
                ->get();
        });
    }
}
