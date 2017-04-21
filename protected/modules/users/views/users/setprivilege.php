<?php
/**
 * 用户批量设置权限
 * @author lihy
 * @since 2016-07-25
 */
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
?>
<script type="text/javascript">
function checkMenuPrivileges(obj){
	$(obj).parent("td").next("td").find("input[type=checkbox]").attr("checked", !!$(obj).attr("checked"));
};
</script>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'catForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, ''),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => array(Yii::app()->createUrl("/users/users/saveprivilege")), 
        'htmlOptions' => array(        
            'class' => 'pageForm',   
			'onSubmit' => "return validateCallback(this, dialogAjaxDone)"      
         )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56"> 
    	<table class="dataintable_inquire" width="98%" cellspacing="1" cellpadding="3" border="0">
		    <tbody>
		    
		    	<tr>
		        	<td>用户名</td>
		        	<td>
		        		<?php echo $userInfo['user_name'];?>
		        		<input name="uid" value="<?php echo $uid;?>" type="hidden"/>
		        	</td>
		        </tr>
		        
		    	<?php if($menuGroup):?>
					<?php foreach ($menuGroup as $key=>$menu):?>
					<tr>
						<td><?php echo $menu['name'];?> <input type="checkbox" class="checkMenuPrivileges" onclick="checkMenuPrivileges(this)" onpropertychange="checkMenuPrivileges(this)"/></td>
						<td>
						<?php foreach ($menu['submenu'] as $subkey=>$submenu):?>
							<span style="width: 160px;height:40px;line-height:24px;display:inline-block;">
					
			            		<input id="continents_<?php echo $subkey?>" type="checkbox" name="menu[<?php echo $key;?>][<?php echo $subkey;?>]" <?php if(in_array($subkey, $selectedMenu)):?> checked<?php endif;?> value='<?php echo $subkey;?>'>
								<label for="continents_<?php echo $subkey;?>" style="float: right;"><?php echo $submenu['name'];?></label>
							</span>

                            <?php User::getMenu($submenu, $subkey, $selectedMenu) ?>
						<?php endforeach;?>
						</td>
					</tr>
					<?php endforeach;?>
					<?php endif;?>
		    </tbody>
		</table>   
   	</div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                         
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button> 
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script type="text/javascript">

function divSubmitRefresh(form) {
    var $form = $(form);
    var resources = [];
    $.ajax({
        type: form.method || 'POST',
        url: $form.attr("action"),
        data: {
        	from_user_id:$('#from_user_id').val(),
        	to_user_id:$('#to_user_id').val(),
			copyAuth:1
        },
        dataType: "json",
        cache: false,
        success: function(json) {
            dialogAjaxDone(json);
        },
        error: DWZ.ajaxError
    });
    return false;
}
</script>