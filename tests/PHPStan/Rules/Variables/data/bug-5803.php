<?php declare(strict_types = 1);

namespace Bug5803;

function () {
	if (!($_GET['foo'])) { // if 'foo' is falsy, SET $var
		$var = "set";
	}

	if ($_GET['foo']) {

	} else {
		echo $var;
	}
};
