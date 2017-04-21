<?php 
    Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl.'/css/custom.css');
    Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl.'/js/custom_eui/ueb.system.js', CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile(Yii::app()->request->baseUrl.'/js/custom_eui/preload.js', CClientScript::POS_HEAD);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" " http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>
        <?php echo Yii::t('app', Yii::app()->name)?>
    </title>
    <script type="text/javascript">
	</script>
</head>
<body class="easyui-layout">
    <?php 
        //渲染顶部板块
        $this->UBeginWidget('EuiRegion', array(
                'id'        => 'layout_top',
                'region'    => 'north',
                'style'     => 'height:52px;',
//                 'split'     => true,
        ));
        echo $this->renderPartial('//eui_layouts/header');
        $this->endWidget();
        
        //渲染左侧栏板块
        $this->UBeginWidget('EuiRegion', array(
                'id'        => 'layout_left',
                'region'    => 'west',
//                 'split'     => true,
                'style'     => 'width:250px;',
                'title'     => 'Main'
        ));
        echo $this->renderPartial('//eui_layouts/leftside');
        $this->endWidget();
        
        //渲染顶部板块
        $this->UBeginWidget('EuiRegion', array(
                'id'        => 'layout_center',
                'region'    => 'center',
                'split'     => true,
        ));
        echo $content;
        $this->endWidget();
        
        //渲染底部板块
        $this->UBeginWidget('EuiRegion', array(
                'id'        => 'layout_bottom',
                'region'    => 'south',
                'style'     => 'height:30px;line-height:30px;',
        ));
        echo Yii::app()->params['copyrightInfo'];;
        $this->endWidget();
    ?>
    <ul style="display:none;width:padding:5px;margin:0;" class="show_drop">
        <li><a href="javascript:void(0)"><?php echo Yii::t('system', 'Modify Password');?></a></li>
        <li style="height:1px;background-color:#e5e5e5;margin:9px 0;"></li>
        <li><a href="/site/logout"><?php echo Yii::t('app', 'Logout');?></a></li>
    </ul>
    <div id="dialog"></div>
</body>
</html>