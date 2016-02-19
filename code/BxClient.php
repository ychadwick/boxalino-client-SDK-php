<?php

class BxClient
{
	private $account;
	private $password;
	private $isDev;
	private $host;
	private $port;
	private $uri;
	private $schema;
	private $p13n_username;
	private $p13n_password;
	private $domain;
	private $language;
	private $additionalFields;
	private $p13n;
	private $facets;
	
	private $filters = array();
	private $autocompleteResponse = null;
	private $searchResponse = null;
	private $recommendationsResponse = null;
	
	
    const VISITOR_COOKIE_TIME = 31536000;

	public function __construct($account, $password, $domain, $language=null, $isDev=false, $host=null, $port=null, $uri=null, $schema=null, $p13n_username=null, $p13n_password=null, $additionalFields=array()) {
		$this->account = $account;
		$this->password = $password;
		$this->isDev = $isDev;
		$this->host = $host;
		if($this->host == null) {
			$this->host = "cdn.bx-cloud.com";
		}
		$this->port = $port;
		if($this->port == null) {
			$this->port = 443;
		}
		$this->uri = $uri;
		if($this->uri == null) {
			$this->uri = '/p13n.web/p13n';
		}
		$this->schema = $schema;
		if($this->schema == null) {
			$this->schema = 'https';
		}
		$this->p13n_username = $p13n_username;
		if($this->p13n_username == null) {
			$this->p13n_username = "boxalino";
		}
		$this->p13n_password = $p13n_password;
		if($this->p13n_password == null) {
			$this->p13n_password = "tkZ8EXfzeZc6SdXZntCU";
		}
		$this->domain = $domain;
		$this->language = $language;
		$this->additionalFields = $additionalFields;
		
		$this->facets = new \BxFacets();
	}
	
	public static function LOAD_CLASSES($codePath, $libPath) {
		
		require_once($libPath . '/Thrift/ClassLoader/ThriftClassLoader.php');		
		$cl = new \Thrift\ClassLoader\ThriftClassLoader(false);
		$cl->registerNamespace('Thrift', $libPath);
		$cl->register();
		require_once($libPath . '/P13nService.php');
		require_once($libPath . '/Types.php');

		require_once($codePath . "/BxFacets.php");
		require_once($codePath . "/BxFilter.php");
		require_once($codePath . "/BxRecommendation.php");
		require_once($codePath . "/BxSortFields.php");
		require_once($codePath . "/BxChooseResponse.php");
	}

    /**
     * @param string $field field name for filter
     * @param int $hierarchyId names of categories in hierarchy
     * @param int $hierarchy names of categories in hierarchy
     * @param string|null $lang
     *
     */
    public function addFilterHierarchy($field, $hierarchyId, $hierarchy, $localized = false)
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();

        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }
        
        $filter->hierarchyId = $hierarchyId;
        $filter->hierarchy = $hierarchy;

        $this->filters[] = $filter;
    }

    /**
     * @param string $field field name for filter
     * @param mixed $value filter value
     * @param string|null $lang
     *
     */
    public function addFilter($field, $value, $localized = false, $prefix = 'products_', $bodyName = 'description')
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();
		
		if ($field == $bodyName) {
			$field = 'body';
		} else {
			$field = $prefix . $field;
		}
		
        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }

        if (is_array($value)) {
            $filter->stringValues = $value;
        } else {
            $filter->stringValues = array($value);
        }

        $this->filters[] = $filter;
    }
	
	public function addBxFilter($bxFilter) {
        $this->filters[] = $bxFilter->getThriftFilter();
	}
	
    public function addFilterCategory($categoryId, $categoryName)
    {
		$filter = new \com\boxalino\p13n\api\thrift\Filter();

		$filter->fieldName = 'categories';

		$filter->hierarchyId = $categoryId;
		$filter->hierarchy = array($categoryName);

		$this->filters[] = $filter;
    }

    /**
     * @param string $field field name for filter
     * @param number $from param from
     * @param number $to param from
     * @param string|null $lang
     *
     */
    public function addFilterFromTo($field, $from, $to, $localized = false)
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();

		if ($field == 'price') {
			$field = 'discountedPrice';
		}

        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }

        $filter->rangeFrom = $from;
        $filter->rangeTo = $to;

        $this->filters[] = $filter;
    }
	
	public function getAccount() {
		if($this->isDev) {
			return $this->account . '_dev';
		}
		return $this->account;
	}
	
	private function getSessionAndProfile() {
		if (empty($_COOKIE['cems'])) {
            $sessionid = session_id();
            if (empty($sessionid)) {
                session_start();
                $sessionid = session_id();
            }
        } else {
            $sessionid = $_COOKIE['cems'];
        }

        if (empty($_COOKIE['cemv'])) {
            $profileid = '';
            if (function_exists('openssl_random_pseudo_bytes')) {
                $profileid = bin2hex(openssl_random_pseudo_bytes(16));
            }
            if (empty($profileid)) {
                $profileid = uniqid('', true);
            }
        } else {
            $profileid = $_COOKIE['cemv'];
        }

        // Refresh cookies
        if (empty($this->domain)) {
            setcookie('cems', $sessionid, 0);
            setcookie('cemv', $profileid, time() + self::VISITOR_COOKIE_TIME);
        } else {
            setcookie('cems', $sessionid, 0, '/', $this->domain);
            setcookie('cemv', $profileid, time() + 1800, '/', self::VISITOR_COOKIE_TIME);
        }
		
		return array($sessionid, $profileid);
	}
	
	private function getUserRecord() {
		$userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
        $userRecord->username = $this->getAccount();
        return $userRecord;
	}
	
    private function getP13n($sendTimeout=120000, $recvTimeout=120000, $useCurlIfAvailable=true)
    {
        $transport = new \Thrift\Transport\TSocket($this->host, $this->port);
		$transport->setSendTimeout($sendTimeout);
		$transport->setRecvTimeout($recvTimeout);
		
		if($useCurlIfAvailable && function_exists('curl_version')) {
			$transport = new \Thrift\Transport\P13nTCurlClient($this->host, $this->port, $this->uri, $this->schema);
		} else {
			$transport = new \Thrift\Transport\P13nTHttpClient($this->host, $this->port, $this->uri, $this->schema);
		}
		$transport->setAuthorization($this->p13n_username, $this->p13n_password);
		$client = new \com\boxalino\p13n\api\thrift\P13nServiceClient(new \Thrift\Protocol\TCompactProtocol($transport));
		$transport->open();
		return $client;
    }
	
	public function getChoiceRequest($inquiries, $requestContext = null) {
		
		$choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();

        list($sessionid, $profileid) = $this->getSessionAndProfile();
        
		$choiceRequest->userRecord = $this->getUserRecord();
		$choiceRequest->profileId = $profileid;
		$choiceRequest->inquiries = $inquiries;
		if($requestContext == null) {
			$requestContext = $this->getRequestContext();
		}
		$choiceRequest->requestContext = $requestContext;

        return $choiceRequest;
	}

	private function getSimpleSearchQuery($returnFields, $hitCount, $queryText, $bxFacets = array(), $bxSortFields = null, $offset = 0) {
		$searchQuery = new \com\boxalino\p13n\api\thrift\SimpleSearchQuery();
		$searchQuery->indexId = $this->getAccount();
		$searchQuery->language = $this->language;
		$searchQuery->returnFields = $returnFields;
		$searchQuery->offset = $offset;
		$searchQuery->hitCount = $hitCount;
		$searchQuery->queryText = $queryText;
		if($bxFacets) {
			$searchQuery->facetRequests = $bxFacets->getThriftFacets();
		}

		if($bxSortFields) {
			$searchQuery->sortFields = $bxSortFields->getThriftSortFields();
		}

		return $searchQuery;
	}
	
	protected function getIP()
    {
        $ip = null;
        $clientip = @$_SERVER['HTTP_CLIENT_IP'];
        $forwardedip = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        if (filter_var($clientip, FILTER_VALIDATE_IP)) {
            $ip = $clientip;
        } elseif (filter_var($forwardedip, FILTER_VALIDATE_IP)) {
            $ip = $forwardedip;
        } else {
            $ip = @$_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
	
    protected function getCurrentURL()
    {
        $protocol = strpos(strtolower(@$_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
        $hostname = @$_SERVER['HTTP_HOST'];
        $requesturi = @$_SERVER['REQUEST_URI'];

        return $protocol . '://' . $hostname . $requesturi;
    }
	
	protected function getRequestContext()
    {
        list($sessionid, $profileid) = $this->getSessionAndProfile();
		
        $requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
        $requestContext->parameters = array(
            'User-Agent'     => array(@$_SERVER['HTTP_USER_AGENT']),
            'User-Host'      => array($this->getIP()),
            'User-SessionId' => array($sessionid),
            'User-Referer'   => array(@$_SERVER['HTTP_REFERER']),
            'User-URL'       => array($this->getCurrentURL())
        );

        if (isset($_REQUEST['p13nRequestContext']) && is_array($_REQUEST['p13nRequestContext'])) {
            $requestContext->parameters = array_merge(
                $_REQUEST['p13nRequestContext'],
                $requestContext->parameters
            );
        }

        return $requestContext;
    }
	
	private function throwCorrectP13nException($e) {
		if(strpos($e->getMessage(), 'Could not connect ') !== false) {
			throw new \Exception('The connection to our server failed even before checking your credentials. This might be typically caused by 2 possible things: wrong values in host, port, schema or uri (typical value should be host=cdn.bx-cloud.com, port=443, uri =/p13n.web/p13n and schema=https, your values are : host=' . $this->host . ', port=' . $this->port . ', schema=' . $this->schema . ', uri=' . $this->uri . '). Another possibility, is that your server environment has a problem with ssl certificate (peer certificate cannot be authenticated with given ca certificates), which you can either fix, or avoid the problem by adding the line "curl_setopt(self::$curlHandle, CURLOPT_SSL_VERIFYPEER, false);" in the file "lib\Thrift\Transport\P13nTCurlClient" after the call to curl_init in the function flush. Full error message=' . $e->getMessage());
		}
		if(strpos($e->getMessage(), 'Bad protocol id in TCompact message') !== false) {
			throw new \Exception('The connection to our server has worked, but your credentials were refused. Provided credentials username=' . $this->p13n_username. ', password=' . $this->p13n_password . '. Full error message=' . $e->getMessage());
		}
		if(strpos($e->getMessage(), 'choice not found') !== false) {
			$parts = explode('choice not found', $e->getMessage());
			$pieces = explode('	at ', $parts[1]);
			$choiceId = str_replace(':', '', trim($pieces[0]));
			throw new \Exception("Configuration not live on account " . $this->getAccount() . ": choice $choiceId doesn't exist");
		}
		throw $e;
	}
	
	private function p13nchoose($choiceRequest) {
		try {
			return $this->getP13n()->choose($choiceRequest);
		} catch(\Exception $e) {
			$this->throwCorrectP13nException($e);
		}
	}
	
	public function setBxFacets($facets) {
		$this->facets = $facets;
	}
	
	public function getBxFacets() {
		return $this->facets;
	}
	
	/**
	* RECOMMENDATIONS METHODS
	*/
	
	protected function recommend($bxRecommendations, $returnFields = array(), $bxFacets = null, $bxSortFields=null, $queryText=null) {
		
		$choiceInquiries = array();
		
		$requestContext = $this->getRequestContext();

		$contextItems = array();
		
		foreach($bxRecommendations as $bxRecommendation) {
			$searchQuery = $this->getSimpleSearchQuery($returnFields, $bxRecommendation->getMax(), $queryText, $bxFacets, $bxSortFields);
			
			$choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
			$choiceInquiry->choiceId = $bxRecommendation->getChoiceId();
			$choiceInquiry->simpleSearchQuery = $searchQuery;
			$choiceInquiry->contextItems = $contextItems;
			$choiceInquiry->minHitCount = $bxRecommendation->getMin();
			
			$choiceInquiries[] = $choiceInquiry;
		}

		$choiceRequest = $this->getChoiceRequest($choiceInquiries, $requestContext);
		$this->recommendationsResponse = $this->p13nchoose($choiceRequest);
	}
	
	public function getCurrentRecommendationsResponse($bxRecommendations, $returnFields) {
		if(!$this->recommendationsResponse) {
			$this->recommend($bxRecommendations, $returnFields);
		}
		return new \BxChooseResponse($this->recommendationsResponse, $bxRecommendations);
	}
	
	/**
	* SERCH METHODS
	*/

	public function search($queryText, $language = null, $hitCount = 10, $returnFields = array(), $searchChoice = 'search', $bxFacets = null, $offset = 0, $bxSortFields=null, $withRelaxation = true) {
		
		if($language != null) {
			$this->language = $language;
		}

		$simpleSearchQuery = $this->getSimpleSearchQuery($returnFields, $hitCount, $queryText, $bxFacets, $bxSortFields, $offset);

		$choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
		$choiceInquiry->choiceId = $searchChoice;
        $choiceInquiry->simpleSearchQuery = $simpleSearchQuery;
        $choiceInquiry->withRelaxation = $withRelaxation;
		
		$choiceRequest = $this->getChoiceRequest(array($choiceInquiry));

		if(isset($_REQUEST['show_search_request']) && $_REQUEST['show_search_request'] == 'true') {
			print_r($choiceRequest);
			exit;
		}
		$this->searchResponse = $this->p13nchoose($choiceRequest);
	}
	
	public function isSearchDone() {
		return $this->searchResponse != null;
	}
	
	public function getCurrentSearchResponse() {
		return new \BxChooseResponse($this->searchResponse);
	}
	
	/**
	* AUTOCOMPLETE METHODS
	*/
	
	private function p13nautocomplete($autocompleteRequest) {
		try {
			return $this->getP13n()->autocomplete($choiceRequest);
		} catch(\Exception $e) {
			$this->throwCorrectP13nException($e);
		}
	}
	
	private function getAutocompleteQuery($queryText, $suggestionsHitCount) {
		$autocompleteQuery = new \com\boxalino\p13n\api\thrift\AutocompleteQuery();
        $autocompleteQuery->indexId = $this->getAccount();
        $autocompleteQuery->language = $this->language;
        $autocompleteQuery->queryText = $queryText;
        $autocompleteQuery->suggestionsHitCount = $suggestionsHitCount;
        $autocompleteQuery->highlight = true;
        $autocompleteQuery->highlightPre = '<em>';
        $autocompleteQuery->highlightPost = '</em>';
		return $autocompleteQuery;
	}
	
    public function autocomplete($queryText, $suggestionsHitCount, $hitCount = 0, $returnFields = array(), $autocompleteChoice = 'autocomplete', $searchChoice = 'search')
    {
        $searchQuery = $this->getSimpleSearchQuery($returnFields, $hitCount, $queryText);
		
		list($sessionid, $profileid) = $this->getSessionAndProfile();
        
		$autocompleteRequest = new \com\boxalino\p13n\api\thrift\AutocompleteRequest();
		$autocompleteRequest->userRecord = $this->getUserRecord();
		$autocompleteRequest->profileId = $profileid;
		$autocompleteRequest->choiceId = $autocompleteChoice;
        $autocompleteRequest->searchQuery = $searchQuery;
        $autocompleteRequest->searchChoiceId = $searchChoice;
		$autocompleteRequest->autocompleteQuery = $this->getAutocompleteQuery($queryText, $suggestionsHitCount);
        
		$this->autocompleteResponse = $this->p13nautocomplete($autocompleteRequest);

    }
	
	public function getAutocompleteResponse() {
		if($this->autocompleteResponse == null) {
			throw new \Exception("getAutocompleteResponse called before any call to autocomplete method");
		}
		return $this->autocompleteResponse;
	}

    public function getACPrefixSearchHash() {
        if ($this->getAutocompleteResponse()->prefixSearchResult->totalHitCount > 0) {
            return substr(md5($this->getAutocompleteResponse()->prefixSearchResult->queryText), 0, 10);
        } else {
            return null;
        }
    }
	
	public function getAutocompleteTextualSuggestions() {
		$suggestions = array();
		foreach ($this->getAutocompleteResponse()->hits as $hit) {
			$suggestions[] = $hit->suggestion;
        }
        return $suggestions;
	}
	
	protected function getAutocompleteTextualSuggestionHit($suggestion) {
		foreach ($this->getAutocompleteResponse()->hits as $hit) {
			if($hit->suggestion == $suggestion) {
				return $hit;
			}
		}
		throw new \Exception("unexisting textual suggestion provided " . $suggestion);
	}
	
	public function getAutocompleteTextualSuggestionTotalHitCount($suggestion) {
		$hit = $this->getAutocompleteTextualSuggestionHit($suggestion);
		return $hit->searchResult->totalHitCount;
	}
	
	public function getAutocompleteProducts($fields, $suggestion=null) {
		$searchResult = $suggestion == null ? $this->getAutocompleteResponse()->prefixSearchResult : $this->getAutocompleteTextualSuggestionHit($suggestion)->searchResult;
		
		$products = array();
		foreach($searchResult->hits as $item) {
			$values = array();
			foreach($fields as $field) {
				if(isset($item->values[$field])) {
					$values[$field] = $item->values[$field];
				} else {
					$values[$field] = array();
				}
			}
			$k = isset($item->values['id'][0]) ? $item->values['id'][0] : sizeof($products);
			$products[$k] = $values;
		}
		return $products;
	}
}
