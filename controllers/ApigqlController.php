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
      Helpers::debug(false, $data);
      exit;
    }
    if ($result['success']) {
      $return = Helpers::result(true, $data, $bootstrap->apiMessage, [], $result['count']);
      if ($bootstrap->postProcessing !== false) {
        $post = new $bootstrap->postProcessing($data, $table);
        try {
          return Helpers::result(true, $post->init(), $bootstrap->apiMessage, [], $result['count']);
        } catch (\Throwable $e) {
          echo "<pre>";
          print_r([
            'message' => "You should add method `init` to your post processing class, so it could return changed data.",
            'class' => $bootstrap->postProcessing,
            'error' => [
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine()
            ],
            'object' => $post,
          ]);
          echo "</pre>";
          exit;
        }
      }
      return $return;
    }
    return Helpers::error($data);
  }

  public function actionUpdate($table, $id = null)
  {
    $bootstrap = \Yii::$app->gql;
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
    if (!$core->hasErrors()) {
      if ($bootstrap->postProcessing !== false) {
        $post = new $bootstrap->postProcessing($result, $table);
        try {
          return Helpers::result(true, $post->init(), $bootstrap->apiMessage, [], 1);
        } catch (\Throwable $e) {
          echo "<pre>";
          print_r([
            'message' => "You should add method `init` to your post processing class, so it could return changed data.",
            'class' => $bootstrap->postProcessing
          ]);
          echo "</pre>";
          exit;
        }
      }
      return Helpers::result(true, $result, false, [], 1);
    }
    return Helpers::error($core->errors);
  }
}
