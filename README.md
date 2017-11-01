andyharis/yii2-apigql
==========
yii2-apigql provides methods to work with database on CRUD operations

Usage
---
[API docs](docs.md) 

For example we have 3 table/models: Users, Messages, Post.

* `Users` -> hasMany `Post`
* `Post` -> hasMany `Messages`

We want to access clients with all messages including post:
```json
{
  "username": "",
  "avatarUrl": "",
  "post": {
    "postName":"",
    "messages": {
      "textMessage": "",
      "dateAdded": ""
    }
  }
}
```
Make request and get all data with the same format you provided.

GET `/clients?select={"username": "","avatarUrl": "","post": {"postName": "","messages": {"textMessage": "","dateAdded": ""}}}`
```javascript
// response
{
    "username": "Andyhar",
    "avatarUrl": "http://example.com/andyhar.png",
    "post": [
      {
        "postName": "Post about API",
        "dateAdded": "1500276204",
        "messages": [
          {
            "textMessage": "Hey what a nice post!",
            "dateAdded": "1500276704",
          },
          {
            "textMessage": "Make more posts like this!",
            "dateAdded": "1500279841",
          }
        ]
      },
    ]
  }
```
* Access main model and nested relations data with one query.
* Sort by nested relations:
  * `/clients?select={...}&sort=post.postName` - sort by `post.postName ASC`
  * `/clients?select={...}&sort=!post.messages.dateAdded` - sort by `post.messaged.dateAdded DESC`
* Filter data with nested conditions:
  * `/clients?select={"username":"=Andyhar"}` - where `username equals Andyhar`
  * `/clients?select={"post":{"messages":{"textMessage":"~Rocks"}}}` - where `post.messages.textMessage like Rocks`
  * `/clients?select={"post":{"likes":">35"}}` - where `post.likes > 35`


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
Either run
```
composer require andyharis/yii2apigql
```


Getting started
---
After installation you should enable this module extension in your config file:
1. Open your `frontend/config/main.php`
2. Add module `gql` to `bootstrap` section
```php
// main.php
return [
  'id' => 'app-frontend',
  'basePath' => dirname(__DIR__),
  'bootstrap' => ['log', 'gql'],
   // your code
];
``` 
Then you need to initialize component itself.
Just add new component `gql` to list of your components:
```php
// main.php
'components' => [
   'gql' => [
     'class' => "andyharis\yii2apigql\Bootstrap",
     'relations' => require 'models.php'
    ],
    // your code
]
```
Creating file models.php
---
As you can see we `require 'models.php'` to let component know which models to use.

So you probably want create a separate file to store you models for this case.
```php
// models.php
use andyharis\yii2apigql\components\api\Relations;
// Initializing component relations class which will handle dependencies
$object = new Relations();
// Add all models you need to work with
$object
  ->addModel(String $name, String $className)
  ...
  ...
  ->addModel('clients', \frontend\models\Clients::className())
  ->addModel('job', \frontend\models\Job::className());
// we need to return this object with relations
return $object;
``` 
Where:
* `clients` - indicates name for your model
* `\frontend\models\Clients::className()` - indicates what model should use to fetch and update data

Almost there
---
Another important thing is to extend all your models with `Yii2ApigqlRecord` component.
```php
// frontend/models/Clients.php
namespace frontend\models;

use andyharis\yii2apigql\components\Yii2ApigqlRecord;
// This is important, because Yii2ApigqlRecord has some methods which use your models to make magic. 
// Of course you can extend it with your class but don't forget to extend Yii2ApigqlRecord
class Clients extends Yii2ApigqlRecord
```
That's it. Now you can work with `yii2apigql`.

For more info please visit Wiki for API documentation.
