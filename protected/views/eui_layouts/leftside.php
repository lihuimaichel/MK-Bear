<?php $this->UBeginWidget('EuiTabs'); ?>
    <div title="<?php echo Yii::t('system','Menu');?>">
        <?php $this->UBeginWidget('EuiAccordion');?>
            <div title="<?php echo Yii::t('system','Menu');?>" data-options="selected:true" style="padding:10px;">
                <ul class="navlist easyui-tree" id="navlist">
                    
                </ul>
            </div>
            <div title="<?php echo Yii::t('system','New Message');?>" style="padding:10px">
                
            </div>
        <?php $this->endWidget();?>
    </div>
    <div title="<?php echo Yii::t('system','Online Member');?>" data-options="tools:'#p-tools'" style="padding:10px">

    </div>
<?php $this->endWidget();?>
<div id="p-tools">
    <a href="javascript:void(0)" class="icon-mini-refresh" onclick="alert('refresh')"></a>
</div>