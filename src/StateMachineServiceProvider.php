<?php

namespace JobMetric\StateMachine;

use Illuminate\Contracts\Container\BindingResolutionException;
use JobMetric\EventSystem\Support\EventRegistry;
use JobMetric\PackageCore\PackageCore;
use JobMetric\PackageCore\PackageCoreServiceProvider;
use JobMetric\StateMachine\Commands\MakeStateMachine;
use JobMetric\StateMachine\Commands\StateMachineDebug;

class StateMachineServiceProvider extends PackageCoreServiceProvider
{
    public function configuration(PackageCore $package): void
    {
        $package->name('laravel-state-machine')
            ->hasTranslation()
            ->registerCommand(MakeStateMachine::class)
            ->registerCommand(StateMachineDebug::class);
    }

    /**
     * after boot package
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function afterBootPackage(): void
    {
        // Register events if EventRegistry is available
        // This ensures EventRegistry is available if EventSystemServiceProvider is loaded
        if ($this->app->bound('EventRegistry')) {
            /** @var EventRegistry $registry */
            $registry = $this->app->make('EventRegistry');

            // StateMachine Events
            $registry->register(\JobMetric\StateMachine\Events\StateTransitioned::class);
        }
    }
}
