<?php

namespace UnusedBinaryOperationResult;

class Foo
{

	public function unused(): void
	{
		4 + $this->doInt();
		4 - $this->doInt();
		4 * $this->doInt();
		4 / $this->doInt();
		4 === $this->doInt();
		4 ** $this->doInt();
		pow(4, $this->doInt());
		4 + $this->doInt() - 5;
		'No' . $this->doString();
	}

	/**
	 * @param int[] $numbers
	 */
	public function unusedInForeach(array $numbers): void
	{
		foreach ($numbers as $number) {
			$number + $this->doInt();
			$number - $this->doInt();
			$number * $this->doInt();
			$number / $this->doInt();
			$number === $this->doInt();
			$number ** $this->doInt();
			pow($number, $this->doInt());
			$number + $this->doInt() - 5;
			'No' . $this->doString();
		}
	}

	public function doInt(): int
	{
		return 5;
	}

	public function doString(): string
	{
		return 'some text';
	}

	private function useResult(int $result): void
	{
		echo $result;
	}

}

$foo = new Foo();
$foo->doInt() + $foo->doInt();
$foo->doInt() - $foo->doInt();
$foo->doInt() * $foo->doInt();
$foo->doInt() ^ $foo->doInt();
pow($foo->doInt(), $foo->doInt());
$foo->doInt() - 5 + $foo->doInt();
'No' . $foo->doString();
echo 'Yes' . $foo->doString();
