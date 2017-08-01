<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 18:19
 */

namespace andyharis\yii2apigql\components\api;

use andyharis\yii2apigql\components\Helpers;
use yii\base\Object;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

class Core extends Object
{
  const JOIN = 0;
  const JOIN_WITH = 1;

  public $table;
  public $model;
  /**
   * @var ActiveQuery
   */
  public $query;
  public $className;

  public $select = [];
  public $limit = 25;
  public $sort = [];
  public $where = [];
  public $offset = 0;

  public $with = [];
  public $joinWith = [];

  public $aliasChains = [];
  public $attributes = [];
  public $warnings = [];
  /**
   * @var Relations
   */
  private $relations;
  /**
   * @var Conditions
   */
  private $conditions;

  public function __construct($table, array $params = [])
  {
    $relations = \Yii::$app->gql->relations;
    $className = $relations->getClass($table);
    $model = new $className();
    $model->generateAlias();
    $query = $className::find()
      ->asArray()
      ->alias($model->alias)
      ->groupBy($this->addAttribute($model, $model->PK));
    $this->aliasChains[$table] = $model->alias;
    $this->className = $className;
    $this->table = $table;
    $this->model = $model;
    $this->query = $query;
    $this->relations = $relations;
    $this->conditions = Conditions::className();
    foreach ($params as $k => $param) {
      $this->$k = $param;
    }
    parent::__construct([]);
  }


  public function prepareQuery()
  {
    return $this->prepareSelect()->prepareLimit()->prepareSort()->prepareWhere()->prepareRelations();
  }

  private function prepareSelect()
  {
    $this->query = $this->recursiveSelect($this->select, $this->model, $this->query, []);
//    $this->query->select(array_unique(array_merge($attributes, $this->attributes)));
    return $this;
//    return $this;
  }


  public function getModelAttributes($model, $attributes, $full = false)
  {
    $data = [];
    $least = [];
    foreach ($attributes as $k => $attribute) {
      if (!is_array($attribute) && $model->hasProperty($this->getRawAttribute($attribute)))
        $data[] = $this->getRawAttribute($attribute);
      else
        $least[$k] = $attribute;
    }
    if ($full)
      return [$data, $least];
    return $data;
  }

  private function prepareJoin($relationModel, $relatedTable, $attributes, $chain, $query)
  {
    $model = $relationModel->primaryModel;
    $joinModel = new $relationModel->modelClass();
    $joinModel->generateAlias();
    $resultAttributes = $this->getModelAttributes($joinModel, $attributes, true);
    $currentAttributes = $resultAttributes[0];
    $leastAttributes = $resultAttributes[1];
    $relationLink = $this->getRelationLink($model, $relatedTable);
    $chain[] = $relatedTable;
    $stringChain = implode('.', $chain);
    $joinAttributes = array_merge($currentAttributes, [
      $relationLink[0]
    ]);
    $where = [];
    $this->with[$stringChain] = [
      'join' => [
        function ($params, $query, $primaryModel, $join) use ($joinModel, $relatedTable, $stringChain, $joinAttributes, $relationLink) {
          $query->addSelect($this->addAttribute($primaryModel, $relationLink[1]));
          return [
            $relatedTable => function ($q) use ($joinModel, $stringChain, $joinAttributes, $params, $query, $relationLink, $primaryModel, $join) {
              foreach ($this->where as $conditions) {
                if ($conditions->chain == $stringChain)
                  $q->andWhere($conditions->getCondition());
              }
//              if (isset($this->joinWith[$stringChain]) && isset($this->joinWith[$stringChain]['sort']))
//                $q->addOrderBy($this->joinWith[$stringChain]['sort']);
              $q->alias($joinModel->alias)
                ->groupBy($this->addAttribute($joinModel, $joinModel->PK))
                ->addSelect($joinAttributes);
              $cb = function ($key) use ($stringChain) {
                $currentParts = preg_split('/\./', $stringChain);
                $nextParts = preg_split('/\./', $key);
                $return = false;
                if (count($currentParts) == count($nextParts) - 1) {
                  for ($i = 0; $i < count($currentParts); $i++)
                    $return = $currentParts[$i] == $nextParts[$i];
                }
                return $return;
              };
              $withs = $join ? $this->joinWith : $this->with;
              if ($this->checkIsDone($stringChain, $join))
                $this->appendRelations($withs, $joinModel, $q, $join, $cb);
              return $q;
            }];
        }
      ],
      'attributes' => $joinAttributes,
      'done' => false
    ];
    $this->aliasChains[implode('.', $chain)] = $joinModel->alias;
    $query = $this->recursiveSelect($attributes, $joinModel, $query, $chain);
    return $query;
  }

  public function checkIsDone($path, $join)
  {
    if ($join)
      return $this->joinWith[$path]['done'] ?? false;
    return $this->with[$path]['done'] ?? false;
  }

  private function recursiveSelect(Array $select, ActiveRecord $model, ActiveQuery $query, Array $chain = [])
  {
    foreach ($select as $relatedTable => $attribute) {
      if (is_array($attribute)) { // we need to add related query
        if ($rel = self::checkRelationExist($model, $relatedTable)) {
          $query = $this->prepareJoin($rel, $relatedTable, $attribute, $chain, $query);
        } else
          $this->warnings[] = "Model {$model::className()} doesn't have relation named '{$relatedTable}'.";
      } else {
        $rawAttribute = $this->getRawAttribute($attribute);
        if ($model->hasAttribute($rawAttribute)) {
          $this->addWhere($model, $attribute, $chain);
          if (count($chain) == 0)
            $query->addSelect($this->addAttribute($model, $rawAttribute));
        } else
          $this->warnings[] = "Model {$model::className()} doesn't have attribute '{$attribute}'.";
      }
    }
    return $query;
  }


  public static function getPrimaryModel($query, $table = false)
  {
    $model = new $query->modelClass();
    if ($table)
      $model->table = $table;
    return $model;
  }

  public function getRawAttribute(string $attribute)
  {
    $prefix = Conditions::PREFIX;
    return preg_split("/$prefix/", $attribute)[0];
  }

  public function addAttribute(object $model, string $attribute)
  {
    $alias = $model->alias;
    $attribute = $this->getRawAttribute($attribute);
    return "$alias.$attribute";
  }

  public function addWhere($model, $attribute, $chain)
  {
    $conditions = new $this->conditions($model, $attribute, implode('.', $chain));
//    \frontend\components\Helpers::debug(false, $attribute);
    if ($conditions->hasConditions()) {
      $this->where[] = $conditions;
      if (count($chain) > 0) {
        $this->changeJoin($chain);
      }
    }
  }

  public function addSort($sort)
  {
    $sign = preg_match('/!/', $sort) ? SORT_DESC : SORT_ASC;
    $s = preg_split('/\./', str_replace('!', '', $sort));
    $last = count($s) - 1;
    $attribute = $s[$last];
    if ($last > 0)
      unset($s[$last]);
    $chain = implode('.', $s);
    $alias = $this->getAliasByChain($chain);
    $resultSort = ["$alias.{$attribute}" => $sign];
    $this->changeJoin($s);
    return $resultSort;
  }

  public function changeJoin(array $chain)
  {
    $start = [];
    foreach ($chain as $partChain) {
      $start[] = $partChain;
      $sChain = implode('.', $start);
      if (isset($this->with[$sChain])) {
        $this->joinWith[$sChain] = $this->with[$sChain];
//        $this->joinWith[$sChain]['sort'] = $resultSort;
        unset($this->with[$sChain]);
      }
    }
  }

  public function getAliasByChain(string $chain)
  {
    return $this->aliasChains[$chain] ?? $this->aliasChains[$this->table];
  }

  public static function checkRelationExist(Object $model, String $relationName)
  {
    $relationName = "get" . Inflector::id2camel($relationName);
    try {
      return $model->$relationName();
    } catch (\Throwable $e) {
      return false;
    }
  }

  public function getRelationLink(Object $model, String $relationName)
  {
    $relation = "get" . Inflector::id2camel($relationName);
    $link = $model->$relation()->link;
    return [
      array_keys($link)[0],
      array_values($link)[0]
    ];
  }

  private function prepareLimit()
  {
    $this->query->limit($this->limit)->offset($this->offset);
    return $this;
  }

  private function prepareSort()
  {
    foreach ($this->sort as $sort) {
      $this->query->addOrderBy($this->addSort($sort));
    }
    return $this;
  }

  private function prepareWhere()
  {
    foreach ($this->where as $conditions) {
      $this->query->andWhere($conditions->getCondition());
    }
    return $this;
  }

  public function appendRelations(&$joins, $model, &$query, $type, $checkF = false)
  {
    $check = $checkF ? $checkF : function ($key) {
      return count(preg_split('/\./', $key)) == 1;
    };
    $getResult = function ($relationFunction) use (&$query, $model, $type) {
      $result = call_user_func_array($relationFunction[0], [[], &$query, $model, $type]);
      return $result;
    };
    foreach ($joins as $key => &$each) {
      if ($each['done'] || !$check($key))
        continue;
      $each['done'] = true;
      if ($type == self::JOIN)
        $query->with($getResult($each['join']));
      else
        $query->joinWith($getResult($each['join']));
    }
    return $this;
  }

  private function prepareRelations()
  {
    $this->appendRelations($this->with, $this->model, $this->query, self::JOIN);
    $this->appendRelations($this->joinWith, $this->model, $this->query, self::JOIN_WITH);
    if ($this->query->select)
      $this->query->select = array_unique($this->query->select);
    return $this;
  }

  public function execute()
  {
    $count = clone $this->query;
    $count->select($this->addAttribute($this->model, $this->model->PK));
    $data = $this->query->all();
    if (isset($_GET['warnings'])) {
      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
      \frontend\components\Helpers::debug(false, $this->warnings);
      exit;
    }
    $result = [
      'success' => true,
      'data' => $data,
    ];
    if (count($this->warnings) == 0) {
      $result['count'] = $count->limit(-1)->offset(-1)->count();
      return $result;
    } else
      return [
        'success' => false,
        'data' => array_unique($this->warnings)
      ];
  }


}