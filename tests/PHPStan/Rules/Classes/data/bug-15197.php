<?php // lint >= 8.0

namespace Bug15197;

interface DiInterface
{
	/**
	 * @param-closure-this DiInterface $definition
	 */
	public function set(string $name, mixed $definition): void;

	public function get(string $service): object;
}

class MyServiceProvider
{
	const SERVICE_FOO = 'foo';

	public function provide(DiInterface $di): void {
		$di->set('foobar', function () {
			return $this->get(self::SERVICE_FOO);
		});
	}
}

class BoundScope
{
	public const X = 'bound';
}

class Enclosing
{
	public const X = 'enclosing';

	/**
	 * @param-closure-scope BoundScope $cb
	 */
	public function withScope(callable $cb): void
	{
	}

	/**
	 * @param-closure-this BoundScope $cb
	 * @param-closure-scope BoundScope $cb
	 */
	public function withThisAndScope(callable $cb): void
	{
	}

	public function test(): void
	{
		$this->withScope(function () {
			echo self::X;
		});

		$this->withThisAndScope(function () {
			echo self::X;
		});

		$this->withScope(function () {
			echo self::MISSING;
		});
	}
}
