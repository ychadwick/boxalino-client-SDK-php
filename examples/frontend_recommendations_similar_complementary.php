<?php

/**
* In this example, we make a simple recommendation example to display both similar and complementary recommendations on a product detail page (both should be called in one call like in the example here, to avoid the risk that the same recommendations could appear twice which can happen if you do two separate requests). Therefore, consider keeping your bxClient object global, push both requests first and then get the response for each choice id.
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
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
	$choiceIdSimilar = "similar"; //the recommendation choice id (standard choice ids are: "similar" => similar products on product detail page, "complementary" => complementary products on product detail page, "basket" => cross-selling recommendations on basket page, "search"=>search results, "home" => home page personalized suggestions, "category" => category page suggestions, "navigation" => navigation product listing pages suggestions)
	$choiceIdComplementary = "complementary";
	$itemFieldId = "id"; // the field you want to use to define the id of the product (normally id, but could also be a group id if you have a difference between group id and sku)
	$itemFieldIdValue = "1940"; //the product id the user is currently looking at
	$hitCount = 10; //a maximum number of recommended result to return in one page

	
	//create similar recommendations request
	$bxRequestSimilar = new BxRecommendationRequest($language, $choiceIdSimilar, $hitCount);
	//indicate the product the user is looking at now (reference of what the recommendations need to be similar to)
	$bxRequestSimilar->setProductContext($itemFieldId, $itemFieldIdValue);
	//add the request
	$bxClient->addRequest($bxRequestSimilar);
	
	
	//create complementary recommendations request
	$bxRequestComplementary = new BxRecommendationRequest($language, $choiceIdComplementary, $hitCount);
	//indicate the product the user is looking at now (reference of what the recommendations need to be similar to)
	$bxRequestComplementary->setProductContext($itemFieldId, $itemFieldIdValue);
	//add the request
	$bxClient->addRequest($bxRequestComplementary);
	
	//make the query to Boxalino server and get back the response for all requests (make sure you have added all your requests before calling getResponse; i.e.: do not push the first request, then call getResponse, then add a new request, then call getResponse again it wil not work; N.B.: if you need to do to separate requests call, then you cannot reuse the same instance of BxClient, but need to create a new one)
	$bxResponse = $bxClient->getResponse();
	
	//loop on the recommended response hit ids and print them
	echo "recommendations of similar items:<br>";
	foreach($bxResponse->getHitIds($choiceIdSimilar) as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	echo "<br><br>";
	
	//retrieve the recommended responses object of the complementary request
	echo "recommendations of complementary items:<br>";
	//loop on the recommended response hit ids and print them
	foreach($bxResponse->getHitIds($choiceIdComplementary) as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
