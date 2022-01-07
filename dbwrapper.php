
<?php
    //Procedural PHP MySQLi wrapper by Kane, read readme.me for more information
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    require("dbconfig.php");
    require("config.php");

    //function to display an error
    function error($error) {
        if (DEVMODE) {
            echo $error;
        } else {
            error_log($error);
        }
    }    

    //function that opens a connection to the Database, writes an error to the error log if unsuccesful
    function DBOpen() {
        $conn = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
        if (mysqli_connect_errno()) {
            error("Failed to connect to MySQL: " . mysqli_connect_error());
            exit("There was an issue connecting to the database, please try again later!");
        }
        return $conn;
    }

    //function that closes a connection to the Database.
    function DBClose($conn) {
        mysqli_close($conn); 
    }

    //return a string that contains one or more characters which specify the types for the corresponding bind variables
    //used in DBQueryDMLPrep() and DBQueryNoDMLPrep() if $types is NULL
    function DBGetTypes($params) {
        $types = "";
        foreach ($params as $param) {
            $type = gettype($param);
            switch ($type) {
                case "integer":
                    $types = $types . "i";
                case "double":
                    $types = $types . "d";
                case "string":
                    $types = $types . "s";
            }
        }
        return $types;
    }

    //transactional functions, useful for sending multiple queries and making sure they all succeed
    function DBStartTransaction($conn) {
        mysqli_begin_transaction($conn);
    }

    function DBCommitTransaction($conn) {
        mysqli_commit($conn);
    }

    function DBRollbackTransaction($conn) {
        mysqli_rollback($conn);
    }

    //sends binary data
    //used in DBQueryDMLPrep() and DBQueryNoDMLPrep() for sending binary data in blocks to the database server
    function DBSendLongData($stmt,$params,$types) {
        foreach (str_split($types) as $i => $type) {
            if ($type == "b") {
                mysqli_stmt_send_long_data($stmt,$i,$params[$i]);
            }
        }
    }

    //ALL DBQuery... functions return in the following format:
    //[$result=bool(if query was succesful),$result=(assoc array containing the requested data for non-DML),$error=string or NULL(contains error if any)]

    //Performs a syncronous, DML, non-prepared query (INSERT, UPDATE or DELETE)
    //Security warning: If the query contains any variable input then DBQueryDMLPrep() should be used
    //returns true on success, or a string with the error if failed
    function DBQueryDML($conn,$query) {
        $success = mysqli_real_query($conn,$query);
        if (!$success) {
            $error = mysqli_error($conn);
        }
        return [$success,[], $error ?? NULL];
    }

    //Performs a MULTI (concatenated by a semicolon) syncronous, DML, non-prepared query (INSERT, UPDATE or DELETE)
    //Security warning: If the query contains any variable input then all strings must be escaped using the mysqli_real_escape_string() function
    //returns true on success, or a string with the error if failed
    function DBQueryDMLMulti($conn,$query) {
        $success = mysqli_multi_query($conn,$query);
        if (!$success) {
            $error = mysqli_error($conn);
        }
        return [$success,[], $error ?? NULL];       
    }

    //Performs a syncronous, non-DML, non-prepared query (SELECT, SHOW, DESCRIBE, EXPLAIN etc.)
    //Security warning: If the query contains any variable input then DBQueryNoDMLPrep() should be used
    //Returns an associative array with the results
    function DBQueryNoDML($conn,$query) {
        $result = mysqli_query($conn,$query);
        if ($result) {
            $result = mysqli_fetch_all($result,MYSQLI_BOTH);
            $success = true;            
        } else {
            $success= false;
            $error = mysqli_error($conn);
        }
        return [$success,$result,$error ?? NULL];
    }    

    //Performs a prepared DML query (INSERT,UPDATE or DELETE)
    function DBQueryDMLPrep($conn,$query,$params,$types) {
        $stmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($stmt,$query);
        if (!isset($types)) {
            $types = DBGetTypes($params);
        }
        mysqli_stmt_bind_param($stmt,$types,...$params);
        if (strpos($types,"b") !== false) {
            DBSendLongData($stmt,$params,$types);
        }
        $result = mysqli_stmt_execute($stmt);
        $affectedrows = mysqli_stmt_affected_rows($stmt);
        if ($result) {
            $success = true;
        } else {
            $success = false;
            $error = mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
        return [$success,[],$error ?? NULL,$affectedrows];
    }

    //Performs a syncronous, non-DML, prepared query (SELECT,SHOW,DESCRIBE,EXPLAIN etc.)
    function DBQueryNoDMLPrep($conn,$query,$params,$types) {
        $stmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($stmt,$query);
        if (!isset($types)) {
            $types = DBGetTypes($params);
        }
        mysqli_stmt_bind_param($stmt,$types,...$params);
        if (strpos($types,"b") !== false) {
            DBSendLongData($stmt,$params,$types);
        }
        $result = mysqli_stmt_execute($stmt);
        $affectedrows = mysqli_stmt_affected_rows($stmt);
        if ($result) {
            $result = mysqli_stmt_get_result($stmt);
            $result = mysqli_fetch_all($result,MYSQLI_BOTH);
            $success = true;
        } else {
            $error = mysqli_stmt_error($stmt);
            $result = [];
            $success = false;
        }
        mysqli_stmt_close($stmt);
        return [$success,$result,$error ?? NULL,$affectedrows];
    }
?>