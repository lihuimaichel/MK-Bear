<div class="pageHeader" style="border:1px #B8D0D6 solid">
    <form id="pagerForm" <?php if( $_REQUEST['target'] == 'dialog'):?>onsubmit="return dwzSearch(this, 'dialog');"<?php else:?>onsubmit="return divSearch(this, '<?php echo $_REQUEST['target'];?>');"<?php endif;?> action="<?php echo Yii::app()->createUrl($this->route);?>" method="post"> 
        <?php echo CHtml::hiddenField('target', @$_REQUEST['target'])?>
        <?php echo CHtml::hiddenField('platform', @$_REQUEST['platform'])?>
        <?php $this->renderPartial('application.components.views._searchHidden', array( 'pages' => $pages)); ?>
        <div class="searchBar">
            <table class="searchContent">
                <tr>                                    
                    <td>                      
                        <?php echo Yii::t('logs', 'Keywords')?>：<?php echo CHtml::textField('keywords', @$_REQUEST['keywords'])?>
                    </td>
                    <td>
                        <?php echo Yii::t('logs', 'Log Time')?>：<?php echo CHtml::textField('log_time[0]', @$_REQUEST['log_time'][0],array('class'=>'date','datefmt'=>'yyyy-MM-dd HH:mm:ss'))?>
                        - <?php echo CHtml::textField('log_time[1]', @$_REQUEST['log_time'][1],array('class'=>'date','datefmt'=>'yyyy-MM-dd HH:mm:ss'))?>
                    </td>
                    <td>
                        <?php echo Yii::t('logs', 'Level')?>：<?php echo CHtml::dropDownList('level', @$_REQUEST['level'], VHelper::getAllLogStatusList(), array( 'empty' => Yii::t('system', 'Please Select'), 'style' => 'width:100px;'))?>
                    </td>
                    <td>
                        <div class="buttonActive">
                            <div class="buttonContent">
                                <button type="submit"><?php echo Yii::t('system', 'Search')?></button>
                            </div>                         
                        </div>                       
                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>    