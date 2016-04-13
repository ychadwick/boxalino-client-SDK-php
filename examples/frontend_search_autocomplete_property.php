<?php

/**
* In this example, we show how to get autocomplete response on a property (to see what property value start with the query as prefix and how many search result they return if searched)
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxAutocompleteRequest;
BxClient::LOAD_CLASSES($libPath);

//required parameters you should set for this example to work
$account = ""; // your account name
$password = ""; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)

//Create the Boxalino Client SDK instance
//N.B.: you should not create several instances of BxClient on the same page, make sure to save it in a static variable and to re-use it.
$bxClient = new BxClient($account, $password, $domain);

try {
	$language = "de"; // a valid language code (e.g.: "en", "fr", "de", "it", ...)
	$queryText = "a"; // a search query to be completed
	$textualSuggestionsHitCount = 10; //a maximum number of search textual suggestions to return in one page
	$property = 'categories'; //the properties to do a property autocomplete request on, be careful, except the standard "categories" which always work, but return values in an encoded way with the path ( "ID/root/level1/level2"), no other properties are available for autocomplete request on by default, to make a property "searcheable" as property, you must set the field parameter "propertyIndex" to "true"
	$propertyTotalHitCount = 5; //the maximum number of property values to return
	$propertyEvaluateCounters = true; //should the count of results for each property value be calculated? if you do not need to retrieve the total count for each property value, please leave the 3rd parameter empty or set it to false, your query will go faster

	//create search request
	$bxRequest = new BxAutocompleteRequest($language, $queryText, $textualSuggestionsHitCount);
	
	//indicate to the request a property index query is requested
	$bxRequest->addPropertyQuery($property, $propertyTotalHitCount, true);
	
	//set the request
	$bxClient->setAutocompleteRequest($bxRequest);
	
	//make the query to Boxalino server and get back the response for all requests
	$bxAutocompleteResponse = $bxClient->getAutocompleteResponse();
	
	//loop on the search response hit ids and print them
	echo "property suggestions for \"$queryText\":<br>";
	foreach($bxAutocompleteResponse->getPropertyHitValues($property) as $hitValue) {
		$label = $bxAutocompleteResponse->getPropertyHitValueLabel($property, $hitValue);
		$totalHitCount = $bxAutocompleteResponse->getPropertyHitValueTotalHitCount($property, $hitValue);
		echo "<div>$hitValue</b> : label=$label, totalHitCount=$totalHitCount</div>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
