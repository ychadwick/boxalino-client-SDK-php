<?php

class BxRequest
{
	protected $indexId;
	protected $language;
	protected $choiceId;
	protected $min;
	protected $max;
	
	protected $returnFields = array();
	protected $offset = 0;
	protected $queryText = "";
	protected $bxFacets = null;
	protected $bxSortFields = null;
	protected $bxFilters = array();
	protected $orFilters = false;
	
	public function __construct($indexId, $language, $choiceId, $max=10, $min=0) {
		$this->indexId = $indexId;
		$this->language = $language;
		$this->choiceId = $choiceId;
		$this->min = (float)$min;
		$this->max = (float)$max;
		if($this->max == 0) {
			$this->max = 1;
		}
	}
	
	public function getReturnFields() {
		return $this->returnFields;
	}
	
	public function setReturnFields($returnFields) {
		$this->returnFields = $returnFields;
	}
	
	public function getOffset() {
		return $this->offset;
	}
	
	public function setOffset($offset) {
		$this->offset = $offset;
	}
	
	public function getQuerytext() {
		return $this->queryText;
	}
	
	public function setQuerytext($queryText) {
		$this->queryText = $queryText;
	}
	
	public function getFacets() {
		return $this->bxFacets;
	}
	
	public function setFacets($bxFacets) {
		$this->bxFacets = $bxFacets;
	}
	
	public function getSortFields() {
		return $this->bxSortFields;
	}
	
	public function setSortFields($bxSortFields) {
		$this->bxSortFields = $bxSortFields;
	}
	
	public function getFilters() {
		$filters = $this->bxFilters;
		if($this->getFacets()) {
			foreach($this->getFacets()->getFilters() as $filter) {
				$filters[] = $filter;
			}
		}
		return $this->bxFilters;
	}
	
	public function setFilters($bxFilters) {
		$this->bxFilters = $bxFilters;
	}
	
	public function addFilter($bxFilter) {
		$this->bxFilters[] = $bxFilter;
	}
	
	public function getOrFilters() {
		return $this->orFilters;
	}
	
	public function setOrFilters($orFilters) {
		$this->orFilters = $orFilters;
	}
	
	public function addSortField($field, $reverse = false) {
		if($this->bxSortFields == null) {
			$this->bxSortFields = new BxSortFields();
		}
		$this->bxSortFields->push($field, $reverse);
	}
	
	public function getChoiceId() {
		return $this->choiceId;
	}
	
	public function setChoiceId($choiceId) {
		$this->choiceId = $choiceId;
	}
	
	public function getMax() {
		return $this->max;
	}
	
	public function setMax($max) {
		$this->max = $max;
	}

	public function getMin() {
		return $this->min;
	}
	
	public function setMin($min) {
		$this->min = $min;
	}

	public function getIndexId() {
		return $this->indexId;
	}
	
	public function setIndexId($indexId) {
		$this->indexId = $indexId;
	}

	public function getLanguage() {
		return $this->language;
	}
	
	public function setLanguage($language) {
		$this->language = $language;
	}

	public function getSimpleSearchQuery() {
		
		$searchQuery = new \com\boxalino\p13n\api\thrift\SimpleSearchQuery();
		$searchQuery->indexId = $this->getIndexId();
		$searchQuery->language = $this->getLanguage();
		$searchQuery->returnFields = $this->getReturnFields();
		$searchQuery->offset = $this->getOffset();
		$searchQuery->hitCount = $this->getMax();
		$searchQuery->queryText = $this->getQueryText();
		if(sizeof($this->getFilters()) > 0) {
			$searchQuery->filters = array();
			foreach($this->getFilters() as $filter) {
				$searchQuery->filters[] = $filter->getThriftFilter();
			}
		}
		$searchQuery->orFilters = $this->getOrFilters();
		if($this->getFacets()) {
			$searchQuery->facetRequests = $this->getFacets()->getThriftFacets();
		}
		if($this->getSortFields()) {
			$searchQuery->sortFields = $this->getSortFields()->getThriftSortFields();
		}

		return $searchQuery;
	}
	
	protected $contextItems = array();
	public function setProductContext($fieldName, $contextItemId, $role = 'mainProduct') {
		$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
		$contextItem->indexId = $this->getAccount();
		$contextItem->fieldName = $fieldName;
		$contextItem->contextItemId = $contextItemId;
		$contextItem->role = $role;
		$this->contextItems[] = $contextItem;
	}
	
	public function setBasketContext($fieldName, $basketContent, $role = 'mainProduct') {
		if ($basketContent !== false && count($basketContent)) {
			
			// Sort basket content by price
			usort($basketContent, function ($a, $b) {
				if ($a['price'] > $b['price']) {
					return -1;
				} elseif ($b['price'] > $a['price']) {
					return 1;
				}
				return 0;
			});

			$basketItem = array_shift($basketContent);

			$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
			$contextItem->indexId = $this->getAccount();
			$contextItem->fieldName = $fieldName;
			$contextItem->contextItemId = $basketItem['id'];
			$contextItem->role = $role;

			$this->contextItems[] = $contextItem;

			foreach ($basketContent as $basketItem) {
				$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
				$contextItem->indexId = $this->getAccount();
				$contextItem->fieldName = $fieldName;
				$contextItem->contextItemId = $basketItem['id'];
				$contextItem->role = $role;

				$this->contextItems[] = $contextItem;
			}
		}
	}
	
	public function getContextItems() {
		return $this->contextItems;
	}

}
