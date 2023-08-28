<?php

ini_set("display_errors", "off");
error_reporting(0);

session_start();
$MAX_UPLOAD_SIZE = 33554432; // b

// importing mysql class
require_once "db.php";

// database settings
define(DBHOST, 'localhost');
define(DBUSER, 'luajit');
define(DBPASS, 'hdJLJjxgafpmTxGY');
define(DBNAME, 'luajit');

// the _get and _post variables
define(IP_ADDRESS, ($ip = $_SERVER["HTTP_CF_CONNECTING_IP"]) ? $ip : $_SERVER["REMOTE_ADDR"]);
define(DOMAIN_NAME, $_SERVER["HTTP_HOST"]);
define(REQUEST_URI, $_SERVER["REQUEST_URI"]);
define(USERNAME, isset($_POST["username"]) ? $_POST["username"] : $_SESSION["username"]);
define(PASSWORD, isset($_POST["password"]) ? $_POST["password"] : $_SESSION["password"]);

// connecting to the database
$sql = new db(DBHOST, DBUSER, DBPASS, DBNAME);

// checking for a vpn
// require "vpn.php";

// checking for a ban
/*if ($sql->query("select * from local_bans where ip = ?", IP_ADDRESS)->numRows() !== 0)
    exit("access denied: you are banned");*/

// collecting statistics
if (IP_ADDRESS != "185.82.247.60")
$sql->query("insert into local_logs (ip, time, domain, uri) values (?, ?, ?, ?)",
    IP_ADDRESS,
    time(),
    strlen(DOMAIN_NAME) > 16 ? (substr(DOMAIN_NAME, 0, 15) . "~") : DOMAIN_NAME,
    strlen(REQUEST_URI) > 32 ? (substr(REQUEST_URI, 0, 31) . "~") : REQUEST_URI
);

?>

<title>LuaJIT Tools</title>
<link rel="stylesheet" type="text/css" href="/css/style.css">

<p>
    <a href="/">Scanner</a> | <a href="/joiner">Joiner</a> | <a href="/conv">Converter</a> | <a href="/prot">Protector</a>
</p>
