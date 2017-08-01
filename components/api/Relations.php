<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 24.07.2017
 * Time: 18:25
 */

namespace andyharis\yii2apigql\components\api;


use yii\base\Object;

class Relations extends Object
{
  public $models = [];
  public $relations = [];
  public $aliases = [];

  /**
   * This method returns prepared object with relations
   *
   * @params string $name Name you want to see in api
   * @param string $className Model class namespace
   * @param array $params Relations params
   * @return Relations
   */
  public function addModel(string $name, string $className, array $params = [])
  {
    $this->models[$name] = $className;
//    $this->addAlias($name);
    return $this;
  }

  /**
   * @param string $parent
   * @param string $child
   * @param string $type
   * @param string $childKey
   * @param string $parentKey
   * @return $this
   */
  public function addRelation(string $parent, string $child, string $type = '', $childKey = '', $parentKey = '')
  {
    $this->relations[$parent][$child] = [
      $type, [$childKey => $parentKey]
    ];
    return $this;
  }

  public function addAlias(string $class)
  {
    $this->aliases[$class] = "gql" . substr(md5(time() * rand(1, 1111)), 0, rand(5, 10));
    return $this;
  }


  public function getModel(string $class)
  {
    return new $this->models[$class]();
  }

  public function getClass(String $class)
  {
    return $this->models[$class];
  }

}