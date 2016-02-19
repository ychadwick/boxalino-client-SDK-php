<?php

class BxChooseResponse 
{
	private $response;
	private $bxRecommendations;
	private $facets;
	public function __construct($response, $bxRecommendations=array(), $facets = null) {
		$this->response = $response;
		$this->bxRecommendations = $bxRecommendations;
		$this->facets = $facets;
	}
	
	public function getResponse() {
		return $this->response;
	}
	
	public function getChoiceResponseVariant($choice=null) {
        $id = 0;
		foreach($this->bxRecommendations as $k => $bxRecommendation) {
			if($choice != null && $choice == $bxRecommendation->getChoiceId()) {
				$id = $k;
			}
		}
		return $this->getChoiceIdResponseVariant($id);
	}
	
	protected function getChoiceIdResponseVariant($id=0) {
        $response = $this->getResponse();
		if (!empty($response->variants) && isset($response->variants[$id])) {
            return $response->variants[$id];
		}
		throw new \Exception("no variant provided in choice response for variant id $id");
	}
	
	protected function getFirstPositiveSuggestionSearchResult($variant) {
        if(!isset($variant->searchRelaxation->suggestionsResults)) {
            return null;
        }
		foreach($variant->searchRelaxation->suggestionsResults as $searchResult) {
			if($searchResult->totalHitCount > 0) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getVariantSearchResult($variant, $considerRelaxation=true) {
		$searchResult = $variant->searchResult;
		if($considerRelaxation && $variant->searchResult->totalHitCount == 0) {
			return $this->getFirstPositiveSuggestionSearchResult($variant);
		}
		return $searchResult;
	}
	
	public function getSearchResultHitIds($searchResult) {
		$ids = array();
        foreach ($searchResult->hits as $item) {
			$ids[] = $item->values['id'][0];
		}
        return $ids;
	}

    public function getHitIds($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		return $this->getSearchResultHitIds($this->getVariantSearchResult($variant, $considerRelaxation));
    }
	
	public function getSearchHitFieldValues($searchResult) {
		$fieldValues = array();
		foreach ($searchResult->hits as $item) {
			foreach ($fields as $field) {
				if (isset($item->values[$field])) {
					if (!empty($item->values[$field])) {
						$fieldValues[$item->values['id'][0]][$field] = $item->values[$field];
					}
				}
			}
		}
		return $fieldValues;
	}

    public function getFacets($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation);
		$facets = $searchResult->facetResponses;
		$this->facets->setFacetResponse($variant->searchResult->facetResponses);
		return $this->facets;
    }

    public function getHitFieldValues($choice, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		return $this->getSearchHitFieldValues($this->getVariantSearchResult($variant, $considerRelaxation));
    }

    public function getTotalHitCount($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		return $this->getVariantSearchResult($variant, $considerRelaxation)->totalHitCount;
    }
	
	public function areResultsCorrected($choice=null) {
        return $this->getTotalHitCount($choice, false) == 0 && $this->getTotalHitCount($choice) > 0 && $this->areThereSubPhrases() == false;
	}
	
	public function getCorrectedQuery($choice=null) {
		$variant = $this->getChoiceResponseVariant($choice);
		$searchResult = $this->getVariantSearchResult($variant);
		if($searchResult) {
			return $searchResult->queryText;
		}
		return null;
	}
	
	public function areThereSubPhrases($choice=null) {
		$variant = $this->getChoiceResponseVariant($choice);
		return isset($variant->searchRelaxation->subphrasesResults) && sizeof($variant->searchRelaxation->subphrasesResults) > 0;
	}
	
	public function getSubPhrasesQueries($choice=null) {
		if(!$this->areThereSubPhrases($choice)) {
			return array();
		}
		$queries = array();
		$variant = $this->getChoiceResponseVariant($choice);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			$queries[] = $searchResult->queryText;
		}
		return $queries;
	}
	
	protected function getSubPhraseSearchResult($queryText, $choice=null) {
		if(!$this->areThereSubPhrases()) {
			return null;
		}
		$variant = $this->getChoiceResponseVariant($choice);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			if($searchResult->queryText == $queryText) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getSubPhraseTotalHitCount($queryText, $choice=null) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice);
		if($searchResult) {
			return $searchResult->totalHitCount;
		}
		return 0;
	}

    public function getSubPhraseHitIds($queryText, $choice=null) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice);
		if($searchResult) {
			return $this->getSearchResultHitIds($searchResult);
		}
		return array();
    }
}
