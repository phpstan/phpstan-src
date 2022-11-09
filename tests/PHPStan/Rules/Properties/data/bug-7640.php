<?php declare(strict_types = 1);

namespace Bug7640;

class C
{
}

class P
{
	private ?C $_connection = null;

	public function getConnection(): C
	{
		$this->_connection = new C();

		return $this->_connection;
	}

	public static function connect(): P
	{
		return new P();
	}

	public static function assertInstanceOf(object $object): static
	{
		if (!$object instanceof static) {
			throw new \TypeError('Object is not an instance of static class');
		}

		return $object;
	}
}

abstract class TestCase
{
	protected function createPWithLazyConnect(): void
	{
		new class() extends P
		{
			public function __construct()
			{
			}

			public function getConnection(): C
			{
				\Closure::bind(function () {
					if ($this->_connection === null) {
						$connection = P::assertInstanceOf(P::connect())->_connection;
					}
				}, null, P::class)();

				return parent::getConnection();
			}
		};
	}
}
