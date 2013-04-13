<?php
/**
 * 
 * This class handels some of the base comunication with the API of your chosing
 * @author Matt Lea
 *
 */
class MLCApiClientBase{
	const APP_ID = 'app_id';
	const APP_SECRET = 'app_secret';
	protected $_appId = null;
	protected $_appSecret = null;
	public function __construct($intAppId, $strAppSecret){
		$this->_appId = $intAppId;
		$this->_appSecret = $strAppSecret;
	}
	protected function _send($strUrl, $arrParams = array(), $blnPost = false){
		$arrHeaders = array(
			'Accept: application/json',
			'Content-Type: application/json',
			self::APP_ID . ':' . $this->_appId,
			self::APP_SECRET . ':' . $this->_appSecret
		);
		if(!$blnPost){
			if(strpos($strUrl, '?') === false){
				$strUrl .= '?';
			}else{
				$strUrl .= '&';
			}
			$strUrl .= http_build_query($arrParams);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($blnPost){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrParams));
		} 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeaders);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		//curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/Certificate/cacert.pem');
		
		$strReturn = curl_exec($ch);
		$curlError = curl_error($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (intval($httpCode / 100) != 2) {
			if ($httpCode == 422) {
				$strReturn = json_decode($strReturn);
				throw new Exception($strReturn->Message, $strReturn->ErrorCode);
			} else {
				throw new Exception("Error while calling API. Api returned HTTP code {$httpCode} with message \"{$strReturn}\"", $httpCode);
			}
		}
		$arrReturn = json_decode($strReturn, true);
		if(!is_array($arrReturn)){
			die($strReturn);
		}else{
			return $arrReturn;
		}
	}
	
}