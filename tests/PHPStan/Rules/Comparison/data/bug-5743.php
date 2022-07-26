<?php

namespace Bug5743;

$list = [];
$offset = 0;

do {
	//
} while ($list[++$offset] && $list[++$offset]);
