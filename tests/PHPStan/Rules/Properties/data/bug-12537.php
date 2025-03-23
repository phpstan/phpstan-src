<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12537;

use WeakMap;

class Metadata {
	/**
	 * @var WeakMap<stdClass, int>
	 */
	private readonly WeakMap $storage;

	public function __construct() {
		$this->storage = new WeakMap();
	}

	public function set(stdClass $class, int $value): void {
		$this->storage[$class] = $value;
	}

	public function get(stdClass $class): mixed {
		return $this->storage[$class] ?? null;
	}
}

$class = new stdClass();
$meta  = new Metadata();

$meta->set($class, 123);

var_dump($meta->get($class));
