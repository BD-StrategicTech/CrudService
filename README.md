# CrudService

## Installation

`composer require budgetdumpster/crud-service:dev-master`

## Tests

This package comes with a full test suite, and that test suite can be run by executing the command `phpunit` in the package root directory.

## Usage

The `CrudService` package was designed to work with Eloquent Models (Laravel) and creates an unspecific service that will allow you to
perform CRUD operations on your data and models.

### Retrieve Single Model

```php
<?php

use \BudgetDumpster\Services\CRUDService;
use \BudgetDumpster\Exceptions\ModelNotFoundException;
use \YourNameSpace\Models\Person;
use \Monolog\Logger;

$id = 'abcdef123456789';
$logger = new Logger('test');
// continue configuring monolog

$crudService = new CRUDService($logger);
$person = new Person();

try {
    model = $crudService->retrieve($person, $id);
} catch (ModelNotFoundException $e) {
   // log the error
}
```

This is a base use case for retrieving a single model by id, there's a built in way of `Eager Loading` the relationships, which is simply to provide a public property on the model that is called `relationNames`. The relation names are just strings that match the name of the relationship methods in the model. Relationships can be loaded in a normal manner by referring to that property, the `relationNames` property only comes into play if the relationships need to be eager loaded.

### Create Single Model

```php
<?php 

use \BudgetDumpster\Services\CRUDService;
use \BudgetDumpster\Exceptions\ModelNotFoundException;
use \YourNameSpace\Models\Person;
use \Monolog\Logger;
use \RuntimeException;

$data = [
    'first_name' => 'Test',
    'last_name' => 'Person',
    'phone' => '555-555-5555',
    'email' => 'test@test.com'
];

$logger = new Logger('test');
//continue configuring logger

$crudService = new CrudService($logger);
$person = new Person();
$id = 'abcdef123456789';

try {
    $model = $crudService->create($person, $data, $id);
} catch (RuntimeException $e) {
    // log error
}
```

### Update Single Model

```php
<?php
use \BudgetDumpster\Services\CRUDService;
use \BudgetDumpster\Exceptions\ModelNotFoundException;
use \YourNameSpace\Models\Person;
use \Monolog\Logger;
use \RuntimeException;

$data = [
    'first_name' => 'Test',
    'last_name' => 'Person',
    'phone' => '555-555-5554',
    'email' => 'test@test.com'
];

$logger = new Logger('test');
// continue configuring logger

$crudService = new CRUDService($logger);
$person = new Person;
$id = '123456789abcdef';

try {
    $model = $crudService->update($person, $data, $id);
} catch (ModelNotFoundException $e) {
    // log error
} catch (RuntimeException $e) {
    // log error
}
```

### Delete Single Model

```php
<?php
use \BudgetDumpster\Services\CRUDService;
use \BudgetDumpster\Exceptions\ModelNotFoundException;
use \YourNameSpace\Models\Person;
use \Monolog\Logger;
use \RuntimeException;

$logger = new Logger('test');
// continue configuring logger

$crudService = new CRUDService($logger);
$person = new Person;
$id = '123456789abcdef';

try {
    // returns a boolean value to identify whether the method call was successful
    $result = $crudService->delete($person, $id);
} catch (ModelNotFoundException $e) {
    // log error
} catch (RuntimeException $e) {
    // log error
}
```

### Retrieve Collection of Models

```php
<?php
use \BudgetDumpster\Services\CRUDService;
use \BudgetDumpster\Exceptions\ModelNotFoundException;
use \YourNameSpace\Models\Person;
use \Monolog\Logger;
use \RuntimeException;
use \InvalidArgumentException;

$logger = new Logger('test');
// continue configuring logger

$crudService = new CRUDService($logger);
$person = new Person;
$page = 1;
$per_page = 1;
// optional parameter - defaults to id != null
$filter = ['field' => 'first_name', 'operator' => '=', 'value' => 'Test'];

try {
    $collection = $crudService->retrieveAll($person, $page, $per_page, $filter);
} catch (InvalidArgumentException $e) {
    // log error
} catch (RuntimeException $e) {
    // log error
}
```
