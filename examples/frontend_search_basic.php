<?php

/**
* In this example, we make a simple search query, get the search results and print their ids
*/

//required parameters you should set for this example to work
$account = ""; // your account name
$password = ""; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)
$queryText = ""; // a search query
$language = ""; // a valid language code (e.g.: "en", "fr", "de", "it", ...)

//include the Boxalino Client SDK php files
$clientPath = '../code'; //path to the code folder with the Boxalino Client SDK files
$thriftPath = '../lib'; //path to the lib folder with the thrift client files
require_once($clientPath . "/BxClient.php");
BxClient::LOAD_CLASSES($clientPath, $thriftPath);

//Create the Boxalino Client SDK instance
//N.B.: you should not create several instances of BxClient on the same page, make sure to save it in a static variable and to re-use it.
$bxClient = new BxClient($account, $password, $domain);

try {
	//make the search request to Boxalino servers
	$bxClient->search($queryText, $language);
	
	//retrieve the search response object
	$bxChooseResponse = $bxClient->getCurrentSearchResponse();
	
	//loop on the search response hit ids and print them
	foreach($bxChooseResponse->getHitIds() as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
