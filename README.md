# State Machine for Eloquent laravel

This package is for implementing the Eloquent State Machine of various Laravel projects.

## Install via composer

Run the following command to pull in the latest version:
```bash
composer require jobmetric/state-machine
```

### Add service provider

Add the service provider to the providers array in the config/app.php config file as follows:

```php
'providers' => [

    ...

    JobMetric\StateMachine\StateMachineServiceProvider::class,
]
```

## Documentation
