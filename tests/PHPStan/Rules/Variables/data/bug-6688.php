<?php declare(strict_types = 1);

namespace Bug6688;

echo $a; // possibly undefined

/** @var string $b */
echo $b; // willed into existence

/** @var string[] $c */
foreach ($c as $v) { // willed into existence
}

/** @var string[][] $result */
foreach ($result as $data) {
	echo '"' . implode('", "', $data) . '"' . "\n";
}
