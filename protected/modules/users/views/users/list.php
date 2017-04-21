<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false; 
$row = 0;
$config = array(
    'id' => 'user-grid',  
    'dataProvider' => $model->search($departmentId),
    'filter' => $model,
    
    'columns' => array(
       array(
            'class'              => 'CCheckBoxColumn',  
            'name'               => 'orgId',
            'selectableRows'     => 2,
            'value'              => '$data->id',    
            'htmlOptions' => array('style'=>'width:30px;'),           
        ), 
       array(
            'name' => 'id',
            'value' => '$row+1',  
            'htmlOptions' => array('style'=>'width:60px;'),   
        ),       
        array(
            'name' => 'user_name',
            'value' => '$data->user_name',
            'htmlOptions' => array('style'=>'width:100px;'),
        ),
            array(
                    'name' => 'en_name',
                    'value' => '$data->en_name',
                    'htmlOptions' => array('style'=>'width:130px;'),
            ),
            array(
                    'name' => 'user_full_name',
                    'value' => '$data->user_full_name',
                    'htmlOptions' => array('style'=>'width:130px;'),
            ),
            
            // array(
            //         'name' => 'user_email',
            //         'value' => '$data->user_email',
            // ),
            // array(
            //         'name' => 'user_tel',
            //         'value' => '$data->user_tel',
            // ),
        array(
            'name'  => 'department_id',
            'value' => '$data->department_id>0 ? UebModel::model("Department")->getDepartment($data->department_id):"--"',
            'htmlOptions' => array('style'=>'width:130px;'),
        ),
        // array(
        //     'header' => Yii::t('system', '岗位'),
        //     'value' => 'UserPosition::model()->getUserPositionNameByUserID($data->id)',
        //     'type'  => 'raw',
        //     'htmlOptions' => array('style'=>'width:160px;'),
        // ),
        array(
            'name'  => 'user_status',
            'value' => 'VHelper::getStatusLable($data->user_status)',
            'htmlOptions' => array('style'=>'width:100px;'),
        ),      
    ),
    'tableOptions' => array(
        'layoutH' => 135,
    ),
    'pager' => array(),
);
if ( Yii::app()->request->getParam('target',null) == 'dialog' ) {
    $config['toolBar'] = array(
        array(
            'text' => Yii::t('system', 'Please Select'),
            'type' => 'button',
            'htmlOptions' => array(
                'class' => 'edit',
                'multLookup' => 'user-grid_c0[]',
                'warn' => Yii::t('users', 'Please select a user'),
                'rel' => Yii::app()->request->getParam('on')=='userId' ? '{target:"to_user_id", url:"users/users/getuserid"}' : "{target:'roleUserPanel', url: 'users/users/ulist'}",
            )
        ),
    );
    $config['tableOptions'] = array( 'layoutH' => 126 );
} else {
    $config['columns'][] = array(
        'header' => Yii::t('system', 'Operation'),
        'class' => 'CButtonColumn',
        'headerHtmlOptions' => array('width' => '200', 'align' => 'center'),
        'template' => '{editUserPrivilege}',  
        'buttons' => array(
                    // 'update1' => array(
                    //         'url'       => 'Yii::app()->createUrl("/systems/position/leadersetpersonnel", array("departmentId" => '.$departmentId.', "userId" => $data->id))',
                    //         'label'     => Yii::t('system', '设置组长下属岗位'),
                    //         'options'   => array(
                    //                 'class' => 'btnAdd',
                    //                 'target'    => 'dialog',
                    //                 'height' => '500',
                    //                 'width' => '650',
                    //         ),
                    //         'visible'   =>  '$data->isleader'
                    // ),
                    'editUserPrivilege' => array(
                                    'label' => Yii::t('user', 'Edit Users Privilege'),
                                    'url' => 'Yii::app()->createUrl("/users/users/setprivilege", array("id" => $data->id))',
                                    'title' => Yii::t('user', 'Edit Users Privilege'),
                                    'options' => array('target' => 'dialog','class'=>'btnEdit', 'width'=>800, 'height'=>600),
                                    
                    ),
        ),
    );

    $config['toolBar'] = array(
        array(
                'text'          => Yii::t('user', 'Batch Setting Privilege'),
                'url'           => 'javascript:void(0)',
                'htmlOptions'   => array(
                        'class'     => 'edit',
                        //'title'     => Yii::t('user', "Are you sure set these account privileges"),
                        //'target'    => 'selectedTodo',
                        'rel'       => 'user-grid',
                        'onclick'   =>  'batchSetPrivilege()'
                )
        ),
    );

    //如果是超级管理员，显示设置主管按钮
    if($isAdmin){
        // $config['toolBar'][] = array(
        //         'text'          => Yii::t('user', '设置主管'),
        //         'url'           => "javascript:void(0)",
        //         'htmlOptions'   => array(
        //             'class'     => 'add',
        //             'rel'       => 'user-grid',
        //             'onclick'   =>  'setDirector()'
        //         )
        // );

        // $config['toolBar'][] = array(
        //         'text'          => Yii::t('user', '设置组长'),
        //         'url'           => "javascript:void(0)",
        //         'htmlOptions'   => array(
        //             'class'     => 'add',
        //             'rel'       => 'user-grid',
        //             'onclick'   =>  'batchSetLeader()'
        //         )
        // );

        // $config['toolBar'][] = array(
        //         'text'          => Yii::t('user', '设置员工'),
        //         'url'           => "javascript:void(0)",
        //         'htmlOptions'   => array(
        //             'class'     => 'add',
        //             'rel'       => 'user-grid',
        //             'onclick'   =>  'batchSetPersonnel()'
        //         )
        // );
    }
}
$this->widget('UGridView', $config);
?>

<script type="text/javascript">
    function batchSetPrivilege(){
        var ids = "";
        var arrChk= $("input[name='user-grid_c0[]']:checked");
        if(arrChk.length==0){
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i=0;i<arrChk.length;i++)
        {
            ids += arrChk[i].value+',';
        }
        var urls ='/users/users/batchsetprivilege/ids/'+encodeURIComponent(ids);
        $.pdialog.open(urls, '1', '<?php echo Yii::t('user', 'Batch Setting Privileges');?>', {width: 1000, height: 600,mask:true,fresh:true});
        return false;
    }

    // 设置主管岗位
    function setDirector(){
        var id = "";
        var arrChk= $("input[name='user-grid_c0[]']:checked");
        if(arrChk.length==0){
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }

        if(arrChk.length > 1){
            alertMsg.error('<?php echo Yii::t('system', '设置主管只能选择一个用户'); ?>');
            return false;
        }

        var departmentId = "<?php echo $departmentId; ?>";

        var url ='/systems/position/batchsetdirector';
        var param = {'id':arrChk[0].value, 'departmentId':departmentId};

        $.post(url, param, function(data){
            if(data.statusCode == 200){
                alertMsg.correct(data.message);
            }else{
                alertMsg.error(data.message);
            }
        }, 'json');
    }


    // 设置组长岗位
    function batchSetLeader(){
        var ids = "";
        var arrChk= $("input[name='user-grid_c0[]']:checked");
        if(arrChk.length==0){
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i=0;i<arrChk.length;i++)
        {
            ids += arrChk[i].value+',';
        }

        var departmentId = "<?php echo $departmentId; ?>";

        var url ='/systems/position/batchsetleader';
        var param = {'ids':ids, 'departmentId':departmentId};

        $.post(url, param, function(data){
            if(data.statusCode == 200){
                alertMsg.correct(data.message);
            }else{
                alertMsg.error(data.message);
            }
        }, 'json');
    }


    // 设置员工岗位
    function batchSetPersonnel(){
        var ids = "";
        var arrChk= $("input[name='user-grid_c0[]']:checked");
        if(arrChk.length==0){
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i=0;i<arrChk.length;i++)
        {
            ids += arrChk[i].value+',';
        }

        var departmentId = "<?php echo $departmentId; ?>";

        var url ='/systems/position/batchsetpersonnel';
        var param = {'ids':ids, 'departmentId':departmentId};

        $.post(url, param, function(data){
            if(data.statusCode == 200){
                alertMsg.correct(data.message);
                return false;
            }else if(data.statusCode == 600){
                alertMsg.confirm(data.message, {okCall:function(){
                    var params = {'ids':ids, 'departmentId':departmentId, 'ishave':'1'};
                    $.post(url, params, function(data){
                        if(data.statusCode == 200){
                            alertMsg.correct(data.message);
                            return false;
                        }else{
                            alertMsg.error(data.message);
                        }
                    }, 'json');
                }, cancelCall : function(){
                    //取消复原
                }});
                return false;
            }else{
                alertMsg.error(data.message);
            }
        }, 'json');
    }

</script>