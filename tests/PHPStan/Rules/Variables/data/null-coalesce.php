<?php

namespace CoalesceRule;

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
		echo $this->string ?? null;
	}
}

function coalesce()
{

	$scalar = 3;

	echo $scalar ?? 4;

	$array = [1, 2, 3];

	echo $array['string'] ?? 0;

	$multiDimArray = [[1], [2], [3]];

	echo $multiDimArray['string'] ?? 0;

	echo $doesNotExist ?? 0;

	if (rand() > 0.5) {
		$maybeVariable = 3;
	}

	echo $maybeVariable ?? 0;

	$fixedDimArray = [
		'dim' => 1,
		'dim-null' => rand() > 0.5 ? null : 1,
		'dim-null-offset' => ['a' => rand() > 0.5 ? true : null],
		'dim-empty' => []
	];

	// Always set
	echo $fixedDimArray['dim'] ?? 0;

	// Maybe set
	echo $fixedDimArray['dim-null'] ?? 0;

	// Never set, then unknown
	echo $fixedDimArray['dim-null-not-set']['a'] ?? 0;

	// Always set, then always set
	echo $fixedDimArray['dim-null-offset']['a'] ?? 0;

	// Always set, then never set
	echo $fixedDimArray['dim-empty']['b'] ?? 0;

	echo rand() ?? 0;

	echo preg_replace('', '', '') ?? 0;

	$foo = new FooCoalesce();

	echo $foo->stringOrNull ?? '';

	echo $foo->string ?? '';

	echo $foo->alwaysNull ?? '';

	echo $foo->fooCoalesce->string ?? '';

	echo $foo->fooCoalesceOrNull->string ?? '';

	echo FooCoalesce::$staticStringOrNull ?? '';

	echo FooCoalesce::$staticString ?? '';

	echo FooCoalesce::$staticAlwaysNull ?? '';
}

/**
 * @param array<string, int> $array
 */
function coalesceStringOffset(array $array)
{
	echo $array['string'] ?? 0;
}

function alwaysNullCoalesce (?string $a): void
{
	if (!is_string($a)) {
		echo $a ?? 'foo';
	}
}

function (): void {
	echo (new FooCoalesce())->string ?? 'foo';
	echo (new FooCoalesce())->stringOrNull ?? 'foo';
	echo (new FooCoalesce())->alwaysNull ?? 'foo';

	(new FooCoalesce()) ?? 'foo';
	null ?? 'foo';
};

function (FooCoalesce $foo): void
{
	echo $foo::$staticAlwaysNull ?? 'foo';
	echo $foo::$staticString ?? 'foo';
	echo $foo::$staticStringOrNull ?? 'foo';
};

function (\ReflectionClass $ref): void {
	echo $ref->name ?? 'foo';
	echo $ref->nonexistent ?? 'bar';
};

function (): void {
	echo $foo ?? 'foo';

	echo $bar->bar ?? 'foo';
};
