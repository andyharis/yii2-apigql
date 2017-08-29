<?php

namespace andyharis\yii2apigql;

use andyharis\yii2apigql\components\api\Conditions;
use andyharis\yii2apigql\components\api\Relations;
use andyharis\yii2apigql\components\api\Select;
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

  public $relations = Relations::class;
  public $conditions = Conditions::class;
  public $select = Select::class;
  public $postProcessing = false;
  public $limit = 25;
  public $apiMessage = 'Record(s) found.';

  public function bootstrap($app)
  {
    $app->controllerMap['gql'] = ApigqlController::className();
    $app->urlManager->addRules($this->rules);
  }
}
