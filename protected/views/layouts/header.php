<div id="header">
    <div class="headerNav">
        <a class="logo" href="<?php echo Yii::app()->request->getHostInfo();?>">标志</a>
        <ul class="nav">
        	<span style="float:left;"></span>
        	<li style="font-weight:bold;font-size:16px;"><a href="#" >欢迎您: <?php echo Yii::app()->user->full_name;?></a></li>		
        	<!-- 
        	<li><a href="<?php echo Yii::app()->createUrl("/users/users/change", array("id" =>Yii::app()->user->id))?>" target="dialog">修改密码</a></li>
        	 -->			
            
            <li><a href="#" target="_blank">桌面</a></li>
            <li><a href="javascript:void(0)" target="dialog" width="600">控制面板</a></li>
            <li><a href="javascript:void(0)" target="_blank">内部论坛</a></li>					
            <li><?php echo CHtml::link(Yii::t('app', 'Logout'), array('/site/logout')); ?></li>
        </ul>
        <ul class="themeList" id="themeList">
            <li theme="default"><div class="selected">蓝色</div></li>
            <li theme="green"><div>绿色</div></li>
            <!--<li theme="red"><div>红色</div></li>-->
            <li theme="purple"><div>紫色</div></li>
            <li theme="silver"><div>银色</div></li>
            <li theme="azure"><div>天蓝</div></li>
        </ul>
    </div>		
    <div id="navMenu">
        <?php
        $this->widget('zii.widgets.CMenu', array(
					'id'=>'main-menu',
					'encodeLabel'=>false,
					'htmlOptions'=>array('class'=>'main-menu'),
					'items'=>Menu::model()->getNavMenu()
				));
        ?>             
    </div>
</div>
