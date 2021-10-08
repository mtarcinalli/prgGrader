<?php
try {
	$user = "pgrader";
	$password = "";
	$dsn = "pgsql:dbname=pgrader;";
	// make a database connection
	$db = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

} catch (PDOException $e) {
	die($e->getMessage());
}

