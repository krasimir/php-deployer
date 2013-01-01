# Adapters

A wrapper around MySQL functions with friendly API. The module creates the database, tables and columns automatically, based on definition.

- - -

## MySQL

### Initializing

    $mysql = new MySQLAdapter((object) array(
        "host" => "localhost",
        "user" => "root",
        "pass" => "",
        "dbname" => "fabrico_mysqladapter_test"
    ));

### Defining tables/contexts

    $mysql->defineContext("users", array(
        "firstName" => "VARCHAR(250)",
        "lastName" => "VARCHAR(250)",
        "email" => "VARCHAR(100)",
        "password" => "INT",
        "createdAt" => "DATETIME",
        "bio" => "LONGTEXT"
    ));

### Adding record

    $record = (object) array(
        "firstName" => "Krasimir",
        "lastName" => "Tsonev",
        "email" => "info@krasimirtsonev.com",
        "password" => rand(0, 1000000)
    );
    $mysql->users->save($record);

### Updating record

    $record->lastName = "My-Custom-Last-Name";
    $mysql->users->save($record);

### Deleting record
    
    $mysql->users->trash($record);

### Getting records

    $allUsers = $mysql->users->get();

### Getting user with position=2

    $user = $mysql->users->where("position=2")->get();

### Getting user order by password value

    $user = $mysql->users->order("password")->get();

### Getting user order by password value (ascending)

    $user = $mysql->users->order("password")->asc()->get();

### Getting user order by password value (descending)

    $user = $mysql->users->order("password")->desc()->get();

### Executing custom mysql query
    
    $res = $mysql->action("SELECT * FROM users WHERE id > 30");

### To view all the queries executed

    var_dump($mysql->queries);

### Freezing the adapter
The adapter execute some mysql queries in the background to find out what is created and what is not in your database. By setting the *freeze* property to true, you will stop those queries. It will increase the performance of you application, but you will not get the automatically creation of database, tables and columns.

    $mysql->freeze = true;
