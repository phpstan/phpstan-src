<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug7317;

class MySimpleXMLElement extends \SimpleXMLElement {
	public function current(): bool {
		return false;
	}

	public function valid(): int {
		return 1;
	}
}
