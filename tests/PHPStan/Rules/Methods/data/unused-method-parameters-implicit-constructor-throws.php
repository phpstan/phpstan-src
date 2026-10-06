<?php // lint >= 8.0

namespace UnusedMethodParametersImplicitConstructorThrows;

class Foo
{

	public function __construct(string $value)
	{
		if ($value === '') {
			throw new \InvalidArgumentException('Empty value');
		}
	}

}

class Bar
{

	private function readOnlyInCatch(string $value, string $param): ?Foo
	{
		try {
			return new Foo($value);
		} catch (\Throwable $e) {
			echo $param;
			return null;
		}
	}

	private function readOnlyInCatchOfException(string $value, string $param): ?Foo
	{
		try {
			return new Foo($value);
		} catch (\Exception $e) {
			echo $param;
			return null;
		}
	}

	public function doBar(): void
	{
		$this->readOnlyInCatch('x', 'y');
		$this->readOnlyInCatchOfException('x', 'y');
	}

}
