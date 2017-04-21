<?php
error_reporting(E_ERROR & ~E_WARNING);
header("Content-type:text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
require dirname(__FILE__).'/protected/config/env.php';
$envParm = Env::DEVELOPMENT;
$env = new Env($envParm);
define('YII_ENV', $envParm);
define('DS', DIRECTORY_SEPARATOR);
define('CONF_PATH', dirname(__FILE__) . DS . 'protected' .DS .'config'.DS);
define('UPLOAD_DIR', dirname(__FILE__)  .DS .'uploads'.DS);

// change the following paths if necessary
$yii=dirname(__FILE__).'/framework/yii.php';
// $config=dirname(__FILE__).'/protected/config/main.php';
$config = $env->getConfig();

// remove the following line when in production mode
// defined('YII_DEBUG') or define('YII_DEBUG', $env->getDebug());
// defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', $env->getTraceLevel());

require_once($yii);
Yii::createWebApplication($config)->run();