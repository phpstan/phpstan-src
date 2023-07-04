<?php

namespace Bug2508;

function connect(\mysqli $m): void
{
	mysqli_real_connect(
		$m,
		null,
		null,
		null,
		null,
		null,
		null,
		\MYSQLI_CLIENT_SSL
	);

}
