<?php

namespace com\boxalino\bxclient\v1;

class BxData
{
	const URL_VERIFY_CREDENTIALS = '/frontend/dbmind/en/dbmind/api/credentials/verify';
	const URL_XML = '/frontend/dbmind/en/dbmind/api/data/source/update';
    const URL_PUBLISH_CONFIGURATION_CHANGES = '/frontend/dbmind/en/dbmind/api/configuration/publish/owner';
	const URL_ZIP = '/frontend/dbmind/en/dbmind/api/data/push';
	
	private $bxClient;
	private $languages;
	private $isDev;
	private $isDelta;
	
	private $sources = array();
	
	private $host = 'http://di1.bx-cloud.com';
	
	private $owner = 'bx_client_data_api';

	public function __construct($bxClient, $languages = array(), $isDev=false, $isDelta=false) {
		$this->bxClient = $bxClient;
		$this->languages = $languages;
		$this->isDev = $isDev;
		$this->isDelta = $isDelta;
	}
	
	public function setLanguages($languages) {
		$this->languages = $languages;
	}
	
	public function getLanguages() {
		return $this->languages;
	}
	
	public function addMainCSVItemFile($filePath, $itemIdColumn, $encoding = 'UTF-8', $delimiter = ',', $enclosure = '&quot;', $escape = "\\", $lineSeparator = "\n", $sourceId = 'item_vals', $container = 'products', $validate=true) {
		$params = array('encoding'=>$encoding, 'delimiter'=>$delimiter, 'enclosure'=>$enclosure, 'escape'=>$escape, 'lineSeparator'=>$lineSeparator);
		$sourceKey = $this->addSourceFile($filePath, $itemIdColumn, $sourceId, $container, 'item_data_file', 'CSV', $params, $validate);
		$this->addSourceIdField($sourceKey, $itemIdColumn, $validate) ;
		$this->addSourceStringField($sourceKey, "bx_item_id", $itemIdColumn, $validate) ;
		return $sourceKey;
	}
	
	public function addSourceFile($filePath, $itemIdColumn, $sourceId, $container, $type, $format='CSV', $params=array(), $validate=true) {
		if(sizeof($this->getLanguages())==0) {
			throw new \Exception("trying to add a source before having declared the languages with method setLanguages");
		}
		if(!isset($this->sources[$container])) {
			$this->sources[$container] = array();
		}
		$params['filePath'] = $filePath;
		$params['itemIdColumn'] = $itemIdColumn;
		$params['format'] = $format;
		$params['type'] = $type;
		$this->sources[$container][$sourceId] = $params;
		if($validate) {
			$this->validateSource($container, $sourceId);
		}
		$this->sourceIdContainers[$sourceId] = $container;
		return $this->encodesourceKey($container, $sourceId);
	}
	
	public function decodeSourceKey($sourceKey) {
		return explode('-', $sourceKey);
	}
	
	public function encodesourceKey($container, $sourceId) {
		return $container.'-'.$sourceId;
	}
	
	public function getSourceCSVRow($container, $sourceId, $row=0, $maxRow = 2) {
		if(!isset($this->sources[$container][$sourceId]['rows'])) {
			if (($handle = fopen($this->sources[$container][$sourceId]['filePath'], "r")) !== FALSE) {
				$count = 1;
				$this->sources[$container][$sourceId]['rows'] = array();
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$this->sources[$container][$sourceId]['rows'][] = $data;
					if($count++>=$maxRow) {
						break;
					}
				}
				fclose($handle);
			}
		}
		if(isset($this->sources[$container][$sourceId]['rows'][$row])) {
			return $this->sources[$container][$sourceId]['rows'][$row];
		}
		return null;
	}
	
	public function validateSource($container, $sourceId) {
		$source = $this->sources[$container][$sourceId];
		if($source['format'] == 'CSV') {
			$this->validateColumnExistance($container, $sourceId, $source['itemIdColumn']);
		}
	}
	
	public function validateColumnExistance($container, $sourceId, $col) {
		$row = $this->getSourceCSVRow($container, $sourceId, 0);
		if(!in_array($col, $row)) {
			throw new \Exception("the source '$sourceId' in the container '$container' declares an column '$col' which is not present in the header row of the provided CSV file: " . implode(',', $row));
		}
	}
	
	public function addSourceIdField($sourceKey, $col, $validate=true) {
		$this->addSourceField($sourceKey, 'bx_id', "id", false, $col, $validate);
	}
	
	public function addSourceTitleField($sourceKey, $colMap, $validate=true) {
		$this->addSourceField($sourceKey, "bx_title", "title", true, $colMap, $validate);
	}
	
	public function addSourceDescriptionField($sourceKey, $colMap, $validate=true) {
		$this->addSourceField($sourceKey, "bx_description", "body", true, $colMap, $validate);
	}
	
	public function addSourceListPriceField($sourceKey, $col, $validate=true) {
		$this->addSourceField($sourceKey, "bx_listprice", "price", false, $col, $validate);
	}
	
	public function addSourceDiscountedPriceField($sourceKey, $col, $validate=true) {
		$this->addSourceField($sourceKey, "bx_discountedprice", "discounted", false, $col, $validate);
	}
	
	public function addSourceLocalizedTextField($sourceKey, $fieldName, $colMap, $validate=true) {
		$this->addSourceField($sourceKey, $fieldName, "text", true, $colMap, $validate);
	}
	
	public function addSourceStringField($sourceKey, $fieldName, $col, $validate=true) {
		$this->addSourceField($sourceKey, $fieldName, "string", false, $col, $validate);
	}
	
	public function addSourceNumberField($sourceKey, $fieldName, $col, $validate=true) {
		$this->addSourceField($sourceKey, $fieldName, "number", false, $col, $validate);
	}
	
	public function addSourceField($sourceKey, $fieldName, $type, $localized, $colMap, $validate=true) {
		list($container, $sourceId) = $this->decodeSourceKey($sourceKey);
		if(!isset($this->sources[$container][$sourceId]['fields'])) {
			$this->sources[$container][$sourceId]['fields'] = array();
		}
		$this->sources[$container][$sourceId]['fields'][$fieldName] = array('type'=>$type, 'localized'=>$localized, 'map'=>$colMap);
		if($this->sources[$container][$sourceId]['format'] == 'CSV') {
			if($localized) {
				if(!is_array($colMap)) {
					throw new \Exception("'$fieldName': invalid column field name for a localized field (expect an array with a column name for each language array(lang=>colName)): " . serialize($colMap));
				}
				foreach($this->getLanguages() as $lang) {
					if(!isset($colMap[$lang])) {
						throw new \Exception("'$fieldName': no language column provided for language '$lang' in provided column map): " . serialize($colMap));
					}
					if(!is_string($colMap[$lang])) {
						throw new \Exception("'$fieldName': invalid column field name for a non-localized field (expect a string): " . serialize($colMap));
					}
					if($validate) {
						$this->validateColumnExistance($container, $sourceId, $colMap[$lang]);
					}
				}
			} else {
				if(!is_string($colMap)) {
					throw new \Exception("'$fieldName' invalid column field name for a non-localized field (expect a string): " . serialize($colMap));
				}
				if($validate) {
					$this->validateColumnExistance($container, $sourceId, $colMap);
				}
			}
		}
	}
	
	public function getXML() {
		
		$xml = new \SimpleXMLElement('<root/>');
		
		//languages
        $languagesXML = $xml->addChild('languages');
        foreach ($this->getLanguages() as $lang) {
            $language = $languagesXML->addChild('language');
            $language->addAttribute('id', $lang);
        }

		//containers
        $containers = $xml->addChild('containers');
		foreach($this->sources as $containerName => $containerSources) {
			
			$container = $containers->addChild('container');
			$container->addAttribute('id', $containerName);
			$container->addAttribute('type', $containerName);

			$sources = $container->addChild('sources');
			$properties = $container->addChild('properties');
        
			//foreach source
			foreach($containerSources as $sourceId => $sourceValues) {
				
				$source = $sources->addChild('source');				
				$source->addAttribute('id', $sourceId);
				$source->addAttribute('type', $sourceValues['type']);
				
				$parts = explode('/', $sourceValues['filePath']);
				$sourceValues['file'] = $parts[sizeof($parts)-1];
				
				$parameters = array(
								'file'=>false,
								'itemIdColumn'=>false, 
								'format'=>'CSV', 
								'encoding'=>'UTF-8', 
								'delimiter'=>',', 
								'enclosure'=>'"', 
								'lineSeparator'=>"\\n"
							);
							
				foreach($parameters as $parameter => $defaultValue) {
					$value = isset($sourceValues[$parameter]) ? $sourceValues[$parameter] : $defaultValue;
					if($value === false) {
						throw new \Exception("source parameter '$parameter' required but not defined in source id '$sourceId' for container '$containerName'");
					}
					$param = $source->addChild($parameter);
					$param->addAttribute('value', $value);
				}
				
				foreach($sourceValues['fields'] as $fieldId => $fieldValues) {
					
					$property = $properties->addChild('property');
					$property->addAttribute('id', $fieldId);
					$property->addAttribute('type', $fieldValues['type']);
					
					$transform = $property->addChild('transform');				
					$logic = $property->addChild('logic');	
					$logic->addAttribute('source', $sourceId);
					$logic->addAttribute('type', 'direct');			
					if($fieldValues['localized']) {
						foreach($this->getLanguages() as $lang) {
							$field = $logic->addChild('field');
							$field->addAttribute('column', $fieldValues['map'][$lang]);
							$field->addAttribute('language', $lang);			
						}
					} else {
						$field = $logic->addChild('field');
						$field->addAttribute('column', $fieldValues['map']);
					}
					
					$params = $property->addChild('params');	
				}
			}
		}
	
		return $xml->asXML();
	}

    protected function callAPI($fields, $url, $temporaryFilePath=null)
    {
        $s = curl_init();
		
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_TIMEOUT, 35000);
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_ENCODING, '');
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $fields);

        $responseBody = curl_exec($s);
		
		if($responseBody === false)
		{
			if(strpos(curl_error($s), "couldn't open file") !== false) {
				if($temporaryFilePath !== null) {
					throw new \Exception('There seems to be a problem with the folder BxData uses to temporarily store a zip file with all your files before sending it. As you are currently provided a path, this is most likely the problem. Please make sure it is a valid path, or leave it to null (default value), then BxData will use sys_get_temp_dir() + "/bxclient" which typically works fine.');
				} else {
					throw new \Exception('There seems to be a problem with the folder BxData uses to temporarily store a zip file with all your files before sending it. This means that the default path BxData uses sys_get_temp_dir() + "/bxclient" is not supported and you need to path a working path to the pushData function.');
				}
			}
			throw new \Exception('Curl error: ' . curl_error($s));
		}

        curl_close($s);
        if (strpos($responseBody, 'Internal Server Error') !== false) {
            throw new \Exception($this->getError($responseBody));;
        }
        return $this->checkResponseBody($responseBody, $url);
    }
	
	public function getError($responseBody) {
		return $responseBody;
	}
	
	public function checkResponseBody($responseBody, $url) {
		if($responseBody == null) {
			throw new \Exception("API response of call to $url is empty string, this is an error!");
		}
		$value = json_decode($responseBody, true);
		if(sizeof($value) != 1 || !isset($value['token'])) {
			if(!isset($value['changes'])) {
				throw new \Exception($responseBody);
			}
		}
		return $value;
	}
	
	public function pushDataSpecifications($ignoreDeltaException=false) {
		
		if(!$ignoreDeltaException && $this->isDelta) {
			throw new \Exception("You should not push specifications when you are pushing a delta file. Only do it when you are preparing full files. Set method parameter ignoreDeltaException to true to ignore this exception and publish anyway.");
		}
		
		$fields = array(
            'username' => $this->bxClient->getUsername(),
            'password' => $this->bxClient->getPassword(),
            'account' => $this->bxClient->getAccount(false),
            'owner' => $this->owner,
            'xml' => $this->getXML()
        );

        $url = $this->host . self::URL_XML;
		return $this->callAPI($fields, $url);
	}
	
	public function checkChanges() {
		$this->publishOwnerChanges(false);
	}
	
	public function publishChanges() {
		$this->publishOwnerChanges(true);
	}
	
	public function publishOwnerChanges($publish=true) {
		$fields = array(
            'username' => $this->bxClient->getUsername(),
            'password' => $this->bxClient->getPassword(),
            'account' => $this->bxClient->getAccount(false),
            'owner' => $this->owner,
			'publish' => ($publish ? 'true' : 'false')
        );

        $url = $this->host . self::URL_PUBLISH_CONFIGURATION_CHANGES;
		return $this->callAPI($fields, $url);
	}
	
	public function getFiles() {
		$files = array();
		foreach($this->sources as $container => $containerSources) {
			foreach($containerSources as $sourceId => $sourceValues) {
				if(!isset($sourceValues['file'])) {
					$parts = explode('/', $sourceValues['filePath']);
					$sourceValues['file'] = $parts[sizeof($parts)-1];
				}
				$files[$sourceValues['file']] = $sourceValues['filePath'];
			}
		}
		return $files;
	}
	
    public function createZip($temporaryFilePath=null, $name='bxdata.zip')
    {
		if($temporaryFilePath === null) {
			$temporaryFilePath = sys_get_temp_dir() . '/bxclient';
		}
		
		if ($temporaryFilePath != "" && !file_exists($temporaryFilePath)) {
            mkdir($temporaryFilePath);
        }
		
		$zipFilePath = $temporaryFilePath . '/' . $name;
		
        if (file_exists($zipFilePath)) {
            @unlink($zipFilePath);
        }
		
		$files = $this->getFiles();
		
		$zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE)) {

            foreach ($files as $f => $filePath) {
                if (!$zip->addFile($filePath, $f)) {
                    throw new \Exception(
                        'Synchronization failure: Failed to add file "' .
                        $filePath . '" to the zip "' .
                        $name . '". Please try again.'
                    );
                }
            }

            if (!$zip->addFromString ('properties.xml', $this->getXML())) {
                throw new \Exception(
                    'Synchronization failure: Failed to add xml string to the zip "' .
                    $name . '". Please try again.'
                );
            }

            if (!$zip->close()) {
                throw new \Exception(
                    'Synchronization failure: Failed to close the zip "' .
                    $name . '". Please try again.'
                );
            }

        } else {
            throw new \Exception(
                'Synchronization failure: Failed to open the zip "' .
                $name . '" for writing. Please check the permissions and try again.'
            );
        }
		
		return $zipFilePath;
    }
	
	public function pushData($temporaryFilePath=null) {
		
		$zipFile = $this->createZip($temporaryFilePath);
		
		$fields = array(
            'username' => $this->bxClient->getUsername(),
            'password' => $this->bxClient->getPassword(),
            'account' => $this->bxClient->getAccount(false),
            'owner' => $this->owner,
			'dev' => ($this->isDev ? 'true' : 'false'),
			'delta' => ($this->isDelta ? 'true' : 'false'),
            'data' => $this->getCurlFile($zipFile, "application/zip")
        );

        $url = $this->host . self::URL_ZIP;
		return $this->callAPI($fields, $url, $temporaryFilePath);
	}

    protected function getCurlFile($filename, $type)
    {
        try {
            if (class_exists('CURLFile')) {
                return new \CURLFile($filename, $type);
            }
        } catch(\Exception $e) {}
        return "@$filename;type=$type";
    }
}
