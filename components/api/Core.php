<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 18:19
 */

namespace andyharis\yii2apigql\components\api;

use andyharis\yii2apigql\components\Helpers;
use andyharis\yii2apigql\components\Yii2ApigqlRecord;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

class Core extends Model
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
  public $offset = 0;
  public $sort = [];

  public $where = [];
  public $having = [];
  public $advancedWhere = [];
  public $advancedHaving = [];

  public $customSelectAttributes = [];

  public $with = [];
  public $joinWith = [];

  public $models = [];
  public $attributes = [];

  public $warnings = [];
  public $trace = [];

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
    $this->models[$table] = $model;
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
    return $this->prepareSelect()
      ->prepareLimit()
      ->prepareSort()
      ->prepareWhere()
      ->prepareHaving()
      ->prepareRelations();
  }

  private function prepareSelect()
  {
    $this->query = $this->recursiveSelect($this->select, $this->model, $this->query, []);
    $getModel = function ($chain) {
      return $this->getModelByChain($chain);
    };
    if (!is_array($this->advancedWhere)) {
      $this->addError('where', 'Your where condition should be an array!');
      $this->advancedWhere = [];
    }
    foreach ($this->advancedWhere as $k => $conditions) {
      $params = ['condition' => $conditions, 'model' => $this->model, 'getModel' => $getModel];
      $this->where[] = $ac = (new AdvancedConditions($params))->prepareCondition();
//      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
//      \frontend\components\Helpers::debug(false,$ac->getCondition());
//      exit;
    }
    foreach ($this->advancedHaving as $k => $conditions) {
      $params = [
        'condition' => $conditions,
        'model' => $this->model,
        'getModel' => $getModel,
        'having' => true,
        'customAttributes' => $this->customSelectAttributes
      ];
      $this->having[] = (new AdvancedConditions($params))->prepareCondition();
    }
    return $this;
  }

  private function prepareLimit()
  {
    $this->query->limit($this->limit)->offset($this->offset > 1 ? $this->offset * $this->limit : 0);
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
      if (!$conditions->chain)
        $this->query->andWhere($conditions->getCondition());
    }
    return $this;
  }

  private function prepareHaving()
  {
    foreach ($this->having as $conditions) {
      if (!$conditions->chain)
        $this->query->andHaving($conditions->getCondition());
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
    if (isset($_GET['q'])) {
      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
      Helpers::debug(false, $this->query->createCommand()->rawSql);
      exit;
    }
    $count = clone $this->query;
    $count->select($this->addAttribute($this->model, $this->model->PK));
    $count->orderBy = [$this->addAttribute($this->model, $this->model->PK) => SORT_ASC];
    $count->having = null;
    $data = $this->query->all();
    if (isset($_GET['trace'])) {
      echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
      \frontend\components\Helpers::debug(false, $this->trace);
      exit;
    }
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

  public function getModelAttributes($model, $attributes, $full = false)
  {
    $data = [];
    $least = [];
    foreach ($attributes as $k => $attribute) {
      if (!is_array($attribute)) // && $model->hasProperty($this->getRawAttribute($attribute))
        $data[] = $attribute;
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
    $chain[] = $relatedTable;
    $stringChain = implode('.', $chain);

    $resultAttributes = $this->getModelAttributes($joinModel, $attributes);
    $currentAttributes = $resultAttributes;
    $relationLink = $this->getRelationLink($model, $relatedTable);
    $joinAttributes = array_merge($currentAttributes, [
      $relationLink[0],
      $joinModel->PK
    ]);
    $this->with[$stringChain] = [
      'join' => [
        function ($params, $query, $primaryModel, $join) use ($joinModel, $relatedTable, $stringChain, $joinAttributes, $relationLink) {
          $query->addSelect($this->addAttribute($primaryModel, $relationLink[1]));
          return [
            $relatedTable => function ($q) use ($joinModel, $query, $stringChain, $joinAttributes, $params, $relationLink, $primaryModel, $join) {
              foreach ($this->where as $k => $conditions) {
//                echo "Debug: <b>" . __FILE__ . "</b> on method <b>" . __METHOD__ . "</b> on line <b>" . __LINE__ . "</b>";
//                \frontend\components\Helpers::debug(false, [
//                  $stringChain,
//                  $this->where
//                ]);
//                exit;
                if ($conditions->chain == $stringChain) {
                  $q->andWhere($conditions->getCondition());
//                  $query->andWhere($conditions->getCondition());
                  unset($this->where[$k]);
                }
              }
              if (isset($this->joinWith[$stringChain]) && isset($this->joinWith[$stringChain]['sort']))
                $q->addOrderBy($this->joinWith[$stringChain]['sort']);
              $q->alias($joinModel->alias)
                ->groupBy($this->addAttribute($joinModel, $joinModel->PK));
              foreach ($joinAttributes as $joinAttribute) {
                $this->addSelect($joinModel, $joinAttribute, explode('.', $stringChain), $q);
              }
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
//              if ($this->checkIsDone($stringChain, $join)) {
              $this->appendRelations($this->joinWith, $joinModel, $q, self::JOIN_WITH, $cb);
//              }
              $this->appendRelations($this->with, $joinModel, $q, self::JOIN, $cb);
//              \frontend\components\Helpers::debug(false, [
//                'model' => $q->modelClass,
//                'primaryModel'=>$primaryModel::className(),
//                'withs' => array_keys($q->with ?? []),
//                'joinWiths' => array_keys($q->joinWith ?? [])
//              ]);
              return $q;
            }];
        }
      ],
      'attributes' => $joinAttributes,
      'done' => false
    ];
    $this->models[implode('.', $chain)] = $joinModel;
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
        $this->addSelect($model, $attribute, $chain, count($chain) == 0 ? $query : false);
      }
    }
    return $query;
  }

  public function addSelect($model, $attribute, $chain, $query = false)
  {
    $selectObject = new \Yii::$app->gql->select($model, $attribute);
    $select = $this->getRawAttribute($attribute);
    if ($selectObject->hasCustomAttribute) {
      $select = $selectObject->customAttribute;
      $this->customSelectAttributes[$selectObject->rawAttribute] = $select;
    } else if ($model->hasAttribute($select)) {
      $this->addWhere($model, $attribute, $chain);
      $select = $this->addAttribute($model, $select);
    } else {
      $this->warnings[] = "Model {$model::className()} doesn't have attribute '{$attribute}' in chain '{" . implode(".", $chain) . "}'.";
      return false;
    }
    if ($query) {
      $query->addSelect($select);
    }
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

  public function addAttribute($model, string $attribute)
  {
    $alias = $model->alias;
    $attribute = $this->getRawAttribute($attribute);
    return "$alias.$attribute";
  }

  public function addWhere($model, $attribute, $chain)
  {
    $conditions = new $this->conditions($model, $attribute, implode('.', $chain));
    if ($conditions->hasConditions()) {
      $this->trace[] = [
        'message' => 'Adding new condition',
        'condition' => $conditions->getCondition()
      ];
//      $model->andWhere($conditions->getCondition());
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
    $model = $this->getModelByChain($chain);
    $alias = $model->alias;
    if ($model->hasAttribute($attribute)) {
      $resultSort = ["$alias.{$attribute}" => $sign];
      $this->changeJoin($s, $resultSort);
      return $resultSort;
    }
    return [$attribute => $sign];
  }

  public function changeJoin(array $chain, $sort = false)
  {
    $start = [];
    foreach ($chain as $partChain) {
      $start[] = $partChain;
      $sChain = implode('.', $start);
      if (isset($this->with[$sChain])) {
        $this->joinWith[$sChain] = $this->with[$sChain];
        if ($sort)
          $this->joinWith[$sChain]['sort'] = $sort;
//        $this->trace[] = [
//          'message' => 'Changing with to joinWith',
//          'sChain' => $sChain
//        ];
        unset($this->with[$sChain]);
      }
    }
  }

  public function getAliasByChain(string $chain, $type = 'alias')
  {
    $model = $this->getModelByChain($chain);
    return $model->$type;
  }

  public function getModelByChain($chain)
  {
    return $this->models[$chain] ?? $this->models[$this->table];
  }

  public static function checkRelationExist($model, String $relationName)
  {
    $relationName = "get" . Inflector::id2camel($relationName);
    try {
      return $model->$relationName();
    } catch (\Throwable $e) {
      return false;
    }
  }

  public function getRelationLink($model, String $relationName)
  {
    $relation = "get" . Inflector::id2camel($relationName);
    $link = $model->$relation()->link;
    return [
      array_keys($link)[0],
      array_values($link)[0]
    ];
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
      if (!$check($key))
        continue;
//      $each['done'] = true;
      $result = $getResult($each['join']);
      if ($type == self::JOIN)
        $query->with($result);
      else {
        $query->joinWith($result);
      }
      $this->trace[] = [
        'model' => $model->className(),
        'key' => $key,
        'withs' => array_keys($query->with ?? []),
        'joinWiths' => array_keys($query->joinWith ?? []),
      ];
    }
    return $this;
  }


}