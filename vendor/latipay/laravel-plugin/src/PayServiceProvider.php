<?php

namespace Latipay\LaravelPlugin;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class PayServiceProvider extends ServiceProvider
{
    /**
     * If is defer.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the service.
     *
     * @author mamba <me@mamba.cn>
     */
    public function boot()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/config.php' => config_path('latipay.php'), ],
                'laravel-latipay'
            );
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('latipay');
        }
    }

    /**
     * Register the service.
     *
     * @author mamba <me@mamba.cn>
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/config.php', 'latipay');

        $this->app->singleton('pay.latipay', function () {
            $config = array_only(config('latipay'), [
                'api_key',
                'user_id',
                'versioin',
            ]);
            return Pay::latipay($config);
        });
    }

    /**
     * Get services.
     *
     * @author mamba <me@mamba.cn>
     *
     * @return array
     */
    public function provides()
    {
        return ['pay.latipay'];
    }
}
