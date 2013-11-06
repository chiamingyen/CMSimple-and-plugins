<?php
//include(dirname(__FILE__)."./../../adodb5/adodb.inc.php");

// 本段落適用於Access
/*
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
*/
//本段落適用於SQLite 2.0
/*
$dbc=NewADOConnection('sqlite');
$dbc->SetFetchMode(2);
$connstr=dirname(__FILE__)."/lewis.db";
$dbc->charPage=CP_UTF8;

$dbc->Connect($connstr);

    if ($dbc->Connect($connstr)==NULL)
    {
    exit("Sorry! Can not connect to database!");
    }
*/

// 改為 PDO
$database_name = "lewis.db";
$connstr="sqlite:".dirname(__FILE__)."/db/".$database_name;
$dbc = new PDO($connstr) or die("Couldn't connect to server.<br>\n");
/*
 * // this is for sqlite 3.0 through adodb and pdo
$dbc = NewADOConnection('pdo');
$dbc->SetFetchMode(2);
$connstr="sqlite:".dirname(__FILE__)."/".$database_name;
// for UTF-8 charset
$dbc->charPage=CP_UTF8;
$dbc->Connect($connstr);

    if ($dbc->Connect($connstr)==NULL)
    {
    exit("Sorry! Can not connect to database!");
    }
 */
