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
					'name'=>'account_id',
					'value'=>'$data->account_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
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
					'name'=>'event',
					'value'=>'$data->event',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'start_time',
					'value'=>'$data->start_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
                        array(
					'name'=>'end_time',
					'value'=>'$data->end_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
                        array(
					'name'=>'response_time',
					'value'=>'$data->response_time',
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