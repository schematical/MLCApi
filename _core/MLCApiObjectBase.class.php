<?php
abstract class MLCApiObjectBase extends MLCApiClassBase{
	public function __construct($objEntity) {
        $this->objEntity = $objEntity;
    }
	public function FinalAction($arrPostData){
		
		$objEntity = $this->GetEntity();
		
     	//$this->UpdateEntity($arrPostData, $objEntity);
		// return the XML or JASON for the object
        $objResponse = new MLCApiResponse($objEntity);
        return $objResponse;
		 
    }
	
	public function GetEntity(){
		return $this->objEntity;
	}
}
