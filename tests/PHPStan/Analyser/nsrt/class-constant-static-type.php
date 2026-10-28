<?php declare(strict_types = 1);

namespace ClassConstantStaticType;

use function PHPStan\Testing\assertType;

class Base
{

	/** @var class-string<static> */
	const IMPL = Base::class;

	/** @var list<class-string<static>> */
	const IMPLS = [Base::class];

	public function inBase(): void
	{
		assertType('class-string<static(ClassConstantStaticType\Base)>', static::IMPL);
		assertType('class-string<static(ClassConstantStaticType\Base)>', $this::IMPL);
		assertType('\'ClassConstantStaticType\\\\Base\'', self::IMPL);
	}

}

class Child extends Base
{

	public function inChild(): void
	{
		assertType('class-string<static(ClassConstantStaticType\Child)>', static::IMPL);
		assertType('list<class-string<static(ClassConstantStaticType\Child)>>', static::IMPLS);
		assertType('class-string<static(ClassConstantStaticType\Child)>', $this::IMPL);
	}

}

/**
 * @template T
 */
class GenericBase
{

	/** @var class-string<static<int>> */
	const IMPL = GenericBase::class; // @phpstan-ignore classConstant.phpDocType

}

/** @extends GenericBase<string> */
class GenericChild extends GenericBase
{

}

/**
 * @param GenericBase<string> $genericBase
 */
function test(Base $base, Child $child, GenericBase $genericBase, GenericChild $genericChild): void
{
	assertType('class-string<ClassConstantStaticType\Base>', $base::IMPL);
	assertType('class-string<ClassConstantStaticType\Child>', $child::IMPL);
	assertType('list<class-string<ClassConstantStaticType\Child>>', $child::IMPLS);
	assertType('class-string<ClassConstantStaticType\GenericBase<int>>', $genericBase::IMPL);
	assertType('class-string<ClassConstantStaticType\GenericChild>', $genericChild::IMPL);
}
