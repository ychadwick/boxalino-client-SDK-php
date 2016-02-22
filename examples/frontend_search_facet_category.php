<?php

/**
* In this example, we make a simple search query, request a facet and get the search results and print the facet values and counter of categories.
* We also implement a simple link logic so that if the user clicks on one of the facet values the page is reloaded with the results with this facet value selected and a clickable category bread-crumbs is generated.
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
	$selectedValue = isset($_REQUEST['bx_category_id']) ? $_REQUEST['bx_category_id'] : null;
	
	//create search request
	$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
	
	//add a facert
	$facets = new BxFacets();
	$facets->addCategoryFacet($selectedValue);
	$bxRequest->setFacets($facets);
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//retrieve the search response object (if no parameter provided, method returns response to first request)
	$bxResponse = $bxClient->getResponse();
	
	//get the facet responses
	$facets = $bxResponse->getFacets();
	
	//show the category breadcrumbs
	$level = 0;
	echo "<a href=\"?\">home</a>";
	foreach($facets->getParentCategories() as $categoryId => $categoryLabel) {
		echo ">> <a href=\"?bx_category_id=$categoryId\">$categoryLabel</a>";
		$level++;
	}
	echo "<br><br>";
	
	//show the category facet values
	foreach($facets->getCategories() as $value) {
		echo "<a href=\"?bx_category_id=" . $facets->getCategoryValueId($value) . "\">" . $facets->getCategoryValueLabel($value) . "</a> (" . $facets->getCategoryValueCount($value) . ")<br>";
	}
	echo "<br>";
	
	//loop on the search response hit ids and print them
	foreach($bxResponse->getHitIds() as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
