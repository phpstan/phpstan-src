<?php

namespace Bug9524;

class MysqlConnection extends \mysqli
{
	public function change_user($user, $password, $database): bool
	{
		return false;
	}
}
