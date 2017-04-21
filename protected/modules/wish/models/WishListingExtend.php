<?php
class WishListingExtend extends WishModel{
	public function tableName(){
		return 'ueb_wish_listing_extend';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}


	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
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

	public function getExtendByParentId($parentId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select('description')
            ->where('listing_id=:listingId', array(':listingId'=> $parentId));

        return $queryBuilder->queryRow();

    }
}