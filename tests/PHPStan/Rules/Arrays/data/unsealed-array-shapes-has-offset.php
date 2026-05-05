<?php

namespace UnsealedArrayShapesHasOffset;

class Foo
{

	/**
	 * @param array{a: int, ...<int, int>} $a
	 * @param array{0: int, ...<int, int>} $b
	 * @param non-decimal-int-string $nonDecimalIntString
	 */
	public function doFoo(array $a, array $b, int $i, string $s, string $nonDecimalIntString): array
	{
		echo $a['a'];
		echo $a[2];
		echo $a[$i];
		echo $a[$s];
		echo $a[$nonDecimalIntString];

		echo $b[0];
		echo $b[1];
		echo $b[$i];
		echo $b[$s];
		echo $b[$nonDecimalIntString];
	}

}
