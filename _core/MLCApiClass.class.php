<?php
abstract class MLCApiClassBase extends MLCApiClassBase{
	protected $strClassName = null;
	public function  __call($strName, $arrArguments) {
			$arrPostData = $arrArguments[0];
			
    		switch($strName){
				case('tag'):
					
					if(is_array($arrPostData)){
						return $this->CreateTag($arrPostData);
					}
					
					//Load all tags
					$arrTags = MLCTagDriver::LoadTagByEntites($this->GetEntity());
					$objResponse = new MLCApiResponse($arrTags);        			
					
					return $objResponse;
				break;
				default:
					return parent::__call($strName, $arrArguments);
				
    		}
	}
	
	public function CreateTag($arrPostData){
							
		//Add tag
		$objEntity = $this->GetEntity();
		//Load tag
		$mixTag = null;
		if(array_key_exists(MLCBaseQS::TAG_ID, $arrPostData)){
			$mixTag = $arrPostData[MLCBaseQS::TAG_ID];
		}elseif(array_key_exists(MLCBaseQS::NAME, $arrPostData)){
			$mixTag = $arrPostData[MLCBaseQS::NAME];
		}
		
		//Verify the tag is availalbe
		//TODO: Verify the tag is availalbe
		//Add tag to entity
		if(MLCApiAuthDriver::HasPerms(ApiApplicationPermissionLevelTpcd::Super)){
			$objEntity = $this->GetEntity();
			$blnCreate = true;
		}else{
			$objEntity = MLCApiAuthDriver::App();
			$blnCreate = false;
		}
		$objTag = MLCTagDriver::SafeLoadTag($mixTag, $objEntity, $blnCreate);
		//_dp($objTag);
		if(!is_null($objTag)){
			$objTagRelationship = $objEntity->AddTag($objTag);
			$objResponse = new MLCApiResponse($objTagRelationship->IdTagObject); 
			return $objResponse;
		}else{
			throw new MLCApiException("No tag with exists with name or id_tag passed in");
		}
		
	}
	public function UpdateEntity($arrData, $objEntity = null, $blnForcePerms = true){
		
		if(
			($blnForcePerms) &&
			(!MLCApiAuthDriver::HasPerms(ApiApplicationPermissionLevelTpcd::Super))
		){
        	throw new MLCApiException("Your app does not have permission to access this method");
		}
		
		if(is_null($this->strClassName)){
			return null;
		}
		
		if(is_array($arrData)){
			$arrData = MLCApiDriver::KeysToLower($arrData);
			if(is_null($objEntity)){
				$strPKeyField = strtolower(call_user_func($this->strClassName . '::GetPKeyField'));
				if(array_key_exists($strPKeyField, $arrData)){
					$objEntity = call_user_func($this->strClassName . '::Load', $arrPostData[$strPKeyField]);
				}else{
					$objEntity = new $this->strClassName();
					$objEntity->CreDate = QDateTime::Now();
				}
			}
			
			$objEntity->ParseArray($arrData);
			$objEntity->Save();
			$objResponse = new MLCApiResponse($objEntity);
			return $objResponse;
		}
	}
	public function Query(){
		$mixResults = null;
		$mixTag = MLCApiDriver::GetQueryString(MLCBaseQS::TAG);
		if(
			(!is_null($mixTag)) 
		){
			$mixResults = MLCTagDriver::LoadTaggedEntites($mixTag, $this->strClassName);
		}
		
		if(is_null($mixResults)){
			
			//Super load all for super level apps
			if(
				MLCAuthDriver::HasPerms(ApiApplicationPermissionLevelTpcd::Super)
			){
	        	$intMaxReturn = MLCApiDriver::GetQueryString(MLCApiQS::MAX_RETURN);
				if(is_null($intMaxReturn)){
					$intMaxReturn = MLCApiResponse::DEFAULT_MAX_RETURN;
				}
				$intPage = MLCApiDriver::GetQueryString(MLCApiQS::PAGE);
				if(is_null($intPage)){
					$intPage = 0;
				}
				$mixResults = call_user_func(
					$this->strClassName . '::LoadAll',
					QQ::Clause(QQ::LimitInfo($intMaxReturn, ($intPage * $intMaxReturn)))
				);
				
			}
		}
		
		
		$objResponse = new MLCApiResponse($mixResults);
	    return $objResponse;
		
    		
    }
	public function FinalAction($arrPostData){
     	if(is_array($arrPostData)){
			return $this->UpdateEntity($arrPostData);
		}
		$objResponse = $this->Query();
		return $objResponse;
	 }
}
abstract class MLCBaseQS{
	//Used for Adding a Tag
    const TAG_ID = 'tag_id';
	const NAME = 'name';
	//Used for querying
	const TAG = 'tag';
}
