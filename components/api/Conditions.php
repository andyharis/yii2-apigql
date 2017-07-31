<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 28.07.2017
 * Time: 12:35
 */

namespace andyharis\yii2apigql\components\api;


use frontend\components\SneakyRecord;
use Prophecy\Exception\Doubler\MethodNotFoundException;
use yii\base\Object;
use yii\helpers\Inflector;

class Conditions extends Object
{
  CONST PREFIX = ':';
  public $attribute;
  public $chain;
  public $rawValue;
  public $rawAttribute;
  public $rawCondition;
  /**
   * @var SneakyRecord
   */
  public $model;
  public $conditions = [
    '=' => 'equals',
    '~' => 'like',
    '<' => 'lt',
    '<=' => 'lteq',
    '>' => 'gt',
    '>=' => 'gteq',
  ];

  public function __construct($model, $attribute,$chain)
  {
    $this->model = $model;
    $this->attribute = $attribute;
    $this->chain = $chain;
    krsort($this->conditions);
    parent::__construct([]);
  }

  public function hasConditions()
  {
    foreach ($this->conditions as $condition => $method) {
      $pattern = self::PREFIX . $condition;
      //    /(\w+\d+)^{$pattern}$(.+)/
      if (preg_match("/{$pattern}/", $this->attribute)) {
        $splitted = preg_split("/$pattern/", $this->attribute);
        $this->rawAttribute = $splitted[0];
        $this->rawValue = $splitted[1];
        $this->rawCondition = $condition;
        return true;
      }
    }
    return false;
  }


  private function getAttribute()
  {
    return "{$this->model->alias}.{$this->rawAttribute}";
  }

  public function createEqualsConditions()
  {
    return [$this->getAttribute() => $this->rawValue];
  }

  public function createLikeConditions()
  {
    return ['like', $this->getAttribute(), $this->rawValue];
  }

  public function createLtConditions()
  {
    return ['<', $this->getAttribute(), $this->rawValue];
  }

  public function createLteqConditions()
  {
    return ['<=', $this->getAttribute(), $this->rawValue];
  }

  public function createGtConditions()
  {
    return ['>', $this->getAttribute(), $this->rawValue];
  }

  public function createGteqConditions()
  {
    return ['>=', $this->getAttribute(), $this->rawValue];
  }

  public function getCondition()
  {
    $method = $this->conditions[$this->rawCondition];
    $method = 'create' . Inflector::id2camel($method . 'Conditions');
    if ($this->hasMethod($method))
      return $this->$method();
    else
      throw new MethodNotFoundException("Method '$method' not found!", self::className(), $method);
  }
}