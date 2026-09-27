<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15283;

use function PHPStan\Testing\assertType;

/** @template T */
class Box {}

class Base
{
	/** @return Box<static> */
	public function box(): Box
	{
		/** @var Box<static> */
		return new Box();
	}

	/** @return static */
	public function me(): static
	{
		return $this;
	}
}

interface PlainMarker {}

/** @phpstan-require-extends Base */
interface TaggedMarker {}

function repro(Base&PlainMarker $plain, Base&TaggedMarker $tagged, TaggedMarker $only): void
{
	assertType('Bug15283\Box<Bug15283\Base&Bug15283\PlainMarker>', $plain->box());
	assertType('Bug15283\Box<Bug15283\Base&Bug15283\TaggedMarker>', $tagged->box());
	assertType('Bug15283\Base&Bug15283\TaggedMarker', $tagged->me());
	assertType('Bug15283\Box<Bug15283\Base&Bug15283\TaggedMarker>', $only->box());
	assertType('Bug15283\Base&Bug15283\TaggedMarker', $only->me());
}

/** @template T */
class GenericBase
{

	/** @var Box<static> */
	public $prop;

	/** @var Box<static> */
	public static $staticProp;

	/** @var T */
	public $t;

	/** @return $this */
	public function fluent(): static
	{
		return $this;
	}

	public static function create(): static
	{
		return new static();
	}

	/** @return Box<static> */
	public static function staticBox(): Box
	{
		return new Box();
	}

	/** @return T */
	public function getT()
	{
		return $this->t;
	}

}

/** @phpstan-require-extends GenericBase<int> */
interface GenericTaggedMarker {}

interface SubMarker extends GenericTaggedMarker {}

/**
 * @param GenericBase<int>&GenericTaggedMarker $tagged
 */
function repro2(GenericBase&GenericTaggedMarker $tagged, GenericTaggedMarker $only, SubMarker $sub): void
{
	assertType('Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker', $tagged->fluent());
	assertType('Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker', $only->fluent());
	assertType('Bug15283\GenericBase<int>&Bug15283\SubMarker', $sub->fluent());
	assertType('Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker', $only::create());
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $only::staticBox());
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $tagged::staticBox());
	assertType('int', $only->getT());
	assertType('int', $sub->getT());
	assertType('int', $only->t);

	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $only->prop);
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $tagged->prop);
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\SubMarker>', $sub->prop);
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $only::$staticProp);
	assertType('Bug15283\Box<Bug15283\GenericBase<int>&Bug15283\GenericTaggedMarker>', $tagged::$staticProp);
}
