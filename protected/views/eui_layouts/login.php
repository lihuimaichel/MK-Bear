<?php 
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/login.css', 'screen, projection');
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/loginForm.css', 'screen, projection');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo Yii::t('app', Yii::app()->name);?></title>
</head>
<body>
<!-- main Start-->
<div class="login-main">
    <div class="layout-login-big">
        <div class="layout-login">                
            <?php echo $content;?>
        </div>
        <div class="login-footer">
            <?php echo Yii::app()->params['copyrightInfo'];?>	
        </div>
    </div>
</div>
<!-- main End-->

<!-- bg Start-->
<div class="login-bg">
    <img src="<?php echo $baseUrl;?>/themes/default/images/login/bg.jpg" width="100%" height="100%" />
</div>
<!-- bg End-->
</body>
</html>
