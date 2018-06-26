<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 28.07.2017
 * Time: 20:16
 */

namespace andyharis\yii2apigql\components\api;


use andyharis\yii2apigql\components\Helpers;
use andyharis\yii2apigql\components\Yii2ApigqlRecord;
use yii\base\BaseObject;

class Update extends BaseObject
{
  const UPDATE_LINK = 0;
  const UPDATE_RELATED = 1;
  public $table;
  /**
   * @var Yii2ApigqlRecord
   */
  public $model;
  public $data = [];
  public $errors = [];
  public $linksUpdates = [];
  public $relatedUpdates = [];
  public $deleteUpdates = [];
  public $deletedRecords = [];
  public $trace = [];
  public $debug = false;
  private $relations;

  public function __construct($table, $data, $id = null)
  {
    $relations = \Yii::$app->gql->relations;
    $className = $relations->getClass($table);
    $model = $id ? $className::findOne($id) : new $className();
    $model->table = $table;
    $this->table = $table;
    $this->model = $model;
    $this->data = $data;
    parent::__construct([]);
  }


  public function validateUpdate()
  {
    $this->validateRecursive($this->model, $this->data, [$this->table]);
    return $this;
  }

  /**
   * @param $model Yii2ApigqlRecord
   * @param $data
   * @param $chain
   * @param int $type
   */
  private function validateRecursive($model, $data, $chain, $type = self::UPDATE_LINK)
  {
    if (!$this->checkMultipleUpdate($model, $data, $chain)) {
      $this->trace[] = [
        'messsage' => 'Populating model with data',
        'data' => $data,
        'model' => $model->table
      ];
      foreach ($data as $attribute => $value) {
        if (!is_array($value))
          $this->prepareEntity($model, $attribute, $value, $chain);
        else {
          $nextTable = $attribute;
          $nextChain = array_merge($chain, [$nextTable]);
          if ($relatedModel = Core::checkRelationExist($model, $nextTable)) {
            $hasOne = !$relatedModel->multiple;
            $relatedModel = Core::getPrimaryModel($relatedModel, $nextTable);
            if ($hasOne)
              $this->addLink($relatedModel, $nextChain, self::UPDATE_LINK);
            $this->validateRecursive($relatedModel, $value, $nextChain);
          } else {
            $this->addWarning("Table {table} doesn't have relation {relation}.", ['table' => $model->table, 'relation' => $nextTable]);
          }
        }
      }
      $this->validateEntity($model, $chain);
    }
  }

  public function addLink($model, $chain, $type)
  {
    $link = $type == self::UPDATE_LINK ? 'linksUpdates' : 'relatedUpdates';
    $this->$link[implode('.', $chain)] = $this->debug ? $model->attributes : $model;
  }

  /**
   * @param $model Yii2ApigqlRecord
   * @param $data
   * @param $chain
   * @return bool
   */
  private function checkMultipleUpdate($model, $data, $chain)
  {
    $return = false;
    if (isset($data[Yii2ApigqlRecord::ACTION_ADD])) {
      $this->prepareMultiple($data, Yii2ApigqlRecord::ACTION_ADD, $model, $chain);
      $return = true;
    }
    if (isset($data[Yii2ApigqlRecord::ACTION_EDIT])) {
      $this->prepareMultiple($data, Yii2ApigqlRecord::ACTION_EDIT, $model, $chain);
      $return = true;
    }
    if (isset($data[Yii2ApigqlRecord::ACTION_DELETE])) {
      $deleteModel = $model::findAll($data[Yii2ApigqlRecord::ACTION_DELETE]);
      $this->deleteUpdates = array_merge($this->deleteUpdates, $deleteModel);
      $return = true;
    }
    return $return;
  }

  /**
   * @param $data
   * @param $action
   * @param $model Yii2ApigqlRecord
   * @param $chain
   */
  private function prepareMultiple($data, $action, $model, $chain)
  {
    $i = 0;
    foreach ($data[$action] as $index => $values) {
      if ($action == Yii2ApigqlRecord::ACTION_EDIT) {
        $nextModel = $model::find()->where([$model->PK => "$index"])->one();
        $index = $i;
      } else
        $nextModel = clone $model;
      $nextChain = array_merge($chain, [$index]);
      $this->addLink($nextModel, $nextChain, self::UPDATE_RELATED);
      $this->validateRecursive($nextModel, $values, $nextChain, self::UPDATE_RELATED);
      $i++;
    }
  }

  /**
   * @param $model Yii2ApigqlRecord
   */
  private function validateEntity($model, $chain)
  {
    if (!$model->validate()) {
      $this->addModelErrors($model->errors, $chain);
    }
  }

  private function addModelErrors($errors, $chain)
  {
    foreach ($errors as $attribute => $error) {
      $nextChain = array_merge($chain, [$attribute]);
      $this->trace[] = [
        "message" => "Validate error:",
        "chain" => $nextChain
      ];
      $this->addError($nextChain, $error);
    }
  }

  /**
   * @param $model Yii2ApigqlRecord
   * @param $attribute
   * @param $value
   * @param $chain
   */
  private function prepareEntity(&$model, $attribute, $value, $chain = [])
  {
    $message = "Table {table} doesn't have attribute {attribute}. Chain {chain}";
    if ($model->hasAttribute($attribute))
      $model->$attribute = $value;
    else
      $this->addWarning($message, ['table' => $model->table, 'attribute' => $attribute, 'chain' => implode('.', $chain)]);
  }

  private function addWarning($message, $params = [])
  {
    $this->addError('messages.[]', $message, $params);
  }

  private function addError($chain, $message, $params = [])
  {
    $message = \Yii::t('app', $message, $params);
    $this->errors = Helpers::oSet($this->errors, $chain, $message);
  }

  public function hasErrors()
  {
    return count($this->errors) > 0;
  }

  private function insertTransaction($entity)
  {
    $transaction = $entity::getDb()->beginTransaction();
    try {
      $result = [];
//      if (!$insert) {
//        $attributes = $this->actionSearch($this->table, true);
//        $select = $this->fetch(Json::encode($attributes));
//        $select[0] = "iID:{$entity->iID}";
//        $query = $this->prepare($this->table);
//        $query = $query::find()->asArray();
//        $oldData = $this->find($select, $this->table, $query)->one();
//        $this->oldData[$this->table] = $oldData;
//      }
      if ($entity->save(false)) {
        $result = $entity->attributes;
        $result = array_merge($result, $this->insertLinks($entity, [$this->table]));
        $result = array_merge($result, $this->insertRelations($entity, [$this->table]));
        $this->deleteRelated();
      } else {
        $this->addModelErrors($entity->errors, [$this->table]);
      }
//      $transaction->rollBack();
      $transaction->commit();
//      $result = array_merge([$entity::tableName() => array_merge($entity->attributes, $data, $this->data)]);
//      MongoDB::trigger($this->table, ['data' => $_POST['data'], 'result' => $result, 'insert' => $insert]);
//      MongoDB::add([
//        'entity' => $this->table,
//        'data' => $_POST['data'],
//        'result' => $result,
//        'iID' => $entity->iID,
//        'isRevision' => 0,
//        'action' => $insert ? 'insert' : 'update',
//        'oldAttributes' => $this->oldData
//      ], Snapshots::className());
//      return $result;
      return $result;
    } catch (\Throwable $e) {
      $transaction->rollBack();
      throw $e;
    }
  }


  private function deleteRelated()
  {
    foreach ($this->deleteUpdates as $deleteUpdate) {
//      $this->deletedRecords[$this->table][] = $deleteUpdate->{$deleteUpdate->PK};
      $deleteUpdate->delete();
    }
  }

  /**
   * @param $entity Yii2ApigqlRecord
   * @param $chain
   * @return array
   */
  private function insertLinks($entity, $chain)
  {
    $sChain = implode('.', $chain);
    $data = [];
    foreach ($this->linksUpdates as $keyChain => $relatedModel) {
      $relatedChain = preg_split('/\./', $keyChain);
      $linkTable = $relatedChain[count($relatedChain) - 1];
      if (preg_match("/$sChain/", $keyChain) && count($chain) + 1 == count($relatedChain)) {
        if ($relatedModel->isNewRecord)
          $entity->link($linkTable, $relatedModel);
        else
          $relatedModel->save(false);
        $this->addModelErrors($relatedModel->errors, $relatedChain);
        $data[$linkTable] = $relatedModel->attributes;
      }
    }
    return $data;
  }

  /**
   * @param $entity Yii2ApigqlRecord
   * @param $chain
   */
  private function insertRelations($entity, $chain)
  {
    $data = [];
    $sChain = implode('.', $chain);
    foreach ($this->relatedUpdates as $keyChain => $relatedModel) {
      $relatedChain = preg_split('/\./', $keyChain);
      $linkTable = $relatedChain[count($relatedChain) - 2];
      $index = $relatedChain[count($relatedChain) - 1];
      if (preg_match("/$sChain/", $keyChain) && count($chain) + 2 == count($relatedChain)) {
        if ($relatedModel->isNewRecord)
          $entity->link($linkTable, $relatedModel);
        else
          $relatedModel->save(false);
        $this->addModelErrors($relatedModel->errors, $relatedChain);
        $data[$linkTable][$index] = array_merge($relatedModel->attributes, $this->insertLinks($relatedModel, $relatedChain));
        $data[$linkTable][$index] = array_merge($data[$linkTable][$index], $this->insertRelations($relatedModel, $relatedChain));
      }
    }
    return $data;
  }

  public function execute()
  {
    if (!$this->hasErrors())
      return $this->insertTransaction($this->model);
    return false;
  }
}