<?php

namespace andyharis\yii2apigql;

use andyharis\yii2apigql\controllers\ApigqlController;
use yii\base\BootstrapInterface;

/**
 * This is just an example.
 */
class Bootstrap implements BootstrapInterface
{
  public $rules = [
    'GET gql/<table:\w+>' => 'gql/table',
    'POST gql/<table:\w+>/<id:.+>' => 'gql/update',
    'POST gql/<table:\w+>' => 'gql/update',
  ];

  public $relations = false;
  public $conditions = false;
  public $limit = 25;
  public $apiMessage = 'Record(s) found.';

  public function bootstrap($app)
  {
    $app->controllerMap['gql'] = ApigqlController::className();
    $app->urlManager->addRules($this->rules);
  }
}
