# State Machine for Eloquent laravel

This package is for implementing the Eloquent State Machine of various Laravel projects.

## Install via composer

Run the following command to pull in the latest version:
```bash
composer require jobmetric/laravel-state-machine
```

## Documentation

When it comes to changing the state of a `model` field, and we want to change it and then do other things or more clearly react to another `action`, you can use this package.

### Usage

#### 1. Suppose you have an order `model` with a `status` field.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'status',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'status' => 'string',
    ];
}
```

#### 2. Now you need to add a `trait` called `HasStateMachine` to the model.

```php
<?php

namespace App\StateMachine;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\HasStateMachine;

class OrderStateMachine extends Model
{
    use HasStateMachine;
    
    ...
}
```

#### 3. When this attribute is added to the model, you will have to add the `StateMachineContract` interface to the model

```php
<?php

namespace App\StateMachine;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\Contracts\StateMachineContract;
use JobMetric\StateMachine\HasStateMachine;

class OrderStateMachine extends Model implements StateMachineContract
{
    use HasStateMachine;
    
    ...
}
```

#### 4. Now you will again have to use the `stateMachineAllowTransition` function in the model.

```php
<?php

namespace App\StateMachine;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\Contracts\StateMachineContract;
use JobMetric\StateMachine\HasStateMachine;

class OrderStateMachine extends Model implements StateMachineContract
{
    use HasStateMachine;
    
    ...
    
    public function stateMachineAllowTransition(): void
    {
        $this->allowTransition('status', 'pending', 'processing');
        $this->allowTransition('status', 'processing', 'completed');
    }
}
```

> Note: The `stateMachineAllowTransition` function is used to define the `transition` of the `model` field. In the above example, the `status` field can be changed from `pending` to `processing` and from `processing` to `completed`.

### Why did we do these things?

By doing this, you are defining the states in which status wants to move.

Starting now, manual state manipulation is no longer permitted. Instead, you must navigate between state components using the functions provided by StateMachine. It will handle the update process and a sequence of additional tasks, which will be elaborated upon shortly.

```php
$order = Order::find(1);

$order->status = 'processing';

$order->save();
```

> Note: The above code will not work. Because the `status` field can only be changed from `pending` to `processing` and from `processing` to `completed`.
> 
> If you want to change the `status` field from `pending` to `processing`, you must use the following code.

```php
$order = Order::find(1);

$order->transitionTo('processing');
```

> Note: If it was a field other than status, you can use the second parameter for the name of that field.

### Let's go to the amazing part of our story

When a `transitionTo` occurs, you can have an action for that event that will be executed automatically if there is one.

### Let's go to the actions

To define a `StateMachine`, the following method must be executed:

```php
php artisan make:state-machine {model} {?state} -f={field}
```

> ***model***: The name of the model you want to create a `StateMachine` for.
> 
> - The model must be available in the system
>
> ***state***: The name of the state you want to create a `StateMachine` for.
> 
> - This part should be written like this, for example `PendingToProcessing` and the word `To` must be between two situations.
> 
> - If this field is not filled, a status called `Common` will be created, which will be explained below.
>
> ***field***: The name of the field you want to create a `StateMachine` for.
> 
> - This is the default option on the status field, and if you want to define another field, use this

When you run this command, a file is created inside `app/StateMachines`

In the created file, you have two methods, before and after, which tells you that you want to do it before changing the field in the database or after changing the field in the database.

In each of them, you can use different tasks such as sending e-mail or many other things.

### The difference between Common and detailed files

The difference between the two Common files and PendingToProcessing mode, for example, is in their execution

The `common` file is executed for `all` conditions, but the `exact state` file is executed only for that specific mode, and the form of their execution is as follows

```php
$common->before();
$exact->before();
// state change
$exact->after();
$common->after();
```
