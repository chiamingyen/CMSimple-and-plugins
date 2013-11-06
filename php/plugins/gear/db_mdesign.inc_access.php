<?php
include("./../../adodb5/adodb.inc.php");

// 本段落適用於Access
$mdbname="lewis";
$dir=dirname(__FILE__);
ADOLoadCode("ado_access");
$dbc = &ADONewConnection("ado_access");
$connstr="DRIVER={Microsoft Access Driver (*.mdb)};";
$connstr .= "DBQ=".$dir."\\".$mdbname.".mdb;uid=sa;pwd=;"; 
$dbc->charPage=CP_UTF8;
$dbc->Connect($connstr);

    if ($dbc->Connect($connstr)==NULL)
    {
    exit("sorry, cannot connect to db!");
    }
    
/* 本段落適用於SQLite
$dbc=NewADOConnection('sqlite');
$dbc->SetFetchMode(2);
$connstr=dirname(__FILE__)."/to_be_protected/weblog.db";
$dbc->charPage=CP_UTF8;

$dbc->Connect($connstr);

    if ($dbc->Connect($connstr)==NULL)
    {
    exit("Sorry! Can not connect to database!");
    }
*/

?>
