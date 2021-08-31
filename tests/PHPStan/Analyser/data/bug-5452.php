<?php declare(strict_types = 1);

namespace Bug5452;

use function PHPStan\Testing\assertType;

class Collector {
	/** @var array<string, object> */
	private array $collection = [];
	
	public function rewind(): void { 
		if (reset($this->collection) !== false) {
			assertType('array<string, object>&nonEmpty', $this->collection);
		}
	}
}
