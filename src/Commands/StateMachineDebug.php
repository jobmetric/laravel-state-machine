<?php

namespace JobMetric\StateMachine\Commands;

use Illuminate\Console\Command;
use JobMetric\PackageCore\Commands\ConsoleTools;
use Throwable;

class StateMachineDebug extends Command
{
    use ConsoleTools;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'state-machine:debug
                {model_type : The full class name of the model}
                {model_id : The ID of the model}
                {--field=status : The state machine field to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show current state and possible transitions for a given model instance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $modelType = $this->argument('model_type');
        $modelId = $this->argument('model_id');
        $field = $this->option('field');

        if (!class_exists($modelType)) {
            $this->message("Model class '$modelType' not found.", "error");

            return self::FAILURE;
        }

        $model = $modelType::find($modelId);

        if (!$model) {
            $this->message("No model found with ID [$modelId].", "error");

            return self::FAILURE;
        }

        try {
            $transitions = $model->stateMachines();
            $current = $model->getState($field);

            if (empty($transitions)) {
                $this->message("No transitions available from current state.", "warning");

                return self::FAILURE;
            }

            if (is_null($current)) {
                $this->message("Model does not have a state set for field '$field'.", "warning");
            }

            $this->info("Model: {$this->writeText($modelType, 'blue')} (ID: {$this->writeText($modelId, 'magenta')})");
            $this->info("Current State [{$this->writeText($field)}]: {$this->writeText($current, 'magenta')}");

            $possible_transitions = [];
            foreach ($transitions[$field] as $transition) {
                if ($transition['from'] === $current) {
                    $possible_transitions[] = $transition['to'];
                }
            }

            if (empty($possible_transitions)) {
                $this->info("Possible Transitions from [{$this->writeText($current)}]: No valid transitions available from current state '{$this->writeText($current, 'magenta')}'.");
            } else {
                $this->info("Possible Transitions from [{$this->writeText($current)}]: {$this->writeText(implode(', ', $possible_transitions), 'magenta')}");
            }

            $this->info("Available Transitions:");
            foreach ($transitions[$field] as $transition) {
                $this->line(" - {$this->writeText($transition['from'], 'yellow')} {$this->writeText("→")} {$this->writeText($transition['to'], 'yellow')}");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->message("Error: " . $e->getMessage(), "error");

            return self::FAILURE;
        }
    }
}
