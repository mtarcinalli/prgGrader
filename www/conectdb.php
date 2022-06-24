<?php
try {
	$host = "db";
	$user = "pgrader";
	$password = "sn2144a";
	$dsn = "pgsql:host=db;dbname=pgrader;";
	$db = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
	die($e->getMessage());
}