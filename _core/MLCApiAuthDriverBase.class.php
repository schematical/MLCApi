<?php
/**
 * This class handels authentication for the API
 * @author Matt Lea
 *
 */
//require_once(__MLC_CORE__ . '/util/oauth/OAuth.php');
abstract class MLCApiAuthDriverBase{
	const HEADER_ID_APP = 'app_id';
	const HEADER_API_KEY = 'app_secret';
	const HEADER_ACCESS_TOKEN = 'access_token';
	protected static $objApp = null;
	protected static $objAccessToken = null;
	
	public static function Authenticate($blnForceAuthenticate = false, $arrOverrideHeaders = null){
		if(!is_null(self::$objApp)){
			return self::$objApp;
		}
		/*foreach ($arrHeaders as $strName => $strValue) {
		    error_log("API INCOMING HEADERS: " . $strName . " - " . $strValue);
		}*/
		$intIdApplication = null;
		$strApiKey =  null;
		$objApplication = null;
		if(!is_null($arrOverrideHeaders)){
			$arrHeaders = $arrOverrideHeaders;
		}else{
			$arrHeaders = apache_request_headers();
		}
		
		$intAppId = MLCApiDriver::GetQueryString(self::HEADER_ID_APP);
		if(key_exists(self::HEADER_ID_APP, $arrHeaders)){
			$intIdApplication  = $arrHeaders[self::HEADER_ID_APP];
		}elseif(!is_null($intAppId)){
			$intIdApplication  = $intAppId;
		}else{
			if($blnForceAuthenticate){		
				throw new MLCApiException("In sufficient credentials submitted");
			}
		}
		$strAppSecret = MLCApiDriver::GetQueryString(self::HEADER_API_KEY);
		if(key_exists(self::HEADER_API_KEY, $arrHeaders)){
			$strAppSecret  = $arrHeaders[self::HEADER_API_KEY];
		}
		
		
		$strAccessToken = MLCApiDriver::GetQueryString(self::HEADER_ACCESS_TOKEN);
		if(key_exists(self::HEADER_ACCESS_TOKEN, $arrHeaders)){
			$strAccessToken  = $arrHeaders[self::HEADER_ACCESS_TOKEN];
		}
		
		if(
			(is_null($strAppSecret)) &&
			(is_null($strAccessToken)) &&
			($blnForceAuthenticate)
		){
			throw new MLCApiException("In sufficient credentials submitted");
		}
		if(!is_null($strAppSecret)){
			
			$objApplication = ApiApplication::QuerySingle(
				QQ::AndCondition(
					QQ::Equal(QQN::ApiApplication()->IdApplication, $intIdApplication),
					QQ::Equal(QQN::ApiApplication()->ConsumerSecret, $strAppSecret)
				)
			);
		}
		if(!is_null($strAccessToken)){
			
			$objToken= ApiRequestToken::QuerySingle(
				QQ::AndCondition(
					QQ::Equal(QQN::ApiRequestToken()->IdApplication, $intIdApplication),
					QQ::Equal(QQN::ApiRequestToken()->OauthToken, $strAccessToken),
					QQ::LessOrEqual(QQN::ApiRequestToken()->CreDate, QDateTime::Now()),
					QQ::GreaterOrEqual(QQN::ApiRequestToken()->ExpDate, QDateTime::Now())					
				)
			);
			if(!is_null($objToken)){
				self::$objAccessToken = $objToken;
				$objApplication = $objToken->IdApplicationObject;
				
				//Use the callback url
				if($objApplication->TokenSafeData){
					MLCApiDriver::SetCallbackUrl($objApplication->CallbackUrl);
				}
			}
			
		}
		
		
		MLCApiDriver::SetAdditionalCallbackHeaderData(MLCApiAuthDriver::HEADER_ID_APP, MLCApiAuthDriver::IdApplication());
		$objAccessToken = MLCApiAuthDriver::AccessToken();
		if(!is_null($objAccessToken)){
			MLCApiDriver::SetAdditionalCallbackHeaderData(MLCApiAuthDriver::HEADER_ACCESS_TOKEN, $objAccessToken->OauthToken);
		}else{
			//Dont think we need to post back the SECRET
			//$objResponse->Head(MLCApiAuthDriver::HEADER_ID_APP);
		}
		
		
		if(($blnForceAuthenticate) && (is_null($objApplication))){		
			throw new MLCApiException("Invalid App Id and Secret Key passed in");
		}
		self::$objApp = $objApplication;
		
	}
	public static function App(){
		return self::$objApp;
	}
	public static function Developer(){
		if(is_null(self::App())){
			return null;
		}
		return self::App()->IdDeveloperObject;
	}
	public static function Account(){
		if(is_null(self::Developer())){
			return null;
		}
		return self::Developer()->IdAccountObject;
	}
	public static function AccessToken(){
		return self::$objAccessToken;
	}
	public static function IdApplication(){
		if(!is_null(self::$objApp)){
			return self::$objApp->IdApplication;
		}else{
			return null;
		}
	}
	
	public static function IdDeveloper(){		
		if(!is_null(self::App())){
			return self::App()->IdApp;
		}else{
			return null;
		}
	}
	public static function SecretKey(){
		if(!is_null(self::App())){
			return self::App()->ConsumerSecret;
		}else{
			return null;
		}
	}
	
	public static function DecodeRequest($strRequest){
		$strAPIKey = self::SecretKey();
		if(!is_null($strAPIKey)){
			$strJSON = MFBApplication::Decrypt($strRequest, self::SecretKey());
			$arrRequest = json_decode($strJSON);
			if(is_null($arrRequest)){
				throw new Exception("Invalid JSON response please check your APIKey and Request format");
			}
		}else{
			$strJSON = $strRequest;
			$arrRequest = json_decode($strJSON);
			if(is_null($arrRequest)){
				throw new Exception("There was an error decoding the request");
			}
		}
		return $arrRequest;
	}
	
	
	
	
	
	
	public static function GenerateKey( $unique = false ){
		$key = md5(uniqid(rand(), true));
		if ($unique)
		{
			list($usec,$sec) = explode(' ',microtime());
			$key .= dechex($usec).dechex($sec);
		}
		return $key;
	}
	public static function RegisterNewApp($strName,  $mixDeveloper = null){
		$objApp =  new ApiApplication();
		$objApp->Name = $strName;
		$objApp->CreDate = QDateTime::Now();
		if(!is_null($mixDeveloper)){
			$objApp->IdDeveloper = Developer::Parse($mixDeveloper)->IdDeveloper;
		}else{
			$objDeveloper = MLCAuthDriver::Developer();
			if(!is_null($objDeveloper)){
				$objApp->IdDeveloper = $objDeveloper->IdDeveloper;
			}else{
				throw new Exception("Insufficent Developer information passed in");
			}
		}
		$objApp->ConsumerKey = self::GenerateKey(true);
		$objApp->ConsumerSecret = self::GenerateKey();
		$objApp->IdApplicationStatusTypeCd = ApiApplicationStatusTpcd::active;
		$objApp->LocGate = true;
		$objApp->TokenSafeData = true;
		$objApp->AllowCustomImg = true;
		$objApp->Save();
		return $objApp;
	}
	public static function HasPerms($intIdAppPermissionsType){
		return (self::App()->IdApplicationPermissionLevel == $intIdAppPermissionsType);		
	}
	/*
	 * This function ensures a url is insite of a specific domain
	 * 
	 */
	public static function ValidateDomainUrl($strUrl, $strDomain = null){
		if(is_null($strDomain)){
			$objApp = self::App();
			if(!is_null($objApp)){
				$strDomain = $objApp->Domain;
			}else{
				throw new Exception("Unable to get App Domain");
			}
		}
		$strCompDomain = self::GetUrlDomain($strUrl);
		
		return ($strDomain == $strCompDomain);
	}
	public static function GetUrlDomain($strUrl){
		$arrParts = parse_url($strUrl);
		if(array_key_exists('host', $arrParts)){
			return $arrParts['host']; 
		}
		return null;
	}
}