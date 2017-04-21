<?php
/**
 * 公共模型类
 * @author zhangF
 *
 */
class CommonModel extends UebModel {
	/**
	 * @desc 切换数据库
	 * @return string
	 */
	public function getDbKey() {
		return 'db_common';
	}
}