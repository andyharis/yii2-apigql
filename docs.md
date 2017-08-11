## API docs.
Some docs information here

- [Fetching data](#fetching-data)
    - [Select](#select) 
    - [Sort](#sort) 
    - [Where](#where) 
- [Updating data](#updating-data)
    - [Add](#add)
    - [Edit](#edit)
    - [Delete](#delete)
## Fetching data
To fetch data you may specify list of GET params:

#### Select
`select` - which attributes you want to select from your models.
Select should follow JSON standard to work properly.
For example we want to fetch data from table `users` which has relation `profile`.

Here is the json we want to fetch:
```json
{
  "username":"",
  "email":"",
  "profile":{
    "firstName":"",  
    "lastName":"",  
    "age":""  
  }
}
```
our request will be
```text
/clients?select={"username":"","email":"","profile":{"firstName":"","lastName":"","age":""}}
```

First argument in JSON is the model attribute you want to fetch and the second is query condition: 
```text
{
  "attribute":"where condition",
  "username":":=Andyhar", // username = "Andyhar"
  "email":":~andyhar", // email LIKE "%andyhar%"
  "profile":{
    "age":":>30"  // age > 30
  }
}
```
Example usage:
```text
// SELECT username, email
/clients?select={"username":"","email":""}

// SELECT username, profile.firstName, profile.lastName
/clients?select={"username":"","profile":{"firstName":"","lastName":""}}

// SELECT username ... WHERE  username = 'Andyhar'
/clients?select={"username":":=Andyhar","email":""}

// SELECT username, email, profile.age ... WHERE  profile.age > 30
/clients?select={"username":"","email":"","profile":{"age":":>30"}}
```
See [Where](#where) section for more info about where conditions.

When you use attributes in `select` parameter, you should follow one simple thing - access data in current environment.
You can access any nested data follow this structure. 
For example if you access main model `clients` and this model has attribute `username` - 
you simple write `?select={"username":""}`.
If you have relation `profile` in your main model `clients` you should access it in `profile` environment - 
`?select={"profile":{"firstName":""}}`.
Always follow nested JSON structure to fetch any attribute from your models and relations.

#### Sort

`sort` - provides sort on attributes you selected. You can pass any attribute from main table or any nested table.

Use `?sort=attribute` to sort ASC or `?sort=!attribute` to DESC.
You can specify many params like this `?sort=username,!profile.age` - `ORDER BY username ASC profile.age DESC`. 
Note that if you want to sort by nested attributes, you should set full chain to attribute.

Example usage:
```text
// ORDER BY clients.username ASC
/clients?select={"username":"","email":""}&sort=username

// ORDER BY clients.username ASC profile.age DESC
/clients?select={"username":"","email":"","profile":{"age":"""}}&sort=username,!profile.age
 
// ORDER BY invoices.username ASC
/clients?select={"username":"","profile":{"invoices":{"date_created":""}}}&sort=profile.invoices.date_created 
```

#### Where
```
THIS SECTION IS IN DEVELOPMENT MODE
bugs are coming
```
`where` - provides an advanced search conditions. `where` params uses JSON structure but in array style.
Where conditions has their pattern to understand which condition to use:
- `:=` Equal to
- `:>` Greater than
- `:>=` Greater than or equal
- `:<` Lower than
- `:<=` Lower than or equal
- `:~` Like

Example usage:
```text
// WHERE (username LIKE `%andyhar%`) OR (profile.firstName = 'andyhar')
/clients?where=[["or","username:~andyhar","profile.firstName:=andyhar"]]

// WHERE status=1 AND (profile.firstName LIKE `%andyhar%` OR profile.lastNmae LIKE '%andyhar%')
/clients?where=[["and","status:=1",["or","profile.firstName:~andyhar","profile.lastName:~andyhar"]]]
````

## Updating data

To fetch data you must send POST request `application/json` type with following json structure:
```json
{
  "data": {
    "username": "Bandyhar"
  }
}
```

You can add, edit, delete any data in main and nested tables. 
#### Add


Example usage:
###### Trying to add new member with HAS_ONE relation
POST /members
```text
{
  "data": {
    "username": "Andyhar",
    "email": "andyharrisonnd@gmail.com",
    "profile": {
      "firstName": "Andy",
      "lastName": "Harrison"
    }
  }
}
``` 
This will insert main table `members` and then `profile` with newly created member id.

###### Trying to add new product with HAS_MANY relations
When you work with `HAS_MANY` relation data, you should use `add`, `edit`,`delete` param to let API know what to do with current row.

`add` param requires an array of table attributes for example:

/products
```text
{
  "data": {
    "name":"Product",
    "images":{
      "add":[ 
        {"fileName":"img1"}, // attribute `filename` will be inserted in table `images`
        {"fileName":"img2"},
        {"fileName":"img3"},
      ]
    }
  }
}
```
This will create a new `product` and insert all `images` relation rows
#### Edit

###### Trying to edit member with HAS_ONE relations
POST /members/`id`
```text
Send same json structure as for Add
{
  "data": {
    "username": "Andyhar2",
    "email": "andyharrisonnd@gmail.com",
    "profile": {
      "firstName": "Andy2",
      "lastName": "Harrison2"
    }
  }
}
```
This will edit all rows which was sent to this request.

###### Trying to edit new product with HAS_MANY relations
You should use `id` param in request to let API know which row to edit.
When you work with `HAS_MANY` relation data, you should use `add`, `edit`,`delete` param to let API know what to do with current row.
POST /products/`id`
```text
{
  "data": {
    "name": "Product",
    "images": {
      "edit": { //using edit params
        "id1": { //using id of row we want to edit
          // attribute `fileName` will be updated in table `images` in row with id `id1`
          "fileName": "img-super-important" 
        },
        "id2": {
          "fileName": "another important name"
        },
      }
    }
  }
}
```

#### Delete

When you delete nested data you should specify an array of `ids` which rows should be deleted in `table` you provided.

###### You can delete any nested data by providing full delete path
We need to specify which row we want to update using `id`

Example:
POST /products/`id`
```text
{
  "data": {
    "name": "Product",
    "images": {
      // use `delete` params to let API know that you want to delete some rows
      // provide an array of `ids` to delete all rows from table `images`
      "delete": [
        'id1',
        'id3'
      ]
    }
  }
}
```


#### Complex example 
You can `add`, `edit`, `delete` rows in one single query!
POST /products/121
```text
{
  "data": {
    "name": "New name",
    "description":"This is important",
    "images": {
      "add":[
        {"fileName":"new image1"},
        {"fileName":"new image2"},
      ],
      "edit":{
        "342":{"fileName":"i missed spelling"}
      },
      "delete": [
        'id1',
        'id3'
      ]
    },
    "tags":{
      "add":[
        {"name":"tag-name"}
      ]
    }
  }
}
```