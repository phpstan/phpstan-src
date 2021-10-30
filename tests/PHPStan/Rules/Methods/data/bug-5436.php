<?php

namespace Bug5436;

final class PDO extends \PDO
{
	#[\ReturnTypeWillChange]
	public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs)
	{
		return parent::query($query, $fetchMode, ...$fetchModeArgs);
	}
}
