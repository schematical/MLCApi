<?php
/* 
 * This class will manage all aspects of dealing with api calls
 */
abstract class MLCApiDriver{
    const QUERY_DELIMITER = "/";
    const HOME_CLASS_NAME = "MLCApiHome";
    public static $strHomeClassOverride = null;
    public static $objApiHomeClass = null;
    public static $objActiveApiClass = null;
    public static $blnActionExicuted = false;
    public static $arrApiObject = array();
    public static $arrQueryString = array();
    public static $strResponseType = null;
	public static $blnIsPost = false;
	public static $arrPostData = array();
	public static $strCallbackUrl = null;
	public static $strQueryUrl = null;
   	public static $arrAdditionalCallbackHeaderData = array();
	public static function SetAdditionalCallbackHeaderData($strKey, $mixVal = null){
		if(is_null($mixVal)){
			if(array_key_exists($strKey, self::$arrAdditionalCallbackHeaderData)){
				return self::$arrAdditionalCallbackHeaderData[$strKey];
			}
			return null;
		}else{
			return self::$arrAdditionalCallbackHeaderData[$strKey] = $mixVal;
		}
	}
    public static function Run($strHomeClassOverride = null){
        
     
        //Set override if passed in
        self::$strHomeClassOverride = $strHomeClassOverride;
        //find the page root
        //cut of ever is after the page root      
 
		self::PopulateQS();
     	switch(self::GetQueryString(MLCApiQS::RESPONSE_TYPE)){
			case(MLCApiResponse::RESPONSE_TYPE_JSON):
			default:
    			self::$strResponseType = MLCApiResponse::RESPONSE_TYPE_JSON;
    		break;
    	}
      	MLCApiLogger::LogStart();
		
        //explode whats left
       
        $arrObjectName = explode(self::QUERY_DELIMITER, substr(self::$strQueryUrl, 1));
        try{
        	self::$objApiHomeClass = self::GetApiHomeClass();

        	self::$objActiveApiClass = self::$objApiHomeClass;
			

			foreach($arrObjectName as $strObjectString){
				//error_log(get_class(self::$objActiveApiClass) . '->' .$strObjectString);
	        	self::$objActiveApiClass = self::$objActiveApiClass->$strObjectString(self::$arrPostData);
			}
			
			if((self::$objActiveApiClass instanceof MLCApiClassBase)){
				//error_log(get_class(self::$objActiveApiClass) . '->FinalAction');
	            self::$objActiveApiClass = self::$objActiveApiClass->FinalAction(self::$arrPostData);
	        }
			if((self::$objActiveApiClass instanceof MLCApiResponse)){
				self::RenderResponse(self::$objActiveApiClass);
			}
	        header('HTTP/1.1: 200 OK');     
        }catch(MLCApiException $objException){
 
        	if(!array_key_exists('debug', $_GET)){
	        	//header('HTTP/1.1: 422 OK');
	        	$arrHead = array();
	        	$arrHead['error'] = $objException->getMessage();
	        	$objResponse = new MLCApiResponse(array(), $arrHead);
				self::RenderResponse($objResponse);		
			}else{
				throw $objException;
			}
        }


    }
    public static function MakeActionExicuted(){
        self::$blnActionExicuted = true;
    }
    public static function GetApiHomeClass(){
        if(!is_null(self::$strHomeClassOverride)){
            $strClassName = self::$strHomeClassOverride;
        }else{
            $strClassName = self::HOME_CLASS_NAME;
        }
        $objApiHomeClass = new $strClassName();
        return $objApiHomeClass;
    }
    public static function GetQueryString($strName){        
        if(key_exists(strtoupper($strName), self::$arrQueryString)){
            return self::$arrQueryString[strtoupper($strName)];
        }elseif(key_exists(strtolower($strName),  self::$arrQueryString)){
            return  self::$arrQueryString[strtolower($strName)];
        }else{
            return null;
        }
    }
	public static function LogStart(){
		
	}
	public static function LogEnd($strResponse){
		
	}
	public static function PopulateQS(){
		//_dp($_SERVER);
		if(array_key_exists('CONTENT_TYPE', $_SERVER)){
			$strContentType = $_SERVER['CONTENT_TYPE'];
		}else{
			$strContentType = null;
		}
		if(strpos($strContentType, 'application/json') !== false){
		        $strContentType = 'application/json';
		}
				
		switch($strContentType){
			
			case('application/json'):
				$mixBody = @file_get_contents('php://input');
				self::$arrPostData = json_decode($mixBody, true);
				
			break;
			case('application/x-www-form-urlencoded'):
			default:
				self::$arrPostData = $_POST;
			break;
		}
		if(count(self::$arrPostData) > 0){
			self::$blnIsPost = true;
		}else{
			self::$arrPostData = null;
		}
		
		$strQueryUrl = $_SERVER[MLCServer::REQUEST_URI];
        //cut off everythign after '?'
        $strPos = strpos($strQueryUrl, '?');

        if($strPos !== false){
            $strQueryParams = substr($strQueryUrl, $strPos + 1);
            $strQueryUrl = substr($strQueryUrl, 0, $strPos);
            //create make shift get
            $arrQueryParams = explode("&", $strQueryParams);           
            foreach($arrQueryParams as $strQueryParam){
                $arrParts = explode("=", $strQueryParam);
                if(count($arrParts) == 2){
                    self::$arrQueryString[$arrParts[0]] = $arrParts[1];
                }else{ 
                    //ignore
                }
            }
        }
		//Override with post data
		if(!is_null(self::$arrPostData)){
			foreach(self::$arrPostData as $strKey => $mixValue){
				self::$arrQueryString[$strKey] = $mixValue;
			}
		}
		self::$strQueryUrl = str_replace(__API_DIR__, '', $strQueryUrl);

		return $strQueryUrl;
        
		
	}
	public static function IsPost(){
		return self::$blnIsPost;
	}
	public static function GetPostData(){
		return self::$arrPostData;
	}
	public static function PostToCallback($objResponse, $strUrl = null, $blnUsePayload = false){
		if(is_null($strUrl)){
			if(is_null(self::$strCallbackUrl)){
				throw new Exception("Invalid Url passed in and no static callback url set");	
			}
			$strUrl = self::$strCallbackUrl;
		}
		$arrHeaders = array(
			'Accept: application/json',
			'Content-Type: application/json'
		);
		$objResponse->Head('end_point', self::$strQueryUrl);
		foreach(self::$arrAdditionalCallbackHeaderData as $strKey => $mixVal){
			$objResponse->Head($strKey, $mixVal);
		}
		$arrParams = json_decode($objResponse->RenderResponse(), true);
		$strPostData = json_encode($arrParams);

		if($blnUsePayload){
			$strPostData = array('payload'=>$strPostData);
		}
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $strPostData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
		//curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		//curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/Certificate/cacert.pem');
		
		$return = curl_exec($ch);
		$curlError = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (intval($httpCode / 100) != 2) {
			if ($httpCode == 422) {
				$return = json_decode($return);
				throw new Exception($return->Message, $return->ErrorCode);
			} else {
				throw new Exception("Error while calling API. Api returned HTTP code {$httpCode} with message \"{$return}\"", $httpCode);
			}
		}
		return $return;
	}
	public static function SetCallbackUrl($strUrl){
		self::$strCallbackUrl = $strUrl;
	}
	public static function RenderResponse($objResponse){
		$strResponse = $objResponse->RenderResponse();
		if(is_null(self::$strCallbackUrl)){
    		echo $strResponse;
		}else{
			$strReturn = null;
			try{
				$strReturn = self::PostToCallback($objResponse, self::$strCallbackUrl);
			}catch(excpetion $e){
				//Do nothing, its on the clients end
			}
			$strJsonReturn = json_decode($strReturn, true);
			if(json_last_error() == JSON_ERROR_NONE){
				$strReturn = $strJsonReturn;
			}
			$objResponse = new MLCApiResponse($strReturn);
			echo $objResponse->RenderResponse();	
		}
		MLCApiLogger::LogEnd($strResponse);		
	}
	
	
	public static function ParseData($mixJson){
		
		if(is_string($mixJson)){
			$arrJson = json_decode($mixJson, true);
		}else{
			$arrJson = $mixJson;
		}
		if(!is_array($arrJson)){
			//die($mixJson);
			//return $mixJson;
		}
		if(!array_key_exists('_ClassName', $arrJson)){
			$arrReturn = array();
			foreach($arrJson as $intIndex => $mixChildren){
				if(is_array($mixChildren)){
					$arrReturn[$intIndex] = self::ParseData($mixChildren);
				}else{
					$arrReturn[$intIndex] = $mixChildren;
				}
			}
			return $arrReturn;
		}else{
			
			$strClass = $arrJson['_ClassName'];
			$strField = ucfirst(call_user_func($strClass . '::GetPKeyField'));
			
			$objEntity = call_user_func($strClass . '::Parse', $arrJson[$strField]);
			$objEntity->ParseArray($arrJson);
			return $objEntity;
		}
		
	}
	public static function KeysToLower($arrData){
		$arrReturn = array();
		foreach($arrData as $strKey => $mixData){
			$arrReturn[strtolower($strKey)] = $mixData;
		}
		return $arrReturn;
	}
	
}
?>