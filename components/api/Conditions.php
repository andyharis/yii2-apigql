<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 28.07.2017
 * Time: 12:35
 */

namespace andyharis\yii2apigql\components\api;


use frontend\components\SneakyRecord;
use yii\base\Model;
use yii\base\UnknownMethodException;
use yii\helpers\Inflector;

class Conditions extends Model
{
  CONST PREFIX = ':';
  public $attribute;
  public $chain;
  public $rawValue;
  public $rawAttribute;
  public $rawCondition;
  public $warnings = [];
  public $noAlias = false;
  private $getModel;
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
//    'radial\(\d+,\d+,\d+\)' => 'radialSearch'
  ];

//  public function __construct(array $config = [])
//  {
//
//    krsort($this->conditions);
//    parent::__construct($config);
//  }

  public function __construct($model, $attribute, $chain, $getModel = false)
  {
    $this->model = $model;
    $this->attribute = $attribute;
    $this->chain = $chain;
    $this->getModel = $getModel;
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
        $rawAttribute = $splitted[0];
        try {
          if ($this->getModel && is_callable($this->getModel)) {
            $possibleChain = preg_split('/\./', $rawAttribute);
            if (count($possibleChain) > 1) {
              $rawAttribute = call_user_func_array($this->getModel, [$possibleChain[count($possibleChain) - 2]])->alias . '.'.$possibleChain[count($possibleChain) - 1];
              $this->noAlias = true;
            }
          }
        } catch (\Throwable $e) {

        }
        $this->rawAttribute = $rawAttribute;
        $this->rawValue = $splitted[1];
        $this->rawCondition = $condition;
        return true;
      } else if (preg_match('/:/', $this->attribute)) {
        $this->warnings[] = "Not found $pattern in {$this->attribute}";
      }
    }
    return false;
  }

  private function getAttribute()
  {
    if ($this->noAlias)
      return $this->rawAttribute;
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

  public function createRadialSearchConditions()
  {
    return [];
  }

  public function getCondition()
  {
    $method = $this->conditions[$this->rawCondition];
    $method = 'create' . Inflector::id2camel($method . 'Conditions');
    if ($this->hasMethod($method))
      return $this->$method();
    else
      throw new UnknownMethodException("Method '$method' not found!");
  }
}