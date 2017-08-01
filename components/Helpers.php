<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 18:15
 */

namespace andyharis\yii2apigql\components;


class Helpers
{
  public static function result($success, $data = [], $message = 'Success', $errors = [], $count = false)
  {
    return [
      'success' => $success,
      'totalCount' => $count ? $count : count($data),
      'data' => $data,
      'message' => !$message ? \Yii::$app->gql->apiMessage : $message,
      'errors' => $errors
    ];
  }

  public static function error($errors = [], $message = 'Error!')
  {
    \Yii::$app->response->statusCode = 400;
    return self::result(false, [], $message, $errors);
  }

  public static function oSet($data, $chain, $value)
  {
    $ierarchy = '';
    $chain = is_array($chain) ? $chain : preg_split('/\./', $chain);
    foreach ($chain as $key) {
      $ierarchy .= $key == '[]' ? "[]" : "[\"$key\"]";
    }
    $string = 'return $data' . $ierarchy . ' = $value;';
    try {
      eval($string);
    } catch (\Throwable $e) {
      echo "<pre>";
      print_r($string);
      echo "</pre>";
      exit;
    }
    return $data;
  }

  public static function array_unset($source, $target, $return = false)
  {
    $targetData = [];
    foreach ($source as $key => $value) {
      try {
        $targetData[$key] = $target[$key];
        unset($target[$key]);
      } catch (\Throwable $e) {
      }
    }
    if ($return)
      return [$target, $targetData];
    return $target;
  }

  public static function array_delete_array(Array $array)
  {
    $data = [];
    foreach ($array as $each) {
      if (!is_array($each))
        $data[] = $each;
    }
    return $data;
  }


  /**
   * @param $e \Throwable
   * @param $data
   */
  public static function debug($e, $data, $dump = false)
  {
//    preg_match('/\w+\.php/', __FILE__, $file);
    if ($e != false) {
      $data['errors'] = [
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
      ];
    }
    echo "<div><pre>";
    $dump ? var_dump($data) : print_r($data);
    echo "</pre></div>";
  }

}