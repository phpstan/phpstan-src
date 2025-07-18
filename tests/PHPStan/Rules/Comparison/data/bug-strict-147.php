<?php declare(strict_types = 1);

namespace BugStrict147;

$array = ['a', 'c', 'b'];
asort($array);
if (array_is_list($array)) {
	print 'array was in order';
}
