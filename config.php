<?php

try
{
	$user = "vpn";
	$password = "password";
	$host = "mysql.nvarghese.com";
	$db = "users";


	$options = [
        	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
        	PDO::ATTR_EMULATE_PREPARES => false
	];

    	$char = "utf8";
	
	$dbh = new PDO("mysql:host=$host;dbname=$db;charset=$char", $user, $password, $options);
}
 catch ( Exception $ex )
{
	 die("ERROR: Couldn't connect.{$ex->getMessage()}");
}
