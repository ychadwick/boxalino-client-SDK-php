<?php

/**
* In this example, we make a simple search query, request a facet and get the search results and print the facet values and counter for price ranges.
* We also implement a simple link logic so that if the user clicks on one of the facet values the page is reloaded with the results with this facet value selected.
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\bxclient\v1\BxFacets;
BxClient::LOAD_CLASSES($libPath);

//required parameters you should set for this example to work
$account = "magento2_test_syl8"; // your account name
$password = "magento2_test_syl8"; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)

//Create the Boxalino Client SDK instance
//N.B.: you should not create several instances of BxClient on the same page, make sure to save it in a static variable and to re-use it.
$bxClient = new BxClient($account, $password, $domain);

try {
	$language = "en"; // a valid language code (e.g.: "en", "fr", "de", "it", ...)
	$queryText = "women"; // a search query
	$hitCount = 10; //a maximum number of search result to return in one page
	$selectedValue = isset($_REQUEST['bx_price']) ? $_REQUEST['bx_price'] : null;

	//create search request
	$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
	
	//add a facert
	$facets = new BxFacets();
	$facets->addPriceRangeFacet($selectedValue);
	$bxRequest->setFacets($facets);
	
	//set the fields to be returned for each item in the response
	$bxRequest->setReturnFields(array($facets->getPriceFieldName()));
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//retrieve the search response object (if no parameter provided, method returns response to first request)
	$bxResponse = $bxClient->getResponse();
	
	//get the facet responses
	$facets = $bxResponse->getFacets();
	
	//loop on the search response hit ids and print them
	foreach($facets->getPriceRanges() as $fieldValue) {
		echo "<a href=\"?bx_price=" . $facets->getPriceValueParameterValue($fieldValue) . "\">" . $facets->getPriceValueLabel($fieldValue) . "</a> (" . $facets->getPriceValueCount($fieldValue) . ")";
		if($facets->isPriceValueSelected($fieldValue)) {
			echo "<a href=\"?\">[X]</a>";
		}
		echo "<br>";
	}
	
	//loop on the search response hit ids and print them
	foreach($bxResponse->getHitFieldValues(array($facets->getPriceFieldName())) as $id => $fieldValueMap) {
		echo "<h3>$id</h3>";
		foreach($fieldValueMap as $fieldName => $fieldValues) {
			echo "Price: " . implode(',', $fieldValues) . "<br>";
		}
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
