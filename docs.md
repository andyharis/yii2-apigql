## API docs.
Some docs information here
## Fetching data
Fetching data for model/table.
You can fetch any data from your model class by accessing it by name you specified in `relation` provided in config see `Creating models.php`:
```php
  ->addModel('clients', \frontend\models\Clients::className())
   ```
Where:
* `clients` - indicates name for your model
* `\frontend\models\Clients::className()` - indicates what model should use to fetch and update data

So the pattern is `http://example.com/gql/$name-of-your-model`.

For example you have model `\frontend\models\Clients` and you named it `clients`, to fetch some data for you simply need to request: 

GET /gql/clients `http://example.com/gql/clients`
```json
{"success":true,"totalCount":"1992","data":[{},{},{},{}]}
```
 
Example class Clients
```php
class Clients extends Yii2ApigqlRecord {
    // attributes
    public function attributeLabels()
      {
        return [
          'id' => 'ID',
          'name' => 'Name',
          'age' => 'Age',
          'phoneNumber'=>'Phone number'
        ];
      }
}
```
