<?php


namespace Pechynho\DbWrap\Join;


class RightJoin extends AbstractJoin
{
	public function __construct($table, $onClause)
	{
		parent::__construct($table, "RIGHT", $onClause);
	}
}
