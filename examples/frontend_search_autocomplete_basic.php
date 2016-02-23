<?php

/**
* In this example, we make a simple search autocomplete query, get the textual search suggestions
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
	$language = "en"; // a valid language code (e.g.: "en", "fr", "de", "it", ...)
	$queryText = "whit"; // a search query to be completed
	$textualSuggestionsHitCount = 10; //a maximum number of search textual suggestions to return in one page

	//create search request
	$bxRequest = new BxAutocompleteRequest($language, $queryText, $textualSuggestionsHitCount);
	
	//set the request
	$bxClient->setAutocompleteRequest($bxRequest);
	
	//make the query to Boxalino server and get back the response for all requests
	$bxAutocompleteResponse = $bxClient->getAutocompleteResponse();
	
	//loop on the search response hit ids and print them
	echo "textual suggestions for \"$queryText\":<br>";
	foreach($bxAutocompleteResponse->getTextualSuggestions() as $suggestion) {
		echo "$suggestion<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
