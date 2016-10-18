# Bookworm
A small ORM library written in PHP

---
A small use case:

```php

// register your database to the pool and name it
\Bookworm\Pool::createConnection('default', [
    'port' => 3306,
    'driver' => 'mysql',
    'host' => 'localhost',
    'username' => 'your-username',
    'password' => 'your-secret-password',
    'database' => 'your-database'
]);


use \Bookworm\Model;

// the name of the class you`re using correlates to a corresponding table
class User extends Model {
    // this is all that is required for a normal model
}

// if however, you want to name the table yourself, you can do so like this
class MyLovelyPeople extends Model {
    protected $table = "my_users";
}

// to use it, simply get a model by it`s primary ID
$user = MyLovelyPeople::find(1);

// or alternatively
$fred = User::where("username", "=", "fred")->get();

// if you have a primary field which isn`t named id, you can define it as such

class Preferences extends Model {
    protected $primary = 'preference_id';
}

// and now you can use it like previously 
$preferences = Preferences::find(1);

// if you want to retrieve a collection of types, you can use

// retrieves all users ( note that it has a limit of a 100, to override, simply
// add your own number of records to retrieve )
$users = User::all();

// to selectively retrieve a collection of items
$authorized_users = User::where('authorization', '>=', 3)->all();

```



