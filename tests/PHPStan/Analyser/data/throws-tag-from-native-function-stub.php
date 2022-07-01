<?php

namespace ThrowsTagFromNativeFunction;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	/** @param resource $stream */
	public function doFoo($stream): void
	{
		try {
			$bool = gzclose($stream);
		} finally {
			assertVariableCertainty(TrinaryLogic::createYes(), $bool);
		}
	}

}
