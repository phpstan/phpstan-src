<?php

namespace Bug7138;

function test(): \PgSql\Result|false {
	$connect = \pg_connect('');
	if ($connect === false) {
		return false;
	}

	return \pg_query($connect, '');
}
