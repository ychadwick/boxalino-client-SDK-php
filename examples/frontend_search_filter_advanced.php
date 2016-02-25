<?php

/**
* In this example, we make a simple search query, add a more advanced filters with 2 fields with values and an or conditions between them and get the search results and print their ids
* Filters are different than facets because they are not returned to the user and should not be related to a user interaction
* Filters should be "system" filters (e.g.: filter on a category within a category page, filter on product which are visible and not out of stock, etc.)
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\bxclient\v1\BxFilter;
BxClient::LOAD_CLASSES($libPath);

//required parameters you should set for this example to work
$account = ""; // your account name
$password = ""; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)

//Create the Boxalino Client SDK instance
//N.B.: you should not create several instances of BxClient on the same page, make sure to save it in a static variable and to re-use it.
$bxClient = new BxClient($account, $password, $domain);

try {
	$language = "en"; // a valid language code (e.g.: "en", "fr", "de", "it", ...)
	$queryText = "women"; // a search query
	$hitCount = 10; //a maximum number of search result to return in one page
	$filterField = "id"; //the field to consider in the filter
	$filterValues = array("41", "1941"); //the field to consider any of the values should match (or not match)
	$filterNegative = true; //false by default, should the filter match the values or not?
	$filterField2 = "products_color"; //the field to consider in the filter
	$filterValues2 = array("Yellow"); //the field to consider any of the values should match (or not match)
	$filterNegative2 = false; //false by default, should the filter match the values or not?
	$orFilters = true; //the two filters are either or (only one of them needs to be correct
	$fieldNames = array("products_color"); //IMPORTANT: you need to put "products_" as a prefix to your field name except for standard fields: "title", "body", "discountedPrice", "price"

	//create search request
	$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
	
	//set the fields to be returned for each item in the response
	$bxRequest->setReturnFields($fieldNames);
	
	//add a filter
	$bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
	$bxRequest->addFilter(new BxFilter($filterField2, $filterValues2, $filterNegative2));
	$bxRequest->setOrFilters($orFilters);
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//make the query to Boxalino server and get back the response for all requests
	$bxResponse = $bxClient->getResponse();
	
	//loop on the search response hit ids and print them
	foreach($bxResponse->getHitFieldValues($fieldNames) as $id => $fieldValueMap) {
		echo "<h3>$id</h3>";
		foreach($fieldValueMap as $fieldName => $fieldValues) {
			echo "$fieldName: " . implode(',', $fieldValues) . "<br>";
		}
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
