<?php declare(strict_types = 1);

namespace Bug10854NullCoalesce;

class Foo
{
	public function doFoo(string $input): void
	{
		@list($a, $b) = explode('-', $input);
		$x = $a ?? 'default'; // no error - $a might be null
		$y = $b ?? 'default'; // no error - $b might be null
	}

	public function doBar(string $input): void
	{
		@[$a, $b] = explode('-', $input);
		$x = $a ?? 'default'; // no error
		$y = $b ?? 'default'; // no error
	}

	/**
	 * @param list<string> $list
	 */
	public function doBaz(array $list): void
	{
		[$a, $b] = $list;
		$x = $a ?? 'default'; // no error
		$y = $b ?? 'default'; // no error
	}

	/**
	 * @param array{0: string, 1?: string} $arr
	 */
	public function doQux(array $arr): void
	{
		[$a, $b] = $arr;
		$x = $a ?? 'default'; // $a is always string from required key 0
		$y = $b ?? 'default'; // no error - key 1 is optional
	}

	public function coalesceAssign(string $input): void
	{
		[$a, $b] = explode('-', $input);
		$a ??= 'default'; // $a is always string from non-empty-list index 0
		$b ??= 'default'; // no error - $b might be null
	}

	public function issetAfterList(string $input): void
	{
		[$a, $b] = explode('-', $input);
		if (isset($b)) { // no error - $b might be null/undefined
			echo $b;
		}
	}
}
