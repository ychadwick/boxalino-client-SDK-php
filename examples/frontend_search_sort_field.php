<?php

/**
* In this example, we make a simple search query with a special sort order and get the first search results according to this order
*
* NB: MAKE SURE NEVER TO RE-RANK THE RESULTS AFFTER GETTING THEM FROM BOXALINO SEARCH: BECAUSE YOU ONLY RECEIVE THE FIRST PAGE
* TO ORDER THE SEARCH RESULT, YOU MUST PROVIDE THE SORT FIELDS INFORMATION AND REQUEST THE RESULTS DIRECTLY SORTED AND DISPLAY THEM AS RETURNED
*
* NB: DO NOT SORT THE RESULTS UNLESS THE USER HAS REQUESTED A SPECIAL SORTING OPTION, BECAUSE ALL BOXALION RANKING OPTIMIZATION WILL ONLY WORK IF THERE ARE NO SORT FIELDS DEFINED
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
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
	$sortField = "title"; //sort the search results by this field
	$sortDesc = true; //sort in an ascending / descending way

	//create search request
	$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
	
	//add a sort field in the provided direction
	$bxRequest->addSortField($sortField, $sortDesc);
	
	//set the fields to be returned for each item in the response
	$bxRequest->setReturnFields(array($sortField));
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//retrieve the search response object (if no parameter provided, method returns response to first request)
	$bxResponse = $bxClient->getResponse();
	
	//loop on the search response hit ids and print them
	foreach($bxResponse->getHitFieldValues(array($sortField)) as $id => $fieldValueMap) {
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
