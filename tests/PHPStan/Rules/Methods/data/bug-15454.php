<?php // lint >= 8.1

namespace Bug15454;

class Foo
{
	private string $name = 'a name';

	public function __debugInfo($a) {
		return [__CLASS__ . '::' . $this->name];
	}
}

class Bar
{
	private string $name = 'a name';

	public function __debugInfo(string $a) {
		return [__CLASS__ . '::' . $this->name];
	}
}

class CloneWithArg
{
	function __clone($var) {
	}
}

class DestructWithArg
{
	function __destruct($var) {
	}
}

class Valid
{
	public function __construct($a, $b) {}
	public function __destruct() {}
	public function __clone() {}
	public function __get($name) {}
	public function __set($name, $value) {}
	public function __isset($name) { return true; }
	public function __unset($name) {}
	public function __call($name, $args) {}
	public static function __callStatic($name, $args) {}
	public function __toString() { return ''; }
	public function __debugInfo() { return []; }
	public function __serialize() { return []; }
	public function __unserialize(array $data) {}
	public static function __set_state($properties) { return new self(1, 2); }
	public function __sleep() { return []; }
	public function __wakeup() {}
	public function __invoke($a, $b, $c) {}
}

class VariadicIsNotCounted
{
	public function __clone(...$args) {}
	public function __get(...$args) {}
	public function __call($name, ...$args) {}
}

class WrongCounts
{
	public function __get() {}
	public function __set($name) {}
	public function __isset($name, $b) { return true; }
	public function __unset() {}
	public function __call($name) {}
	public static function __callStatic($name, $args, $c) {}
	public function __toString($a = '') { return ''; }
	public function __serialize($a) { return []; }
	public function __unserialize(array $data, $b) {}
	public static function __set_state() { return new self(); }
	public function __sleep($a) { return []; }
	public function __wakeup($a) {}
	public function __DEBUGINFO($a) { return []; }
}

class ByRef
{
	public function __get(&$name) {}
	public function __set($name, &$value) {}
	public function __isset(&$name) { return true; }
	public function __unset(&$name) {}
	public function __call(&$name, $args) {}
	public static function __callStatic($name, &$args) {}
	public function __unserialize(array &$data) {}
	public static function __set_state(&$properties) { return new self(); }
}

interface SomeInterface
{
	public function __clone($a);
}

abstract class SomeAbstract
{
	abstract public function __get();
}

enum SomeEnum
{
	public function __debugInfo($a) { return []; }
}

trait SomeTrait
{
	public function __toString($a) { return ''; }
}

class UsesTrait
{
	use SomeTrait;
}
