<?php

namespace Crater\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapThree();
        $this->loadJsonTranslationsFrom(resource_path('scripts/locales'));

        $this->configureRateLimiting();

        if (\Storage::disk('local')->has('database_created') && Schema::hasTable('abilities')) {
            $this->addMenus();
        }
    }

    /**
     * Rate limiter for the AI document assistant: each call hits the paid
     * Perplexity API, so it is capped per user, well below the global API
     * throttle.
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('ai-assistant', function (Request $request) {
            return Limit::perMinute((int) config('ai-assistant.rate_limit.per_minute', 10))
                ->by($request->user()->id)
                ->response(function () {
                    return response()->json([
                        'error' => 'Trop de demandes à l’assistant IA. Veuillez patienter une minute.',
                    ], 429);
                });
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function addMenus()
    {
        //main menu
        \Menu::make('main_menu', function ($menu) {
            foreach (config('crater.main_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        //setting menu
        \Menu::make('setting_menu', function ($menu) {
            foreach (config('crater.setting_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });

        \Menu::make('customer_portal_menu', function ($menu) {
            foreach (config('crater.customer_menu') as $data) {
                $this->generateMenu($menu, $data);
            }
        });
    }

    public function generateMenu($menu, $data)
    {
        $menu->add($data['title'], $data['link'])
            ->data('icon', $data['icon'])
            ->data('name', $data['name'])
            ->data('owner_only', $data['owner_only'])
            ->data('ability', $data['ability'])
            ->data('model', $data['model'])
            ->data('group', $data['group']);
    }
}
