<?php $data = array( 'ebay' => 'Ebay Platform', 'amazon' => 'Amazon Platform', 'aliexpress' => 'Aliexpress Platform');?>
<div id="siderBarMenubox" class="siderBarMenubox">
    <ul>
        <li>  
            <?php echo CHtml::link(Yii::t('logs', 'Api Log'), 'javascript:void(0);');?>
            <div class="submenu">
                <?php foreach ($data as $key => $val):?>
                <?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('logs', $val), '/logs/ulog/list/target/apilogBox/platform/'.$key, array( 'target' => 'ajax', 'rel' => 'apilogBox'));?>
                <?php endforeach;?> 
            </div>               
        </li>
        <li>            
            <?php echo CHtml::link(Yii::t('logs', 'Operation Log'), '/logs/ulog/list/target/apilogBox/platform/operation', array( 'target' => 'ajax', 'rel' => 'apilogBox'));?>
        </li>
        <li>
        	<?php echo CHtml::link(Yii::t('logs', 'File action Log'), '/systems/fileactionlog/list/target/apilogBox', array( 'target' => 'ajax', 'rel' => 'apilogBox'));?>   
        </li>
        <li>  
            <?php echo CHtml::link(Yii::t('logs', 'Profile Log'), 'javascript:void(0);');?>
            <div class="submenu">               
                <?php echo CHtml::link('&nbsp;&nbsp;&nbsp;&nbsp;'.Yii::t('logs', 'Timing Log'), '/logs/profilelog/list/target/apilogBox', array( 'target' => 'ajax', 'rel' => 'apilogBox'));?>               
            </div>               
        </li>
    </ul>
</div> 
