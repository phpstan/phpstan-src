<?php declare(strict_types = 1);

namespace PHPStanTurboTests;

/**
 * Class constants of every shape InitializerExprTypeResolver's class
 * constant fetch distinguishes: untyped / typed / PHPDoc-typed / final
 * constants of a non-final class, a final class, an enum's cases and
 * constants, a constant referencing itself through another one, and a trait
 * constant.
 */
class InitializerParent
{

	public const PARENT_CONST = 'parent';

	public const OVERRIDDEN = 1;

}

class InitializerOpen extends InitializerParent
{

	public const UNTYPED = 1;

	public const int TYPED = 2;

	/** @var non-empty-string */
	public const DOCUMENTED = 'doc';

	final public const FINAL_CONST = [1, 2];

	public const SELF_REF = self::UNTYPED + 10;

	public const STATIC_LIST = [self::UNTYPED, self::TYPED, parent::PARENT_CONST];

	public const CYCLE_A = self::CYCLE_B;

	public const CYCLE_B = self::CYCLE_A;

	public const EXPR = 1 << 3 | 5 & 3;

	public const STR = 'a' . 'b' . __CLASS__;

	public const OVERRIDDEN = 2;

}

final class InitializerFinal extends InitializerParent
{

	public const UNTYPED = 'final';

	public const NESTED = InitializerOpen::SELF_REF * 2;

	public const ENUM_CASE = InitializerEnum::Two;

	public const CLASS_NAME = self::class;

	public const PARENT_NAME = parent::class;

}

enum InitializerEnum: int
{

	case One = 1;
	case Two = 2;

	public const ALIAS = self::One;

}

trait InitializerTrait
{

	public const TRAIT_CONST = __TRAIT__;

}
