<?php declare(strict_types = 1);

namespace Bug14349;

$a = [1];

foreach ($a as $this) {
	var_dump($this);
}

foreach ($a as &$this) {
	var_dump($this);
}

foreach ($a as $this => $v) {
	var_dump($this);
}

foreach ($a as $ok) {
	var_dump($ok);
}
