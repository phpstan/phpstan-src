<?php declare(strict_types = 1);

namespace ReturnTypeAfterFinally;

function &byRefChangedInFinally(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 'test';
	}
}

function byValueChangedInFinally(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 'test';
	}
}

function &byRefUnchangedInFinally(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 1;
	}
}

function &byRefUnsetInFinally(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		unset($x);
	}
}

function &byRefIncrementedInFinally(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x .= 'foo';
	}
}

function &byRefStaticVariable(): int
{
	static $x = 0;
	try {
		return $x;
	} finally {
		$x = 'test';
	}
}

/** @return positive-int */
function &byRefPhpDocReturnType(): int
{
	$x = 1;
	try {
		return $x;
	} finally {
		$x = -5;
	}
}

function &byRefReturnFromCatch(): int
{
	$x = 0;
	try {
		throw new \Exception();
	} catch (\Exception $e) {
		return $x;
	} finally {
		$x = 'test';
	}
}

function &byRefNestedFinallyBothBroken(): int
{
	$x = 0;
	try {
		try {
			return $x;
		} finally {
			$x = 1.0;
		}
	} finally {
		$x = 'test';
	}
}

function &byRefNestedFinallyFixedByOuter(): int
{
	$x = 0;
	try {
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	} finally {
		$x = 5;
	}
}

function &byRefNestedOuterFinallyUnrelated(): int
{
	$x = 0;
	try {
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	} finally {
		$y = 1;
	}
}

function &byRefAlreadyWrongAtReturn(): int
{
	$x = 'test';
	try {
		return $x;
	} finally {
		$x = 'test2';
	}
}

function &byRefFinallyAlwaysTerminates(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 'test';
		return 1;
	}
}

class Foo
{

	/** @var int|string */
	public $prop = 1;

	public static int $staticProp = 1;

	public function &byRefMethod(): int
	{
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	}

	public static function &byRefStaticMethod(): int
	{
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	}

	public function &byRefProperty(): int
	{
		if (!is_int($this->prop)) {
			throw new \Exception();
		}
		try {
			return $this->prop;
		} finally {
			$this->prop = 'test';
		}
	}

	public static function &byRefStaticProperty(): int
	{
		try {
			return self::$staticProp;
		} finally {
			self::$staticProp = 5;
		}
	}

}

function testClosures(): void
{
	$byRef = function &(): int {
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	};

	$byValue = function (): int {
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 'test';
		}
	};
}

/** @return \Generator<int, int, mixed, int> */
function &byRefGenerator(): \Generator
{
	$x = 0;
	yield 1;
	try {
		return $x;
	} finally {
		$x = 'test';
	}
}

function &byRefArrayOffset(): int
{
	$arr = ['a' => 0];
	try {
		return $arr['a'];
	} finally {
		$arr['a'] = 'test';
	}
}
