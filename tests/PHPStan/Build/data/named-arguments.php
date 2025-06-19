<?php // lint >= 8.0

namespace NamedArgumentRule;

use Exception;

class Foo
{

	public function doFoo(): void
	{
		new Exception('foo', 0);
		new Exception('foo', 0, null);
		new Exception('foo', 0, new Exception('previous'));
		new Exception('foo', previous: new Exception('previous'));
		new Exception('foo', code: 0, previous: new Exception('previous'));
		new Exception('foo', code: 1, previous: new Exception('previous'));
		new Exception('foo', 1, new Exception('previous'));
		new Exception('foo', 1);
		new Exception('', 0, new Exception('previous'));
	}

}

function (): void {
	$output = null;
	exec('exec', $output, $exitCode);
};

class Bar
{

	public static function doFoo($a, &$byRef = null, int $another = 1, int $yetAnother = 2): void
	{

	}

	public function doBar(): void
	{
		$byRef = null;
		self::doFoo('a', $byRef, 1, 3);
	}

}

class Baz
{

	public const HIGH = 1;
	public const MEDIUM = 2;

	public static function send(Bar $message, ?string $queueName = null, ?Bar $mode = null, int $priority = self::HIGH)
	{

	}

	public function doFoo(Bar $message): void
	{
		self::send($message, 'queue', priority: self::HIGH);
		self::send($message, 'queue', priority: self::MEDIUM);
	}

}
