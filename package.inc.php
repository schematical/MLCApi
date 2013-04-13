<?php
define('__MLC_API__', dirname(__FILE__));
define('__MLC_API_CORE__', __MLC_API__ .'/_core');
define('__API_DIR__', '/api');
MLCApplicationBase::$arrClassFiles['MLCApiDriver'] = __MLC_API_CORE__ . '/MLCApiDriver.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiClassBase'] = __MLC_API_CORE__ . '/MLCApiClassBase.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiObjectBase'] = __MLC_API_CORE__ . '/MLCApiObjectBase.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiAction'] = __MLC_API_CORE__ . '/MLCApiAction.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiResponse'] = __MLC_API_CORE__ . '/MLCApiResponse.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiHeaderResponse'] = __MLC_API_CORE__ . '/MLCApiHeaderResponse.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiLoggerBase'] = __MLC_API_CORE__ . '/MLCApiLoggerBase.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiLogger'] = __MLC_API_CORE__ . '/MLCApiLogger.class.php';
MLCApplicationBase::$arrClassFiles['MLCApiAuthDriverBase'] = __MLC_API_CORE__ . '/MLCApiAuthDriverBase.class.php';

MLCApplicationBase::$arrClassFiles['MLCApiAuthDriver'] = __MLC_API__ . '/MLCApiAuthDriver.class.php';

require_once( __MLC_API_CORE__ . "/_enum.inc.php");
require_once( __MLC_API_CORE__ . "/_exception.inc.php");
