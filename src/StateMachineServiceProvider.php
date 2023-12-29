<?php

namespace JobMetric\StateMachine;

use JobMetric\PackageCore\PackageCore;
use JobMetric\PackageCore\PackageCoreServiceProvider;
use JobMetric\StateMachine\Commands\MakeStateMachine;

class StateMachineServiceProvider extends PackageCoreServiceProvider
{
    public function configuration(PackageCore $package): void
    {
        $package->name('laravel-state-machine')
            ->registerCommand(MakeStateMachine::class);
    }
}
