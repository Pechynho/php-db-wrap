<?php


namespace Pechynho\DbWrap\Join;


class LeftJoin extends AbstractJoin
{
	public function __construct($table, $onClause)
	{
		parent::__construct($table, "LEFT", $onClause);
	}
}
