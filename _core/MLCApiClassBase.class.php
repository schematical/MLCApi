<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
abstract class MLCApiClassBase{

    public function  __call($strName, $arrArguments) {
        throw new MLCApiMissingFunctionException(get_class($this), $strName);
    }
    public function FinalAction($arrPostData){
       throw new  MLCApiMissingFinalActionException(get_class($this));
    }
  
  	
    
}
?>
