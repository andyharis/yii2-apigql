<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 02.08.2017
 * Time: 18:09
 */

namespace andyharis\yii2apigql\components\api;


use andyharis\yii2apigql\components\Yii2ApigqlRecord;
use yii\base\Model;

class AdvancedConditions extends Model
{
  public $chain;
  public $having = false;
  /**
   * @var Yii2ApigqlRecord
   */
  public $model;
  public $customAttributes = [];
  public $condition;
  /**
   * @function
   */
  public $getModel;
  private $_condition;

  public function prepareCondition()
  {
    if (isset($this->condition[0]))
      $this->_condition = $this->prepareAdvancedCondition($this->condition);
    else
      return $this->prepareNormalCondition();
    return $this;
  }

  public function prepareNormalCondition()
  {
    $attribute = array_keys($this->condition)[0] . array_values($this->condition)[0];
    return $this->createCondition($this->model, $attribute, $this->chain);
  }

  public function prepareAdvancedCondition($condition)
  {
    $attributes = [$condition[0]];
    unset($condition[0]);
    foreach ($condition as $eachAttribute) {
      if (!is_array($eachAttribute)) {
        $parts = preg_split('/:/', $eachAttribute);
        $chainParts = preg_split('/\./', $parts[0]);
        $last = count($chainParts) - 1;
        $attribute = $chainParts[$last];
        unset($chainParts[$last]);
        $chain = implode('.', $chainParts);
        $rawCondition = $parts[1];
        $getModel = $this->getModel;
        $model = $getModel($chain);
        $finalAttribute = "$attribute:$rawCondition";
        $conditionObject = $this->createCondition($model, $finalAttribute, $chain, $attribute);
        $attributes[] = $conditionObject->getCondition();
      } else {
        echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
        \andyharis\yii2apigql\components\Helpers::debug(false, [
          'recursiveCondition' => $eachAttribute
        ]);
        exit;
      }
    }
    return $attributes;
  }

  public function createCondition($model, $finalAttribute, $chain, $rawAttribute = false)
  {
    $conditionObject = new Conditions($model, $finalAttribute, $chain, $this->getModel);
    $conditionObject->hasConditions();
    $rawAttribute = $rawAttribute ? $rawAttribute : $conditionObject->rawAttribute;
    if (isset($this->customAttributes[$rawAttribute]))
      $conditionObject->noAlias = true;
    return $conditionObject;
  }


  public function getCondition()
  {
    return $this->_condition;
  }
}