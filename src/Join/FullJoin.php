<?php


namespace Pechynho\DbWrap\Join;


class FullJoin extends AbstractJoin
{
	public function __construct($table, $onClause)
	{
		parent::__construct($table, "FULL", $onClause);
	}
}
