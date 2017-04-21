<script type="text/javascript">
	$(function(){
		changeCategory(0,0);	
	});

	function changeCategory(parentId, level){
		$("div[id^=catTree_]").each(function(){
			var levelNum = $(this).attr('id').replace(/catTree_/, '');
			if( levelNum > level){
				$(this).remove();
			}
		});
		$.ajax({
			type : 'post',
			url : '/products/productcat/getcattree',
			data : {parent_id : parentId},
			success : function(data){
				buildCatTree(data);
				$("a[id^=catTreeItem_]").catCmDialog();
			},
			'dataType' : 'json'
		});	
	}

	
	function buildCatTree(catTree){
		if( catTree && catTree.cat.length > 0 && catTree.level>=0 ){
			var parentHtml = '';
			if( catTree.parent ){
				parentHtml += '<a id="catTreeParent_'+catTree.parent.id+'" >'+(catTree.parent.category_cn_name ? catTree.parent.category_cn_name : catTree.parent.category_en_name)+'</a>';
			}else{
				parentHtml += '<a id="catTreeParent_0" ><?php echo Yii::t('system', 'Root')?></a>';
			}
			var height = $('.navTab-panel').height() - 50;
			var html = ''
						+ '<div layoutH="50" style="float:left; display:block; overflow:auto; width:250px; border:solid 1px #CCC; line-height:21px; background:#fff; height:'+height+'px" id="catTree_'+catTree.level+'">'
							+ '<ul class="tree treeFolder" rel="catTreeCm" >'
								+ '<li>'
									+'<div class=""><div class="end_collapsable"></div><div class="folder_collapsable"></div>'
										+ parentHtml
									+ '</div>'
									+ '<ul>';
			$.each(catTree.cat,function(index,item){
				html += ''
						+ '<li>'
							+ '<div class=""><div class="indent"></div><div class="node"></div><div class="file"></div>'
								+ '<a href="javascript:void(0)" id="catTreeItem_'+item.id+'" onClick="changeCategory('+item.id+','+catTree.level+')">'+(item.category_cn_name ? item.category_cn_name : item.category_en_name)+'</a>'
							+ '</div>'
						+ '</li>';
			});
			html += 				'</ul>'		
								+ '</li>'
							+ '</ul>'
						+ '</div>';
			
			$('.dialogContent .pageContent').append(html);
		}else if(catTree.cat.length==0){
			<?php if(Yii::app()->request->getParam('target')=='dialog'):?>
				//$('.catList .toolBar li').
				$('#<?php echo Yii::app()->request->getParam('callback') ?>').val(catTree.parent.id);
				$('#<?php echo Yii::app()->request->getParam('callback') ?>').parent().find('span').remove();
				$('#<?php echo Yii::app()->request->getParam('callback') ?>').after('<span id="'+catTree.parent.id+'" class="dbl mb5"><a href="javascript:void(0)" class="toggle_btn" id="toggle_btn_'+catTree.parent.id+'">'+(catTree.parent.category_cn_name ? catTree.parent.category_cn_name : catTree.parent.category_en_name)+'</a></span>');
				$('#catTree_0').parents('.dialog').find('a.close').click();
			<?php endif;?>
		}else{
			alertMsg.error('Can Not Get Category Information,Please Try Again!');
		}
	}
</script>
<div class="pageContent " >
	<div class="panelBar catList">
	    <ul class="toolBar">
	        <li></li>       
	    </ul>
	</div>
</div>