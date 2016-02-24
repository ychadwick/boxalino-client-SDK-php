<?php

/**
* In this example, we take very simple CSV files with product data, customer data and transactions historical data generate the specifications, load them, publish them and push the data to Boxalino Data Intelligence
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
	
	$productFile = '../sample_data/products.csv'; //a csv file with header row
	$itemIdColumn = 'id'; //the column header row name of the csv with the unique id of each item
	
	$customerFile = '../sample_data/customers.csv'; //a csv file with header row
	$customerIdColumn = 'customer_id'; //the column header row name of the csv with the unique id of each item
	
	$transactionFile = '../sample_data/transactions.csv'; //a csv file with header row, this file should contain one entry per product and per transaction (so the same transaction should appear several time if it contains more than 1 product
	$orderIdColumn = 'order_id'; //the column header row name of the csv with the order (or transaction) id
	$transactionProductIdColumn = 'product_id'; //the column header row name of the csv with the product id
	$transactionCustomerIdColumn = 'customer_id'; //the column header row name of the csv with the customer id
	$orderDateIdColumn = 'order_date'; //the column header row name of the csv with the order date
	$totalOrderValueColumn = 'total_order_value'; //the column header row name of the csv with the total order value
	$productListPriceColumn = 'price'; //the column header row name of the csv with the product list price
	$productDiscountedPriceColumn = 'discounted_price'; //the column header row name of the csv with the product price after discounts (real price paid)
	
	//optional fields, provided here with default values (so, no effect if not provided), matches the field to connect to the transaction product id and customer id columns (if the ids are not the same as the itemIdColumn of your products and customers files, then you can define another field)
	$transactionProductIdField = 'bx_item_id'; //default value (can be left null) to define a specific field to map with the product id column
	$transactionCustomerIdField = 'bx_customer_id'; //default value (can be left null) to define a specific field to map with the product id column
	
	//add a csv file as main product file
	$bxData->addMainCSVItemFile($productFile, $itemIdColumn);
	
	//add a csv file as main customer file
	$bxData->addMainCSVCustomerFile($customerFile, $customerIdColumn);
	
	//add a csv file as main customer file
	$bxData->setCSVTransactionFile($transactionFile, $orderIdColumn, $transactionProductIdColumn, $transactionCustomerIdColumn, $orderDateIdColumn, $totalOrderValueColumn, $productListPriceColumn, $productDiscountedPriceColumn, $transactionProductIdField, $transactionCustomerIdField);
	
	//this part is only necessary to do when you push your data in full, as no specifications changes should not be published without a full data sync following next
	//even when you publish your data in full, you don't need to repush your data specifications if you know they didn't change, however, it is totally fine (and suggested) to push them everytime if you are not sure if something changed or not
	if(!$isDelta) {
		
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
