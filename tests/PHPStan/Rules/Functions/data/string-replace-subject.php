<?php declare(strict_types=1);

namespace StringReplaceSubject;

class HelloWorld
{
	private string $prop = 'Foo';

	public function wrong(string $path): void
	{
		$x = strtr('\\', '/', $path);
		$x = str_replace($path, '\\', '/');
		$x = preg_replace($path, '\\', '/');
		$x = str_replace($path, '\\', '/', $count);
		$x = preg_replace($path, '\\', '/', $count);

		$x = strtr('\\', $path, '/');
		$x = str_replace('\\', $path, '/');
		$x = preg_replace('{(.*)}', $path, '/');

		$x = strtr('\\', '/', $this->prop);
		$x = str_replace($this->prop, '\\', '/');
		$x = preg_replace($this->prop, '{(.*)}', '/');

		$x = strtr('\\', '/', doFooBar());
		$x = str_replace(doFooBar(), '\\', '/');
		$x = preg_replace(doFooBar(), '{(.*)}', '/');

		$x = strtr('\\', '/', $this->doFoo());
		$x = str_replace($this->doFoo(), '\\', '/');
		$x = preg_replace($this->doFoo(), '{(.*)}', '/');

		$x = strtr('\\', '/', self::doFooStatic());
		$x = str_replace(self::doFooStatic(), '\\', '/');
		$x = preg_replace(self::doFooStatic(), '{(.*)}', '/');
	}

	/**
	 * @param literal-string $literalString
	 */
	public function correct(string $path, $literalString): void
	{
		$x = str_replace('\\', '/', __FILE__);
		$x = str_replace('\\', '/', __DIR__ . '/PHP-Parser');
		$x = strtr($literalString, $literalString, $path);

		$x = strtr($path, '\\', '/');
		$x = str_replace('\\', '/', $path);
		$x = preg_replace('{(.*)}', '/', $path);

		$x = str_replace(['\\'], ['/'], $path);
		$x = preg_replace(['{(.*)}'], ['/'], $path);

		$x = str_replace(['\\'], ['/'], [$path]);
		$x = preg_replace(['{(.*)}'], ['/'], [$path]);
	}

	public function doFoo(): string
	{
		return 'doFoo';
	}

	static public function doFooStatic(): string
	{
		return 'doFooStatic';
	}
}

function doFooBar(): string
{
	return 'doFooBar';
}
