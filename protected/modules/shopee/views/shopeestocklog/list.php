<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'shopee_listing_widget',
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
					'name'=>'sku',
					'value'=>'$data->sku',
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:180px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'online_sku',
					'value'=>'$data->online_sku',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'listing_id',
					'value'=>'$data->listing_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:300px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'account_id',
					'value'=>'$data->account_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:120px'
					),
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
					'name'=>'update_action',
					'value'=>'$data->update_action',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:150px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'created_at',
					'value'=>'$data->created_at',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'is_restored',
					'value'=>'$data->is_restored',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'msg',
					'value'=>'$data->msg',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:250px'
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