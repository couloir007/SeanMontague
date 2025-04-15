<?php
function connectDBLook($host, $username, $db, $password, $port) {
    $dsn = "pgsql:host=$host;port=$port;user=$username;dbname=$db;password=$password";
    return new PDO($dsn);
}
