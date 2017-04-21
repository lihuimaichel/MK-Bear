<div class="accordionHeader">
    <h2><span>Folder</span><?php echo Yii::t('system','Short Message');?>
    <?php 
//     echo CHtml::link(Yii::t('system', 'Refresh'), 'javascript: void(0);', array(
//     		'class' => 'btnRefresh',
//     		'style' => 'display:block;margin-left:100px;margin-top:-23px;',
//     		'title' => Yii::t('system', 'Refresh'),
//     		'onclick' => '',
//     ))
    ?>
    </h2>
</div>
<style>
	.msg_item:hover {background:#f5f5f5;}	    
</style>
<div class="accordionContent">
	<?php $msgList = UserMsg::getMsglist();?>
	<?php foreach($msgList as $msgArr): ?>
	<div class="msg_item" style="height:100px; border-bottom:1px dashed #ccc;padding:8px;">
		<div class="msg_header" style="height:20px;">
			<div class="msg_info" style="float:left;">
				<?php echo '<span style="font-weight:bold;font-size:12px;">'.($msgArr['create_user_id']==0 ? Yii::t('system', 'System Message') : MHelper::getUsername($msgArr['create_user_id'])).'</span>';?>
				<?php 
					$title = substr($msgArr['msg_title'], 0, 12);
					echo ':'.$title;
					if( $title!=$msgArr['msg_title'] ){
						echo '...';
					}
				?>
			</div>
			<div class="msg_time" style="float:right;color:#666;">
				<?php 
					if( ( time() - strtotime($msgArr['created_time']) )/3600/24 < 1 ){
						echo date('H:i', strtotime($msgArr['created_time']));
					}else{
						echo date('Y-m-d', strtotime($msgArr['created_time']));
					}
				?>
			</div>
			<div style="clear:both;"></div>
		</div>
		<div class="msg_body">
			<div class="msg_content" style="height:50px;width:190px;word-wrap:break-word;">
				<?php echo substr($msgArr['msg_content'], 0, 90); ?>
			</div>
			<div class="msg_buttons" style="float:right;">
				<select onChange="$(this).parent().find('a.'+this.value).click()">
					<option value=""><?php echo Yii::t('system','Please Select');?></option>
					<option value="msg_view"><?php echo Yii::t('system','View Message');?></option>	
					<option value="msg_mark" <?php echo $msgArr['status']==2 ? 'disabled="disabled"' : ''; ?>><?php echo Yii::t('system','Mark Message');?></option>
					<option value="msg_delete"><?php echo Yii::t('system','Delete Message');?></option>	
					<option value="msg_reply"><?php echo Yii::t('system','Reply Message');?></option>		
				</select>
				<a href="/systems/msg/detail/id/<?php echo $msgArr['id'];?>" target="dialog" class="msg_view" style="display:none;"><?php echo Yii::t('system','View Message');?></a>
				<a href="/systems/msg/flag/ids/<?php echo $msgArr['id'];?>" target="ajaxTodo" class="msg_mark" style="display:none;"><?php echo Yii::t('system','Mark Message');?></a>
				<a href="/systems/msg/delete/ids/<?php echo $msgArr['id'];?>" target="ajaxTodo" class="msg_delete" style="display:none;"><?php echo Yii::t('system','Delete Message');?></a>
				<a href="/systems/msg/reply/id/<?php echo $msgArr['id'];?>" target="dialog" class="msg_reply" style="display:none;" rel="userMsg"><?php echo Yii::t('system','Reply Message');?></a>
			</div>
			<div style="clear:both;"></div>
		</div>
	</div>     
	<?php endforeach;?> 
	<div style="text-align:center;margin-top:10px;">
		<button onClick="$(this).next().click()"><?php echo Yii::t('system','View All');?></button>
		<a href="/systems/msg/list" style="display:none;" target="navTab"><?php echo Yii::t('system','Short Message'); ?></a>
	</div> 
</div>
