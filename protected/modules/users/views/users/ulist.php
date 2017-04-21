<table class="table" width="99%" layoutH="26" style=" height: 328px;  min-height: 300px;">
    <thead>
        <tr>
            <th width="10%">
                <input type="checkbox" group="ids" class="checkboxCtrl">
            </th>
            <th width="10%"><?php echo Yii::t('users', 'NO.')?></th>
            <th width="40%"><?php echo Yii::t('users', 'Username')?></th> 
            <th width="40%"><?php echo Yii::t('system', 'Oprator')?></th>                                                                       
        </tr>
    </thead>
    <tbody target="delete" >           
        <?php foreach ($models as $key => $val):?>
        <tr target="sid_obj" rel="<?php echo $val['id'];?>">
            <td>
                <input name="ids_c0[]" value="<?php echo $val['id'];?>" type="checkbox" >
            </td>
            <td><?php echo $key+1;?></td>
            <td><?php echo $val['user_name'].'('. $val['user_full_name'] .')';?></td> 
            <td>
            	<?php 
            	echo CHtml::link('', "/systems/menu/taskTree/target/dialog/uid/".$val['id'].($role ? "/role/".$role : ''), array(
            			'class' 	=> 'btnEdit',
            			'title' 	=> Yii::t('users', 'Set Opration Auth'),
            			'target'	=> "dialog",
            			'width' 	=> '600',
            			'height' 	=> '500',
            			'rel'		=> 'taskTree',
            			'mask'		=> 1,
            	));
            	if(User::isAdmin()):
	            	echo CHtml::link('', "/users/users/copyauth/uid/".$val['id'], array(
	            			'class' 	=> 'btnAdd',
	            			'title' 	=> Yii::t('users', 'Copy Auth'),
	            			'target'	=> "dialog",
	            			'width' 	=> '600',
	            			'height' 	=> '300',
	            			'rel'		=> 'copyauth',
	            			'mask'		=> 1,
	            	));
				endif;
				?>
        </tr>
        <?php endforeach;?>         
    </tbody>
</table>  
 