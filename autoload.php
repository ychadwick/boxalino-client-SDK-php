<?php
$libPath = __DIR__.'/lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once("lib/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
BxClient::LOAD_CLASSES($libPath);