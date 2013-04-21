<?php
//PLEASE READ THIS, VERY IMPORTANT
//This is a sample passwords.php file
//This file is readable only by members of this project. Remember that other files are readable by everybody
//This file must contain all passwords needed for your project
//Passwords MUST be ONLY in this file

//The following variables have automatically been provided by Emerginov Project Creation mecanism:
//--Start

// SQL

$mysql_db_name='';
$mysql_db_login='';
$mysql_db_password='';
$mysql_db_server='';

// API - When using emerginov for the IVR
$api_login='';
$api_password='';

//--End

// You can add variables below:
include_once("platform.php");


$ivrPlatform = new IvrPlatform(array("platformType"=>"prophecy",
                                     "sox"=>"/Users/mf/sox-14.3.2/sox",
                                     "evolutionToken"=>"vmeetup-outbound"));


define("APPLICATIONROOTDIR", "/var/www/tabale");
define("APPLICATIONROOTURL", "http://example.org/tabale");

define("APPLICATIONMEDIAPATH", APPLICATIONROOTURL . "/media");
define("APPLICATIONMEDIADIR", APPLICATIONROOTDIR . "/media");

// only useful on Evolution
// this is the outbound dialing token, given when registering a new voxeo application

$inboundPhoneNumber = "+9991234567";

// The URL of the speech recognition API
define("ASRAPI", "http://example.org/org/api.php");

?>
