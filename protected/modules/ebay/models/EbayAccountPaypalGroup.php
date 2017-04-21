<?php

/**
 * @desc Ebay帐号分组paypal
 * @author qzz
 * @since 2017-03-17
 */
class EbayAccountPaypalGroup extends EbayModel
{

    public $rule = null;
    public $account_name = null;
    public $errorMeaage = null;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_ebay_account_paypal_group';
    }

    /**
     * getOneByCondition
     * @param  string $fields
     * @param  string $where
     * @param  string $order
     * @return array
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * getListByCondition
     * @param  string $fields
     * @param  string $where
     * @param  string $order
     * @return array
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    // =========== start: search ==================

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('group_name,status', 'required'),
        );
    }

    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('system', 'No.'),
            'status' => '状态',
            'group_name' => '分组名',
            'add_time' => '添加时间',
            'rule' => '规则',
            'account_name' => '帐号',
        );
    }

    public function getStatusOptions($status = null)
    {
        //@todo 后续语言处理
        $statusOptions = array(
            1 => '启用',
            0 => '禁用',
        );
        if ($status !== null)
            return isset($statusOptions[$status]) ? $statusOptions[$status] : '';
        return $statusOptions;
    }




    public function addtions($datas)
    {
        if (empty($datas)) return $datas;
        $paypalList = PaypalAccount::getPaypalList(Platform::CODE_EBAY);
        foreach ($datas as &$data) {
            //状态
            $data['status'] = $this->getStatusOptions($data['status']);
            $rule = EbayGroupRuleRelation::model()->getListByGroupId($data['id']);
            $data['rule'] = '';
            foreach($rule as $value){
                $data['rule'] .= "paypal帐号：".$paypalList[$value['paypal_id']]."&nbsp;&nbsp;&nbsp;金额开始：".$value['amount_start']."&nbsp;&nbsp;&nbsp;金额结束：".$value['amount_end']."<br>";
            }
            $accountList = $this->getEbayAccountList($data['id']);
            $data['account_name'] = '';
            if($accountList){
                foreach($accountList as $ak=>$value){
                    $data['account_name'] .= $value['short_name'].',';
                    if(($ak+1)%10==0){
                        $data['account_name'] .="<br>";
                    }
                }
                $data['account_name'] = rtrim($data['account_name'],',');
            }
        }
        return $datas;
    }


    /**
     * get search info
     */
    public function search()
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
        );
        $dataProvider = parent::search(get_class($this), $sort);
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }

    /**
     * filter search options
     * @return type
     */
    public function filterOptions()
    {
        $status = Yii::app()->request->getParam('status');
        $result = array(
            array(
                'name' => 'status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getStatusOptions(),
                'value' => $status
            ),
        );
        return $result;
    }



    // =========== end: search ==================

    /**
     * @desc 设置上传报错信息
     * @param string $message
     */
    public function setErrorMessage($message){
        $this->errorMeaage = $message;
    }

    /**
     * @desc 获取报错信息
     */
    public function getErrorMessage(){
        return $this->errorMeaage;
    }

    public function getEbayAccountList($groupId){
        $post = array(
            'select' =>array('id,short_name'),
            'condition' => 'paypal_group_id='.$groupId,
        );
        return EbayAccount::model()->findAll($post);
    }

    public function saveData($data){
        $res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        if($res){
            return $this->getDbConnection()->getLastInsertID();
        }
        return false;
    }

    /**
     * @desc 获取paypal组列表
     * @return multitype:string
     */
    public static function getGroupList(){

        $item = array();
        $list = self::getAblePaypalGroupList();
        if ($list){
            foreach ($list as $val){
                $item[$val['id']] = $val['group_name'];
            }
        }
        return $item;
    }

    /**
     * @desc 获取已启用paypal帐号组
     * @return multitype:string
     */
    public static function getAblePaypalGroupList(){
        $self = new self();
        return $self->getDbConnection()->createCommand()
            ->from(self::tableName())
            ->select("*")
            ->where("status = 1")
            ->queryAll();
    }
    /*
     * 根据帐号和价格获取对应的paypal帐号
     * return paypal_id
     */
    public function getEbayPaypal($accountID,$salePrice,$currency='USD'){
        if($currency != 'USD'){
            $rate = CurrencyRate::model()->getRateToOther($currency);
            $salePrice *= $rate;
        }

        $accountInfo = EbayAccount::model()->getAccountInfoById($accountID);
        if($accountInfo['paypal_group_id']==0){
            $this->setErrorMessage('请先选择paypal分组');
            return false;
        }

        $PayPalList = EbayGroupRuleRelation::model()->getListByGroupId($accountInfo['paypal_group_id']);

        $PayPalId = '';
        foreach($PayPalList as $value){
            if($salePrice<0.01 && $salePrice>=0){ //小于0.01的
                if($value['amount_start']==0.01){
                    $PayPalId = $value['paypal_id'];
                    break;
                }
            }else{
                if($salePrice>=$value['amount_start'] && $salePrice<$value['amount_end']){//判断金额在哪个范围区间
                    $PayPalId = $value['paypal_id'];
                    break;
                }
            }
        }
        if($PayPalId==''){
            $this->setErrorMessage('没找到对应规则的paypal帐号');
            return false;
        }

        $PayPalInfo = PaypalAccount::model()->getOneByCondition("email","status=1 and id={$PayPalId} and platform_code= '".Platform::CODE_EBAY."'");

        if(!$PayPalInfo){
            $this->setErrorMessage('没找到有效的paypal帐号');
            return false;
        }

        return $PayPalInfo['email'];

    }

}