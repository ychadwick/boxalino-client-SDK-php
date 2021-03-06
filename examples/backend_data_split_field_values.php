<?php

/**
* In this example, we take a very simple CSV file with product data, generate the specifications of a field splitting it's values (field provided as coma separated values in one csv cell), load them, publish them and push the data to Boxalino Data Intelligence
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxData;
BxClient::LOAD_CLASSES($libPath);

//required parameters you should set for this example to work
$account = ""; // your account name
$password = ""; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)
$languages = array('en'); //declare the list of available languages
$isDev = false; //are the data to be pushed dev or prod data?
$isDelta = false; //are the data to be pushed full data (reset index) or delta (add/modify index)?

//Create the Boxalino Data SDK instance
$bxData = new BxData(new BxClient($account, $password, $domain), $languages, $isDev, $isDelta);

try {
	
	$file = '../sample_data/products.csv'; //a csv file with header row
	$itemIdColumn = 'id'; //the column header row name of the csv with the unique id of each item
	
	//add a csv file as main product file
	$sourceKey = $bxData->addMainCSVItemFile($file, $itemIdColumn);
	
	//this part is only necessary to do when you push your data in full, as no specifications changes should not be published without a full data sync following next
	//even when you publish your data in full, you don't need to repush your data specifications if you know they didn't change, however, it is totally fine (and suggested) to push them everytime if you are not sure if something changed or not
	if(!$isDelta) {
	
		//declare the fields
		$bxData->addSourceStringField($sourceKey, "related_product_ids", "related_product_ids");
		$bxData->addFieldParameter($sourceKey, "related_product_ids", "splitValues", ",");
		
		echo "publish the data specifications<br>";
		$bxData->pushDataSpecifications();
		
		echo "publish the api owner changes<br>"; //if the specifications have changed since the last time they were pushed
		$bxData->publishChanges();
	}
	
	echo "push the data for data sync<br>";
	$bxData->pushData();
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
