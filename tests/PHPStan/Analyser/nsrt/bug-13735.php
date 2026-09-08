<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13735;

use function PHPStan\Testing\assertType;

class Bug13735Test
{
	private ?Foo $foo = null;

	public function testFoo(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735\Foo', $this->foo);
		self::assertTrue(true);
		assertType('Bug13735\Foo', $this->foo);
	}

	public static function assertTrue(mixed $condition, string $message = ''): void
	{

	}
}

class Foo {
	public ?Foo $inner = null;

	public function doSomething(): bool {
		return true;
	}
}

class Test
{
	private string $data;

	public function __construct() {
		$this->data = 'abc';
		assertType("'abc'", $this->data);
		self::noop('foo');
		assertType("'abc'", $this->data);
	}

	static final public function noop(string $message): void {
		file_put_contents('log file', $message);
	}

}

final class FinalTest
{
	private string $data;

	public function __construct() {
		$this->data = 'abc';
		assertType("'abc'", $this->data);
		self::noop('foo');
		assertType("'abc'", $this->data);
	}

	static public function noop(string $message): void {
		file_put_contents('log file', $message);
	}

}

final class PrivateTest
{
	private string $data;

	public function __construct() {
		$this->data = 'abc';
		assertType("'abc'", $this->data);
		self::noop('foo');
		assertType("'abc'", $this->data);
	}

	static private function noop(string $message): void {
		file_put_contents('log file', $message);
	}

}
