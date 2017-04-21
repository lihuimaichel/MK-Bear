<?php
/**
 * 产品临时表
 * @author	yangsh
 * @since	2016-09-12
 */

class ProductTemp extends CommonModel {
	
	/**
	 * Newly developed
	 *
	 * @var integer
	 */
	const STATUS_NEWLY_DEVELOPED = 1;	#刚开发

	/**
	 * Editing
	 *
	 * @var integer
	 */
	const STATUS_EDITING = 2;	#编辑中

	/**
	 * Pre online
	 *
	 * @var integer
	 */
	const STATUS_PRE_ONLINE = 3;	#预上线
	
	const STATUS_QE_CHECK = 8;		#QE审核

	/**
	 * On sale
	 *
	 * @var integer
	 */
	const STATUS_ON_SALE = 4;	#在售中	

	/**
	 * Has unsalable
	 *
	 * @var integer
	 */
	const STATUS_HAS_UNSALABLE = 5;		#已滞销
	
	/**
	 *Wait for the clearance
	 *
	 * @var integer
	 */
	const STATUS_WAIT_CLEARANCE = 6;	#待清仓

	/**
	 * Stop selling
	 *
	 * @var integer
	 */
	const STATUS_STOP_SELLING = 7;		#已停售


    /**@var 多属性参数*/
    const PRODUCT_MULTIPLE_NORMAL       = 0;//单品
    const PRODUCT_MULTIPLE_VARIATION    = 1;//子sku
    const PRODUCT_MULTIPLE_MAIN         = 2;//主sku	

	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_product_temp';
	}

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }  	
	
}