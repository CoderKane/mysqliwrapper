# mysqliwrapper
A simple wrapper for PHP's mysqli, including support for prepared statements

**How to use:**

Include the following:
```
require("dbwrapper.php");
```

**Examples:**
Open a connection first, before attempting to run any querys
```
   $conn = DBOpen();
```

Performing a non-DML (SELECT, SHOW, DESCRIBE, EXPLAIN etc.) query:
```
  $result = DBQueryNoDML($conn,"SELECT id FROM users WHERE username = 'Kane'");
```

Perform a DML (INSERT, UPDATE, DELETE etc.) query:
```
  $result = DBQueryDML($conn,"UPDATE users SET emailVerified = 1 WHERE username = 'Kane'");
```

Perform a non-DML prepared query
```
  $result = DBQueryNoDMLPrep($conn,"SELECT id FROM users WHERE username = ?",["Kane"],NULL);
```

or 

```
  $result = DBQueryNoDMLPrep($conn,"SELECT id FROM users WHERE username = ?",["Kane"],"s");
```

Perform a DML prepared query
```
  $result = DBQueryDMLPrep($conn,"UPDATE users SET emailVerified = 1 WHERE username = ?",["Kane"],NULL);
```

Close connection when all querys are ran
```
DBClose($conn);
```


$result is returned in the following array format: 

[

  $success => bool (whether the query was succesful),
  
  $result => assoc array (containing the requested data for non-DML querys)
  
  $error => string or NULL (contains error if any)
  
  $affectedrows => number (contains number of affected rows, only for prepared querys)
  
]
