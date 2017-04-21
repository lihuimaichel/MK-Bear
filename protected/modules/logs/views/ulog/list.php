<?php $this->renderPartial('_search', array( 'pages' => $pages)); ?>
<div class="pageContent" style="border-left:1px #B8D0D6 solid;border-right:1px #B8D0D6 solid">
    <div class="panelBar">
        <ul class="toolBar">
            <li></li>          
        </ul>
    </div>
    <table class="table" width="99%"  <?php if ( $_REQUEST['target'] == 'dialog'):?>targetType="dialog" layoutH="110"<?php else:?>rel="<?php echo $_REQUEST['target'];?>" layoutH="120"<?php endif;?> >
        <thead>
            <tr>               
                <th width="5%"><?php echo Yii::t('system', 'NO.')?></th>
                <th width="15%" orderField="tag"><?php echo Yii::t('logs', 'Type')?></th>                              
                <th width="15%" orderField="keywords">
                    <?php echo Yii::t('logs', 'Keywords')?>
                </th>                              
                <th style="width: 600px;"><?php echo Yii::t('logs', 'Message')?></th>
                <th width="15%"><?php echo Yii::t('logs', 'Request Url')?></th>
                <th width="15%"><?php echo Yii::t('logs', 'Level')?></th>
                <th width="10%"><?php echo Yii::t('logs', 'User Id')?></th>
                <th width="10%" orderField="log_time">
                	<?php echo Yii::t('logs', 'Log Time')?>
                </th>                                            
            </tr>
        </thead>
        <tbody>           
            <?php foreach ($models as $key => $val):?>
            <tr target="sid_obj" rel="<?php echo $val['id'];?>">             
                <td><?php echo $key+1;?></td>
                <td><?php echo $val['tag'];?></td>
                <td><?php echo $val['keywords'];?></td>                          
                <td style="width: 600px;word-wrap: break-word;"><?php echo UebModel::getRecordLog($val['message']);?></td>
                <td><?php echo $val['request_url'];?></td>
                <td><?php VHelper::getLogStatusLable($val['level']);?></td>
                <td><?php echo !empty($val['user_id']) ? MHelper::getUsername($val['user_id']) : $val['user_id'];?></td>
                <td><?php echo $val['log_time'];?></td>              
            </tr>
            <?php endforeach;?>         
        </tbody>
    </table>
    <?php $this->renderPartial('application.components.views._pageFooter', array( 'target' => $_REQUEST['target'], 'pages' => $pages)); ?>   
</div>