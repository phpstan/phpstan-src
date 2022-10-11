<?php // lint >= 7.4

declare(strict_types=1);

namespace Bug7240;

/**
 * @phpstan-type TypeArrayMinMax    array{min:int,max:int}
 * @phpstan-type TypeArrayMinMaxSet array<string,TypeArrayMinMax>
 */
class A
{
	/** @var TypeArrayMinMaxSet */
	protected array $var;
}

class B extends A
{
	protected array $var = ["year" => ["min" => 1990, "max" => 2200]];
}

class AbstractC extends A
{
}

class CBroken extends AbstractC
{
	protected array $var = ["year" => ["min" => 1990, "max" => 2200]];
}

/** @phpstan-import-type TypeArrayMinMaxSet from A */
class CWorks extends AbstractC
{
	/** @var TypeArrayMinMaxSet */
	protected array $var = ["year" => ["min" => 1990, "max" => 2200]];
}
