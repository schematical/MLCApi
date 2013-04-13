<?php
/* 
 * 
 */
class MLCApiException extends Exception{

    
}

class MLCApiMissingFunctionException extends Exception{
    public function __construct($strClassName, $strFunction, $code = null) {
        $strMessage = sprintf("Class '%s' function '%s' does not exist", $strClassName, $strFunction);
        parent::__construct($strMessage, $code);
    }
}
class MLCApiMissingFinalActionException extends Exception{
    public function __construct($strClassName, $code = null) {
        $strMessage = sprintf("Class '%s' does not have a final action", $strClassName);
        parent::__construct($strMessage, $code);
    }
}
?>
