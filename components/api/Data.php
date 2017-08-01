<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 18:21
 */

namespace andyharis\yii2apigql\components\api;


use yii\helpers\Json;

class Data
{

  public static function prepareParams($params)
  {
    $data = [
      'select' => [],
      'limit' => \Yii::$app->gql->limit,
      'where' => [],
      'offset' => 0,
      'sort' => []
    ];
    $isset = function ($keys, $key) use (&$data, $params) {
      foreach ($keys as $each) {
        if (isset($params[$each]))
          $data[$key] = $params[$each];
      }
      return $data;
    };
    if (isset($params['select']))
      $data['select'] = self::fetch($params['select']);
    $data = $isset(['limit', 'per-page'], 'limit');
    $data = $isset(['page', 'offset'], 'offset');
    if (isset($params['sort']))
      $data['sort'] = preg_split('/,/', $params['sort']);
    return $data;
  }

  public static function fetch($string)
  {
    $select = [];
    try {
      $json = Json::decode($string);
      $select = self::combine($json);
    } catch (\Throwable $e) {
//      $conditions = Conditions::$conditions();
//      $prefix = Conditions::PREFIX;
//      $pattern = "/(\w+$prefix([$conditions]+|)[A-Za-z0-9\-]+)/";
//      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
//      \frontend\components\Helpers::debug(false,$pattern);
//      exit;
//      $r = preg_replace($pattern, "'$0'", $string);
//      $r = preg_replace('/(\w+' . Conditions::PREFIX . '([<>!=~]+|)[A-Za-z0-9\-]+|(count\(([A-Za-z_]+)\) AS (\w+))|\w+)/', "'$0'", $string);
//      $r = "return [" . preg_replace('/(\'\w+\')(\[)/', "$1=>[", $r) . "];";
//      $select = eval($r);
    }
    if (isset($_GET['d'])) {
      echo "<pre>";
      print_r($select);
      echo "</pre>";
      exit;
    }
    return $select;
  }

  public static function combine($json, $select = [])
  {
    foreach ($json as $key => $array) {
      if (is_array($array))
        $select[$key] = self::combine($array);
      else
        $select[] = $array ? $key.$array : $key;
    }
    return $select;
  }
}