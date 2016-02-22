<?php

/**
* In this example, we make a simple recommendation example to display cross selling recommendations on a basket page.
*/

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once($libPath . "/BxClient.php");
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
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
	$choiceId = "basket"; //the recommendation choice id (standard choice ids are: "similar" => similar products on product detail page, "complementary" => complementary products on product detail page, "basket" => cross-selling recommendations on basket page, "search"=>search results, "home" => home page personalized suggestions, "category" => category page suggestions, "navigation" => navigation product listing pages suggestions)
	$itemFieldId = "id"; // the field you want to use to define the id of the product (normally id, but could also be a group id if you have a difference between group id and sku)
	$itemFieldIdValuesPrices = array(array("id"=>"1940", "price"=>10.80), array("id"=>"1234", "price"=>130.5)); //the product ids and their prices that the user currently has in his basket
	$hitCount = 10; //a maximum number of recommended result to return in one page

	//create similar recommendations request
	$bxRequest = new BxRecommendationRequest($language, $choiceId, $hitCount);
	
	//indicate the products the user currently has in his basket (reference of products for the recommendations)
	$bxRequest->setBasketProductWithPrices($itemFieldId, $itemFieldIdValuesPrices);
	
	//add the request
	$bxClient->addRequest($bxRequest);
	
	//make the query to Boxalino server and get back the response for all requests
	$bxResponse = $bxClient->getResponse();
	
	//loop on the recommended response hit ids and print them
	foreach($bxResponse->getHitIds() as $i => $id) {
		echo "$i: returned id $id<br>";
	}
	
} catch(\Exception $e) {
	
	//be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
	echo $e->getMessage();
	exit;
}
