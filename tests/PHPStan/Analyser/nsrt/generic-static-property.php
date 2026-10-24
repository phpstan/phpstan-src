<?php declare(strict_types = 1);

namespace GenericStaticProperty;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Box
{

	/** @var static<int, string> */
	public $prop;

	/** @var static<int, TValue> */
	public $withTemplate;

	/** @var static<int, string> */
	public static $staticProp;

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return [];
	}

}

/**
 * @template TKey of array-key
 * @template TValue
 */
final class FinalBox
{

	/** @var static<int, string> */
	public $prop;

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return [];
	}

}

/**
 * @param Box<string, bool> $box
 * @param FinalBox<string, bool> $finalBox
 */
function test(Box $box, FinalBox $finalBox): void
{
	assertType('GenericStaticProperty\Box<int, string>', $box->prop);
	assertType('array<int, string>', $box->prop->all());
	assertType('array<int, bool>', $box->withTemplate->all());
	assertType('array<int, string>', Box::$staticProp->all());
	assertType('array<int, string>', $box::$staticProp->all());
	assertType('GenericStaticProperty\FinalBox<int, string>', $finalBox->prop);
	assertType('array<int, string>', $finalBox->prop->all());
}
