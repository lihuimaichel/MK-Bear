<?php

/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2016/12/2
 * Time: 11:37
 *
 * 获取刊登，待优化的历史记录
 */
class TaskController extends TaskCommonController
{
	public function accessRules()
	{
		return array(
			array(
				'allow',
				'users' => array(
					'*'
				),
				'actions' => array(
					'index',//new
					'tasksetting', //任务设置
				)
			)
		);
	}


	/**
	 * 获取待刊登的记录
	 */
	public function actionIndex()
	{
		$model = $this->model('wait');
		$this->render("index", array('model' => $model, 'platform' => $this->userPlatform()));
	}

	public function actionAppeal()
    {
        $id = Yii::app()->request->getParam('id');
        $model = $this->model('wait');
        $data = $model->getoneByCondition("id, account_short_name, site_name, sku, sku_title", "id = '{$id}'");
        $this->render("appeal", array('data' => $data));
    }

    /**
     * 销售提交申诉
     */
    public function actionProcess()
    {
        $id = Yii::app()->request->getParam('id');
        $seller_user_id = Yii::app()->user->id;
        $param['appeal_type'] = Yii::app()->request->getParam('appeal_type');
        $param['appeal_description'] = Yii::app()->request->getParam('appeal_description');
        $param['appeal_status'] = 1; //申诉中
        $param['appeal_time']   = date('Y-m-d H:i:s');
        $model = $this->model('wait');
        //检查此id是否存在或者属于当前操作人员
        $check_row = $model->getOneByCondition("id", "id='{$id}' AND seller_user_id = '{$seller_user_id}'");
        $result = false;
        if (!empty($check_row)) {
            $result = $model->updateDataByID($param, $id);
        }

        $returnData = $this->returnData('appeal');
        $jsonData = array(
            'message' => (true == $result) ? '更改成功' : '更新失败',
            'forward' => $returnData['forward'],
            'navTabId' => $returnData['navTabId'],
            'callbackType' => 'closeCurrent'
        );
        echo $this->successJson($jsonData);
    }

    /**
     * 销售取消申诉
     */
    public function actionUnappeal()
    {
        $id = Yii::app()->request->getParam('id');
        $seller_user_id = Yii::app()->user->id;
        $param['appeal_type'] = 0;
        $param['appeal_status'] = 0;
        $model = $this->model('wait');
        //检查此id是否存在或者属于当前操作人员
        $check_row = $model->getOneByCondition("id", "id='{$id}' AND seller_user_id = '{$seller_user_id}'");
        $result = false;
        if (!empty($check_row)) {
            $result = $model->updateDataByID($param, $id);
        }

        $returnData = $this->returnData('appeal');
        $jsonData = array(
            'message' => (true == $result) ? '更改成功' : '更新失败',
            'forward' => $returnData['forward'],
            'navTabId' => $returnData['navTabId'],
            //'callbackType' => 'closeCurrent'
        );
        echo $this->successJson($jsonData);
    }


    public function actionException()
    {
        $model = $this->model('exception');
        $this->render("exception", array('model' => $model, 'platform' => $this->userPlatform()));
    }


    public function actionProcessappeal()
    {
        $id = Yii::app()->request->getParam('id');
        $model = $this->model('wait');
        $appeal_types = array(
            1 => Yii::t('Task', 'Appeal SKU unlocked'),
            2 => Yii::t('Task', 'Appeal SKU violation'),
            3 => Yii::t('Task', 'Appeal UnAdvantage'),
            4 => Yii::t('Task', 'Appeal Category Uncompliant'),
            5 => Yii::t('Task', 'Appeal Unfit platform'),
            6 => Yii::t('Task', 'Appeal Others'),
        );
        $data = $model->getoneByCondition("id, seller_user_id, account_short_name, site_name, sku, sku_title, appeal_type", "id = '{$id}'");
        $userinfo = isset($data['seller_user_id']) ? User::model()->getUserNameArrById($data['seller_user_id']) : array();
        $data['seller_user_name'] = isset($userinfo[$data['seller_user_id']]) ? $userinfo[$data['seller_user_id']] : 'Unkown';
        $data['appeal_type'] = isset($appeal_types[$data['appeal_type']]) ? $appeal_types[$data['appeal_type']] : 'Unkown';
        $this->render("appeal_process", array('data' => $data));
    }


    public function actionAppealprocess()
    {
        $id = Yii::app()->request->getParam('id');
        $param['appeal_status'] = Yii::app()->request->getParam('appeal_status');
        $param['appeal_reject'] = Yii::app()->request->getParam('appeal_reject');
        $model = $this->model('wait');
        //检查此id是否存在或者属于当前操作人员
        $check_row = $model->getOneByCondition("*", "id='{$id}'");
        $result = false;
        if (!empty($check_row)) {
            $result = $model->updateDataByID($param, $id);
            //如果通过，则修改接口Api数据状态
            if (2 == $param['appeal_status']) {
                $arr[] = array(
                    'platformCode' => $check_row['platform_code'],
                    'staffId' => $check_row['seller_user_id'],
                    'accountId' => $check_row['account_id'],
                    'sku' => $check_row['sku'],
                    'site' => $check_row['site_name'],
                    'status' => 4,
                    'updater' => 'system_client_sync'
                );
                $apiResult = $this->postApi($arr);

                //并且重新拉取一条数据
                $getApi = $this->getApi(array('platform' => $check_row['platform_code'], 'seller_user_id' => $check_row['seller_user_id']));
            }
        }
        $returnData = $this->returnData('appeal');
        $jsonData = array(
            'message' => (true == $result) ? '更改成功' : '更新失败',
            'forward' => $returnData['forward'],
            'navTabId' => $returnData['navTabId'],
        );
        echo $this->successJson($jsonData);
    }

    public function actionApplytask()
    {
        $seller_user_id = Yii::app()->user->id;
        $dep_id = Yii::app()->user->department_id;
        $platforms = Department::departmentPlatform();
        $platform = isset($platforms[$dep_id]) ? $platforms[$dep_id] : 0;
        $result = $this->getApi(array('platform' => $platform, 'seller_user_id' => $seller_user_id));

        $returnData = $this->returnData('appeal');
        $jsonData = array(
            'message' => (true == $result) ? Yii::t('task', 'Apply Sucess') : Yii::t('task', 'Apply Fail'),
            'forward' => $returnData['forward'],
            'navTabId' => $returnData['navTabId'],
        );
        echo $this->successJson($jsonData);
    }

	/**
	 * 待优化列表
	 */
	public function actionOptimization()
	{
		$optimization_type = Yii::app()->request->getParam('optimization_type');
		$model = (1 == $optimization_type) ? $this->model('optimization_history') : $this->model('optimization');
		$this->render("optimization", array('model' => $model, 'platform' => $this->userPlatform()));
	}

	/**
	 * 查看历史记录
	 */
	public function actionListingHistory()
	{
		$model = $this->model('history');
		$this->render("listing_history", array('model' => $model, 'platform' => $this->userPlatform()));
	}

	/**
	 * 历史记录
	 */
	public function actionRecord()
	{
		$model = $this->model('record');
		$this->render('record', array('model' => $model, 'platform' => $this->userPlatform()));
	}

    /**
     * 按月汇总
     */
	public function actionRecordmonth()
    {
        $model = $this->model('month_record');
        $this->render('month_record', array('model' => $model, 'platform' => $this->userPlatform()));
    }

    /**
     * 按年汇总
     */
    public function actionRecordyear()
    {
        $model = $this->model('year_record');
        $this->render('year_record', array('model' => $model, 'platform' => $this->userPlatform()));
    }

    /**
	 * 刊登排行
	 */
	public function actionListingrank()
	{
		$model = $this->model('listing_rank');
		$this->render('listing_rank', array('model' => $model, 'platform' => $this->userPlatform()));
	}

	public function actionOptimizationrank()
	{
		$model = $this->model('optimization_rank');
		$this->render('optimization_rank', array('model' => $model, 'platform' => $this->userPlatform()));
	}
}