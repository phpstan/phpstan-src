<?php declare(strict_types=1);

namespace Bug3297;

class Foo
{
	/** @param array{opt?: string} $param */
	public function bar(array $param): void
	{
		if (!empty($param['opt'])) {
			echo $param['opt'];
		}
	}
}
