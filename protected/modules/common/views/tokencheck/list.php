<script>
    function getAccount(obj){
            var str ='<option value="">所　有</option>';

            if($(obj).val()){
                    $.post('/common/tokencheck/platformAccount',{'platform':$(obj).val()},function(data){

                            $.each(data,function(key,value){
                                    str +='<option value="'+key+'">'+value+'</option>';
                            });

                            $(obj).parent().next().find("select").html(str);	
                    },'json');
            }else{
                    $(obj).parent().next().find("select").html(str);
            }
    }
</script>
<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'wish_listing_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'name'=>'ID',
					'value'=>'$data->id',
					
					'htmlOptions'=>array(
						'style'=>'width:100px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'',
					),
					
			),
                        array(
					'name'=>'platform',
					'value'=>'$data->platform',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'account_id',
					'value'=>'$data->account_id',
					'type'=>'raw',
                                        'htmlOptions'   => array('style'=>'width:200px'),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'status',
					'value'=>'$data->status',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			
			array(
					'name'=>'time',
					'value'=>'$data->time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'message',
					'value'=>'$data->message',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:300px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
	),
	'toolBar'=>array(
			
	),
	'pager'=>array(
		
	),
	'tableOptions'=>array(
		'layoutH'	=>	150,
		'tableFormOptions'	=>	true
	)
		
));
?>