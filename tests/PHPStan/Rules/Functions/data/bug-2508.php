<?php

namespace Bug2508;

function connect(\mysqli $mysqli): void
{
	mysqli_real_connect(
		$mysqli,
		null,
		null,
		null,
		null,
		null,
		null,
		\MYSQLI_CLIENT_SSL
	);
}

function connectOop(\mysqli $mysqli): void
{
	$mysqli->real_connect(
		null,
		null,
		null,
		null,
		null,
		null,
		\MYSQLI_CLIENT_SSL
	);
}
