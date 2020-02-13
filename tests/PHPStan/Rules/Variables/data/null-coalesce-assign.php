<?php // lint >= 7.4

namespace CoalesceAssignRule;

class FooCoalesce
{
	/** @var string|null */
	public static $staticStringOrNull = null;

	/** @var string */
	public static $staticString = '';

	/** @var null */
	public static $staticAlwaysNull;

	/** @var string|null */
	public $stringOrNull = null;

	/** @var string */
	public $string = '';

	/** @var null */
	public $alwaysNull;

	/** @var FooCoalesce|null */
	public $fooCoalesceOrNull;

	/** @var FooCoalesce */
	public $fooCoalesce;

	public function thisCoalesce() {
		echo $this->string ??= null;
	}
}

function coalesce()
{

	$scalar = 3;

	echo $scalar ??= 4;

	$array = [1, 2, 3];

	echo $array['string'] ??= 0;

	$multiDimArray = [[1], [2], [3]];

	echo $multiDimArray['string'] ??= 0;

	echo $doesNotExist ??= 0;

	if (rand() > 0.5) {
		$maybeVariable = 3;
	}

	echo $maybeVariable ??= 0;

	$fixedDimArray = [
		'dim' => 1,
		'dim-null' => rand() > 0.5 ? null : 1,
		'dim-null-offset' => ['a' => rand() > 0.5 ? true : null],
		'dim-empty' => []
	];

	// Always set
	echo $fixedDimArray['dim'] ??= 0;

	// Maybe set
	echo $fixedDimArray['dim-null'] ??= 0;

	// Never set, then unknown
	echo $fixedDimArray['dim-null-not-set']['a'] ??= 0;

	// Always set, then always set
	echo $fixedDimArray['dim-null-offset']['a'] ??= 0;

	// Always set, then never set
	echo $fixedDimArray['dim-empty']['b'] ??= 0;

//	echo rand() ??= 0; // not valid for assignment

//	echo preg_replace('', '', '') ??= 0; // not valid for assignment

	$foo = new FooCoalesce();

	echo $foo->stringOrNull ??= '';

	echo $foo->string ??= '';

	echo $foo->alwaysNull ??= '';

	echo $foo->fooCoalesce->string ??= '';

	echo $foo->fooCoalesceOrNull->string ??= '';

	echo FooCoalesce::$staticStringOrNull ??= '';

	echo FooCoalesce::$staticString ??= '';

	echo FooCoalesce::$staticAlwaysNull ??= '';
}

/**
 * @param array<string, int> $array
 */
function coalesceStringOffset(array $array)
{
	echo $array['string'] ??= 0;
}

function alwaysNullCoalesce (?string $a): void
{
	if (!is_string($a)) {
		echo $a ??= 'foo';
	}
}
