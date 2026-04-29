<?php

namespace Bug14548;

class TProcessHelper
{

	public static function setProcessPriority(int $priority): void
	{
		$priorityValues = [ // The priority cap to windows text priority.
			-15 => 24,
			-10 => 13,
			-5 => 10,
			4 => 8,
			9 => 6,
			PHP_INT_MAX => 4,
		];
		foreach ($priorityValues as $keyPriority => $priorityName) {
			if ($priority <= $keyPriority) {
				break;
			}
		}
	}
}
