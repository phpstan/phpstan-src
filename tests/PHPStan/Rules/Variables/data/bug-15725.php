<?php declare(strict_types = 1);

namespace Bug15725;

if ($level == 'success') {
	echo $level;
}

// null == '', so the variable might still be undefined
if ($other == '') {
	echo $other;
}
