<?php // lint >= 7.4

namespace TooWideArrowFunctionReturnType;

class Foo
{

	public function doFoo(?string $nullableString)
	{
		fn (): \Generator => yield 1;

		fn (): ?string => null;

		fn (): ?string => 'foo';

		fn (): ?string => $nullableString;
	}

	public function doBar()
	{
		/** @var string[][] $data */
		$data = doFoo();
		array_reduce($data, static fn (int $carry, array $item) => $carry + $item['total_count'], 0);
	}

}
