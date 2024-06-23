<?php
ini_set( "error_reporting" , E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED );
ini_set( "error_log" , "log/php-error.log" );
ini_set( "display_errors", true );

date_default_timezone_set( "Asia/Jakarta" );  // http://www.php.net/manual/en/timezones.php

define( "ENVIRONMENT", "PRD2" ); // DEV||PRD

define( "MANDT", "600" );
define( "DB_DSN_PDO", "oci:dbname=(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = 10.1.0.18)(PORT = 1521))(CONNECT_DATA = (SID = MAKESS20)))" ); //Local
define( "DB_DSN", "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = 10.1.0.18)(PORT = 1521))(CONNECT_DATA = (SID = MAKESS20)))" ); //Local
define( "DB_USERNAME", "scott" );
define( "DB_PASSWORD", "tiger" );

define( "CLASS_PATH", "classes" );
define( "TEMPLATE_PATH", "templates" );

define( "APP_VERION", "2.0.0" );

foreach (glob("classes/*.php") as $filename){
  require( $filename );
}

?>