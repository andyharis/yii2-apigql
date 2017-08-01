andyharis/yii2-apigql
==========
yii2-apigql provides methods to work with database on CRUD operations

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
Either run
```
composer require andyharis/yii2apigql
```
Usage
---
After installation you should enable this module extension in your config file:
1. Open your `frontend/config/main.php`
2. Add module `gql` to `bootstrap` section
```php
return [
  'id' => 'app-frontend',
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log', 'gql'],
   ...
];
``` 
Then you need to initialize component itself.
Just add new component `gql` to list of your components:
```php
'components' => [
   'gql' => [
     'class' => "andyharis\yii2apigql\Bootstrap",
     // required attribute
     'relations' => require 'models.php'
    ],
    ...
]
```
Now you need to create an array of all models you want to work with.
I prefer to create a separete file called `models.php` which i require in component `relations` attribute.



Creating file models.php
---
```php
use andyharis\yii2apigql\components\api\Relations;
// Initializing component relations class which will handle dependencies
$object = new Relations();
// Add all models you need to work with
$object
  ->addModel('clients', \frontend\models\Clients::className())
  ->addModel('item', \frontend\models\Item::className())
  ->addModel('job', \frontend\models\Job::className())
  ->addModel('quote', \frontend\models\Quote::className());
// we need to return this object with relations
return $object;
```

Almost there
---
Another important thing is to extend all used models with `gql` component
```php
namespace frontend\models;

use andyharis\yii2apigql\components\Yii2ApigqlRecord;

class Clients extends Yii2ApigqlRecord
```
That's it. Now you can work with `yii2apigql`.

Just make a request to `gql/clients?select=name,age` and you will get your result.
Where `clients` you model name and `name,age` are attributes from current model.

For more info please visit Wiki for API documentation.
