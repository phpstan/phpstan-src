<?php declare(strict_types = 1);

namespace Bug9120;

function do_something(): void
{
	$db = new \PDO('connection-string');

	$statement = $db->query('sql-query');
	if ($statement !== false)
	{
		$statement->setFetchMode(\PDO::FETCH_OBJ);

		foreach ($statement as $tmpObject)
		{
			echo $tmpObject->mycolumn;
		}
	}
}
