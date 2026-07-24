<?php // lint >= 8.1

namespace ClassLevelUseTag;

use function PHPStan\Testing\assertType;

/** @template T */
trait GenericTrait
{

	/** @var T */
	private $value;

	/** @return T */
	public function get()
	{
		return $this->value;
	}

}

/** @template TOther */
trait OtherGenericTrait
{

	/** @return TOther */
	public function getOther()
	{
		throw new \Exception();
	}

}

/**
 * @use GenericTrait<int>
 */
class ClassLevelUse
{

	use GenericTrait;

}

/**
 * @phpstan-use GenericTrait<string>
 */
class PrefixedClassLevelUse
{

	use GenericTrait;

}

class NoUseTag
{

	use GenericTrait;

}

/**
 * @use GenericTrait<int>
 */
class StatementLevelWins
{

	/** @use GenericTrait<string> */
	use GenericTrait;

}

/**
 * @use GenericTrait<int>
 * @use OtherGenericTrait<string>
 */
class MultipleTraits
{

	use GenericTrait;
	use OtherGenericTrait;

}

/**
 * @template T
 * @use GenericTrait<T>
 */
class ForwardedTemplate
{

	use GenericTrait;

}

/**
 * @template TNested
 * @use GenericTrait<TNested>
 */
trait NestedTrait
{

	use GenericTrait;

}

/**
 * @use NestedTrait<bool>
 */
class UsesNestedTrait
{

	use NestedTrait;

}

/**
 * @use OtherGenericTrait<int>
 */
enum SomeEnum
{

	use OtherGenericTrait;

}

/**
 * @param ForwardedTemplate<\DateTimeImmutable> $forwarded
 */
function test(
	ClassLevelUse $a,
	PrefixedClassLevelUse $b,
	NoUseTag $c,
	StatementLevelWins $d,
	MultipleTraits $e,
	ForwardedTemplate $forwarded,
	UsesNestedTrait $f,
	SomeEnum $g,
): void {
	assertType('int', $a->get());
	assertType('string', $b->get());
	assertType('mixed', $c->get());
	assertType('string', $d->get());
	assertType('int', $e->get());
	assertType('string', $e->getOther());
	assertType('DateTimeImmutable', $forwarded->get());
	assertType('bool', $f->get());
	assertType('int', $g->getOther());
}
