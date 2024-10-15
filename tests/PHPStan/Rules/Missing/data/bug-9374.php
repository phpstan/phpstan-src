<?php declare(strict_types = 1);

namespace Bug9374;

function bar(): string {
	for ($i = 0; ; ++$i)
		return "";
}
