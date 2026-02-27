<?php declare(strict_types=1);

namespace Bug7699;

$selections = isset($_GET['x']) ? explode(',', $_GET['x']) : [];
while ($selections && $id = array_shift($selections)) {
	if ($selections) {
		var_dump('x');
	}
}
