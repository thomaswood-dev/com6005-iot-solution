<?php
    $host = "localhost";
    $dbname = "thomaswood";
    $username = "thomas.wood";
    $password = "BF6RV8AX";

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        echo "Connected to MySQL database. ";
    }

    if (!empty($_POST['sendvalue'])) {
        $value = $_POST['sendvalue'];

        $table = "esp8266_data";
        
        $sql = "INSERT INTO `$table` (val) VALUES ('$value')";

        if ($conn->query($sql) === TRUE) {
            echo "Value inserted in MySQL database table.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    $conn->close();
?>
