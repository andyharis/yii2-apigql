## API docs.
Some docs information here
## Fetching data
* Fetching data for model/table.
You can fetch any data from your model class by accessing it by name you specified in `relation` provided in config:

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
You can access all data by making GET request to

`gql/clients?select=name,age`
