## API docs.
Some docs information here

- [Fetching data](#fetching-data)
    - [Select](#select) 
    - [Sort](#sort) 
    - [Where](#where) 
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
```json
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