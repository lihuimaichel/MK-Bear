<div id="sysconfig_siderBarMenubox" class="siderBarMenubox">
    <ul>       
        <li>  
            <?php echo CHtml::link(Yii::t('system', 'General Settings'), 'javascript:void(0);');?>
            <div class="submenu">   
                <?php if (User::isAdmin()):?>
                    <?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('system', 'Global Setting'), '/systems/sysconfig/global', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
                <?php endif;?>
                <?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('system', 'Personalized Settings'), '/systems/sysconfig/person', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            	<?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('system', 'Product Setting'), '/systems/sysconfig/product', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            </div>               
        </li>  
        <li>  
            <?php echo CHtml::link(Yii::t('system', 'Business Property Setting'), 'javascript:void(0);');?> 
            <div class="submenu">
            <?php if (User::isAdmin()):?>
                <?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('system', 'Image parameter Settings'), 'systems/imgset/index', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            <?php endif;?>
            	<?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('purchases', 'Purchase set'), '/systems/purchaseset/index', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            	<?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('logistics', 'Logistics set'), '/systems/logisticsset/index', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            	<?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('order', 'Order set'), '/systems/orderset/index', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            	<?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('logistics', 'API set'), '/systems/wmsapiset/index', array( 'target' => 'ajax', 'rel' => 'settingMenuBox'));?>
            </div>            
        </li> 
    </ul>
</div> 
