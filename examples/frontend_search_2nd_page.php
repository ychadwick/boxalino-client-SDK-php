<?php

/**
* In this example, we make a simple search query, get the second page of search results and print their ids
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
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
	$offset = 10; //the offset to start the page with (if = hitcount ==> page 2)
	
	//create search request
	$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
	
	//set an offset for the returned search results (start at position provided)
	$bxRequest->setOffset($offset);
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//make the query to Boxalino server and get back the response for all requests
	$bxResponse = $bxClient->getResponse();
	
	//loop on the search response hit ids and print them
	foreach($bxResponse->getHitIds() as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
