<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 01.08.2017
 * Time: 16:06
 */

namespace andyharis\yii2apigql\components\api;


use http\Exception\BadMethodCallException;
use yii\base\Model;
use yii\helpers\Inflector;

class Select extends Model
{
  public $model;
  public $attribute;
  public $hasCustomAttribute = false;
  public $customAttribute;
  public $rawAttribute;


  public function __construct($model, $attribute)
  {
    $this->model = $model;
    $this->attribute = $attribute;
    $this->checkForCustomSelect();
  }

  public function addSelectAttribute()
  {
    $attribute = $this->checkForCustomSelect();
    return $attribute;
  }

  private function checkForCustomSelect()
  {
    if (preg_match('/:fn\w+/', $this->attribute, $match)) {
      $function = $this->getFnName($match[0]);
      $args = $this->getFnArgs($this->attribute, $match[0]);
      $this->rawAttribute = preg_split('/:fn/', $this->attribute)[0];
      if ($this->hasMethod($function)) {
        $this->hasCustomAttribute = true;
        $this->customAttribute = $this->$function($args);
      } else
        throw new \BadMethodCallException("Method $function not found!");
    }
    return $this;
  }

  public function getFnName($name)
  {
    return 'select' . str_replace(':fn', '', $name);
  }

  public function getFnArgs($function, $part)
  {
    $result = preg_split("/$part/", $function);
    $stringArgs = preg_replace('/[()]/', '', $result[1]);
    $args = preg_split('/,/', $stringArgs);
    return $args;
  }


  public function selectConcat($args)
  {
    $select = [];
    foreach ($args as $attribute) {
      $select[] = $this->model->hasAttribute($attribute) ? $this->model->alias . '.' . $attribute : $attribute;
    }
    $select = implode(',', $select);
    $select = "(concat($select)) as {$this->rawAttribute}";
    return [$select];
  }

}