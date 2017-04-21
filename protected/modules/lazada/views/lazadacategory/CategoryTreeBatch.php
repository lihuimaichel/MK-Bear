<?php
/**
 * @desc 分类树视图
 * @author Gordon
 */
?>
<div class="pageContent">
    <div class="pageFormContent" layoutH="58">
        <ul class="tree expand categoryTree">
            <?php foreach($categories as $category):?>
            <li>
                <a href="javascript:void(0)" onClick="lookNextCategory(this)" categoryid="<?php echo $category['category_id']; ?>" level="1"><?php echo $category['name'];?></a>
			</li>
			<?php endforeach;?>
		</ul>
	</div>
	
	<div class="formBar LazadacategoryTree">
		<ul>
			<li class="close"><div class="button"><div class="buttonContent"><button class="close" type="button"><?php echo Yii::t('system', 'close');?></button></div></div></li>
		</ul>
	</div>
	<script>
		function lookNextCategory(self){
			$('li.category_confirm').remove();
			var parentId = $(self).attr('categoryid');
			var $parent = $(self).parent().parent();
			var level = $(self).attr('level');
			$.ajax({
        		type: 'post',
        		data: {category_id:parentId},
        		url: 'lazada/lazadacategory/categorytree',
        		success:function(result){
            		if( result.length > 0 ){
            			var node = '';
                		for(var j=0;j<level;j++){
                			node += '<div class="node"></div>';
                    	}
                    	var html = '';
                    	$.each(result, function(i,item){
                    		html += '<li>'
                    						+ '<div class="">'
                    						+ '<div class="line"></div>'
                    						+ node
                    						+ '<a onClick="lookNextCategory(this)" href="javascript:void(0)" categoryid="'+item.category_id+'" level="'+(parseInt(level)+1)+'">'+item.name+'</a>'
                    					+ '</div>'	
                    				+ '</li>';
                        });
            			$parent.append('<ul>'+html+'</ul>');
                	}else{
                		$('.LazadacategoryTree li.close').before(''
								+ '<li class="category_confirm">'
									+ '<div class="button">'	
										+ '<div class="buttonContent">'	
											+ '<button class="confirm" onClick="lazadaCategoryConfirm('+parentId+')" confirmid="'+parentId+'" type="button"><?php echo Yii::t('system', 'confirm');?></button>'
										+ '</div>'
									+ '</div>'
								+ '</li>'
                        );
                    }
                    $('.categoryTree div').removeClass('selected');
            		$(self).parent().addClass('selected');
        		},
        		dataType: 'json'
        	});
		}
		function lazadaCategoryConfirm(categoryID){
//			
                        if( categoryID > 0 ){
                            //加载分类类容
                            $.ajax({
                                    type:'post',
                                    url:'lazada/lazadacategory/GetBreadcrumbCategory',
                                    data:{category_id:categoryID},
                                    success:function(result){
                                        $('#<?php echo Yii::app()->request->getParam('callback') ?>').val(categoryID);
                                        $('#<?php echo Yii::app()->request->getParam('callback') ?>').parent().width(700);
                                        $('#<?php echo Yii::app()->request->getParam('callback') ?>').parent().find('span').remove();
                                        $('#<?php echo Yii::app()->request->getParam('callback') ?>').after('<span style="width:510px;"  id="'+categoryID+'" class="dbl mb5"><a  href="#">' +result+'</a></span>');
                                        $('.category_confirm').parents('.dialog').find('button.close').click();
                                    },
                                  dataType:'json'
                            });
                        }
		}
    </script>
</div>