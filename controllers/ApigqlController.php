<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 17:50
 */

namespace andyharis\yii2apigql\controllers;

use andyharis\yii2apigql\components\API;
use andyharis\yii2apigql\components\api\Core;
use andyharis\yii2apigql\components\api\Data;
use andyharis\yii2apigql\components\api\Update;
use andyharis\yii2apigql\components\Helpers;
use yii\helpers\Json;

class BB
{
  public $a = 1;
}

class ApigqlController extends API
{

  public function actionTable($table)
  {
    $bootstrap = \Yii::$app->gql;
    $params = Data::prepareParams($_GET);
    $core = new Core($table, $params);
    $core->prepareQuery();
    $result = $core->execute();
    $data = $result['data'];
    if (isset($_GET['ss'])) {
      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
      \frontend\components\Helpers::debug(false, $data);
      exit;
    }
    if ($result['success'])
      return Helpers::result(true, $data, $bootstrap->apiMessage, [], $result['count']);
    return Helpers::error($data);
  }

  public function actionUpdate($table, $id = null)
  {
    $data = false;
    try {
      $json = file_get_contents('php://input');
      if ($json != null) {
        $data = Json::decode($json);
      }
    } catch (\Throwable $e) {
      $data = $_POST['data'] ?? false;
    }
    if (!$data)
      return Helpers::error(['No data found!']);
    $core = new Update($table, $data['data'], $id);
    $core->validateUpdate();
    $result = $core->execute();
    if (!$core->hasErrors())
      return Helpers::result(true, $result, false, [], 1);
    return Helpers::error($core->errors);
  }
}
