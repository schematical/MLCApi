<?php
/* 
 * This will parse all the error handeling responses and so on
 */
class MLCApiResponseHeader{
    protected $strName = null;
    protected $strValue = null;
    protected $arrAttribute = array();
    public function __construct($strName, $strValue, $arrAttribute  = null) {
        $this->strName = $strName;
        $this->strValue = $strValue;
        if(!is_null($this->arrAttribute)){
            $this->arrAttribute = $arrAttribute;
        }
    }

    public function SetAttribute($strName, $strValue){
        $this->arrAttribute[$strName] = $strValue;
    }

    public function __toXml(){
        $strAttributes = '';
        foreach($this->arrAttribute as $strAttName=>$strAttValue){
            $strAttributes = sprintf(" %s='%s'");
        }
        $strReturn = sprintf("<%s%s>%s</%s>", $this->strName, $strAttributes, $this->strValue, $this->strName);
        return $strReturn;
    }
}
?>
