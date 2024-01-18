<?php

namespace JobMetric\StateMachine\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JobMetric\PackageCore\Commands\ConsoleTools;

class MakeStateMachine extends Command
{
    use ConsoleTools;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:state-machine
                {model : Eloquent model name}
                {state? : State name}
                {--f|field=status : Field name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new State Machine';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $appNamespace = trim(appNamespace(), "\\");

        $model = $this->argument('model');
        $model = Str::studly($model);

        if (!class_exists($appNamespace . "\\Models\\" . $model)) {
            $this->message("Model <options=bold>$model</> not found.", 'error');

            return 1;
        }

        $state = $this->argument('state');
        if ($state) {
            $state = Str::studly($state);
        } else {
            $state = 'Common';
        }

        $field = $this->option('field');
        $field = Str::studly($field);

        if ($this->isFile($appNamespace . "/StateMachines/$model/{$model}{$field}{$state}StateMachine.php")) {
            $this->message("State Machine already exists.", "error");

            return 2;
        }

        $content = $this->getStub(__DIR__ . "/stub/state-machine", [
            'appNamespace' => $appNamespace,
            'model' => $model,
            'field' => $field,
            'state' => $state,
        ]);

        $path = base_path("$appNamespace/StateMachines/$model");
        if (!$this->isDir($path)) {
            $this->makeDir($path);
        }

        if (!$this->isFile("$path/{$model}{$field}{$state}StateMachine.php")) {
            $this->putFile("$path/{$model}{$field}{$state}StateMachine.php", $content);
        }

        $this->message("State Machine <options=bold>[$path/{$model}{$field}{$state}StateMachine.php]</> created successfully.", "success");

        return 0;
    }
}
