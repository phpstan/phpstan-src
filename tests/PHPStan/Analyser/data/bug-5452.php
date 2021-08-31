<?php declare(strict_types = 1);

namespace Bug5452;

use function PHPStan\Testing\assertType;

class Collector {
	/** @var array<string, object> */
	private $collection = [];
	
	public function rewind(): void { 
		if (reset($this->collection) === false) {
			assertType('array<string, object>', $this->collection);
		}
		if (reset($this->collection) !== false) {
			assertType('array<string, object>&nonEmpty', $this->collection);
		}
		if (reset($this->collection) === null) {
			assertType('array<string, object>&nonEmpty', $this->collection);
		}
		if (reset($this->collection) === "hello") {
			assertType('array<string, object>&nonEmpty', $this->collection);
		}
	}
}
