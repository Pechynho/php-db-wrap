<?php


namespace Pechynho\DbWrap\Join;


class Join extends AbstractJoin
{
	public function __construct($table, $onClause)
	{
		parent::__construct($table, "INNER", $onClause);
	}
}
