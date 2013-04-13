<?php
/* 
 * This class will format and return xml responses in a standardized fassion
 */
class MLCApiResponse{
	const RESPONSE_TYPE_XML = 'XML';
	const RESPONSE_TYPE_JSON = 'JSON';
	const DEFAULT_MAX_RETURN = 10;
	protected $strResponseType = null;
    protected $arrHeaders = array();
    protected $mixBody = null;
    public function __construct($mixBody, $arrHeaders = array()) {
    	
        if(!is_null($arrHeaders)){
            $this->arrHeaders = $arrHeaders;
        }        
        $this->mixBody = $mixBody;
    }
	public function Head($strKey, $mixVal = null){
		if(is_null($mixVal)){
			if(array_key_exists($strKey, $this->arrHeaders)){
				return $this->arrHeaders[$strKey];
			}
			return null;
		}else{
			return $this->arrHeaders[$strKey] = $mixVal;
		}
	}
    public function RenderResponse($blnSetHeader = true){
    	
    	switch(MLCApiDriver::$strResponseType){
    		case(self::RESPONSE_TYPE_XML):
    		
    			if($blnSetHeader){
		            header ("content-type: text/xml");
		        }
    			return $this->RenderAsXML();
    		break;
    		case(self::RESPONSE_TYPE_JSON):
			default:
		    	if($blnSetHeader){
		           // header ("content-type: application/json");
		        }
    			return $this->RenderAsJSON();
    		break;
    		
    	}
    }
    public function RenderAsJSON(){
    	$intPage = MLCApiDriver::GetQueryString(MLCApiQS::PAGE);
		if(is_null($intPage)){
			$intPage = 0;
		}
		$intMaxReturn = MLCApiDriver::GetQueryString(MLCApiQS::MAX_RETURN);
		if(is_null($intMaxReturn)){
			$intMaxReturn = self::DEFAULT_MAX_RETURN;
		}
    	$arrResponse = array('head'=>array());

		
    	if(is_string($this->mixBody)){
        	$arrResponse['body'] = $this->mixBody;
        }elseif(is_array($this->mixBody)){
        	$arrBody = array();
			$this->Head('total', count($this->mixBody));
			$this->Head('page', $intPage);
			if(count($this->mixBody) >= (($intPage + 1) * $intMaxReturn)){
				$this->Head('next_page', ($intPage + 1));
			}
			$this->mixBody = array_slice($this->mixBody, ($intPage * $intMaxReturn), $intMaxReturn);
			$arrBody = $this->ConvertObjectsToArray($this->mixBody);
        	/*foreach($this->mixBody as $strKey=>$strBody){
        		if(is_string($strBody)){
		        	$arrBody[$strKey] = $strBody;
		        }elseif(method_exists($strBody, '__toJson')){
        			$arrBody[$strKey] = $strBody->__toJson(0,0,true);
		        }
        	}*/
			$arrResponse['body'] = $arrBody;
        }elseif(method_exists($this->mixBody, '__toJson')){
			$arrResponse['body'] = $this->mixBody->__toJson(0,0, true);
        }elseif(is_null($this->mixBody)){
        	$arrResponse['body'] = array();
			$this->Head('total', 0);
        }else{
        	throw new Exception("Invalid body supplied");
        }   
		$arrResponse['head'] =($this->arrHeaders); 	
    	$strCallBack = MLCApiDriver::GetQueryString(MLCApiQS::JSON_CALLBACK);
    	if(!is_null($strCallBack)){
    		return sprintf("%s(%s);", $strCallBack, json_encode($arrResponse));
    	}else{
    		return json_encode($arrResponse);
    	}
    }
	public function ConvertObjectsToArray($arrData){
		foreach($arrData as $intIndex => $mixData){
			if(is_array($mixData)){
				$arrData[$intIndex] = self::ConvertObjectsToArray($mixData);
			}elseif(is_object($mixData)){
				if(!method_exists($mixData, '__toJson')){
					throw new Exception("Objects passed in to function '" . __FUNCTION__ . "' must have a '__toJson' method");	
				}
				$arrData[$intIndex] = $mixData->__toJson(0,0, true);
					
			}
		}
		return $arrData;
	}
    public function RenderAsXML(){
    	
        //TODO: User skin tpl for this
        $strReturn = '<?xml version="1.0" encoding="UTF-8"?>';
        $strReturn .= "<response>\n";
        //parse headers
        $strReturn .= "<head>\n";
        foreach($this->arrHeaders as $objHeader){
            $strReturn .= $objHeader->__toXml();
        }
        $strReturn .= "</head>\n";
       
        //parse body
        $strReturn .= "<body>\n";
        if(is_string($this->mixBody)){
        	$strReturn .= $this->mixBody;
        }elseif(is_array($this->mixBody)){
        	foreach($this->mixBody as $strBody){
        		if(is_string($strBody)){
		        	$strReturn .= $strBody;
		        }elseif(method_exists($strBody, '__toXml')){
        			$strReturn .= $strBody->__toXml();
		        }
        	}
        }
        $strReturn .= "</body>\n";
        $strReturn .= "</response>";
        return $strReturn;
    }
}
?>
