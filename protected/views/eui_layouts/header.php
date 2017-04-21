<div id="header">		
    <div id="info">
        <a class="logo" href="<?php echo Yii::app()->request->getHostInfo();?>">
            <img alt="Logo" src="/images/logo.png" style="height:46px;" />
        </a>
    </div>
    <div id="navMenu">
    <?php
        $this->widget('zii.widgets.CMenu', array(
    			'id'           => 'main-menu',
    			'encodeLabel'  => false,
    			'htmlOptions'  => array('class'=>'main-menu'),
    			'items'        => Menu::model()->getNavMenu(),
		));
    ?>
    
    </div>
    <div id="user_zone">
        <a href="javascript:void(0)" class="drop_btn">
            <?php //echo CHtml::link(Yii::t('app', 'Logout'), array('/site/logout')); ?>
            <img src="/images/guest.png" style="width:25px;height:25px;" />
            <span style="margin-left:10px;"><?php echo Yii::app()->user->full_name ? Yii::app()->user->full_name : 'Guest';?></span>
            <b class="caret"></b>
        </a>
    </div>
    <div style="clear:both;"></div>
</div>
