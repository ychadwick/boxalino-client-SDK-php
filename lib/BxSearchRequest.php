<?php

class BxSearchRequest extends BxRequest
{
	public function __construct($indexId, $language, $queryText, $max=10, $choiceId=null) {
		if($choiceId == null) {
			$choiceId = 'search';
		}
		parent::__construct($indexId, $language, $choiceId, $max, 0);
		$this->setQueryText($queryText);
	}
}
