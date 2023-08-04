<?php

namespace JobMetric\Translation;

use Illuminate\Support\ServiceProvider;
use JobMetric\StateMachine\StateMachineService;

class StateMachineServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('JStateMachine', function ($app) {
            return new StateMachineService($app);
        });

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'j-state-machine');
    }

    /**
     * boot provider
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishables();

        // set translations
        $this->loadTranslationsFrom(realpath(__DIR__.'/../lang'), 'j-state-machine');
    }

    /**
     * register publishables
     *
     * @return void
     */
    protected function registerPublishables(): void
    {
        if($this->app->runningInConsole()) {
            // publish config
            $this->publishes([
                realpath(__DIR__.'/../config/config.php') => config_path('j-state-machine.php')
            ], 'state-machine-config');
        }
    }
}
