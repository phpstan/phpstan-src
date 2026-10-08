<?php // lint >= 8.1

namespace SortWithoutEffectInTraitEnum;

trait EnumUtils
{

	/** @return list<static> */
	public static function sortedCases(): array
	{
		$cases = self::cases();
		// self::cases() is a one-element array in SingleCase and a two-element one in ManyCases:
		// generic trait code that only looks pointless in one of its consumers
		usort($cases, static fn (self $a, self $b): int => strcasecmp($a->name, $b->name));

		return $cases;
	}

}

enum SingleCase: string
{

	use EnumUtils;

	case ONLY = 'only';

}

enum ManyCases: string
{

	use EnumUtils;

	case A = 'a';
	case B = 'b';

}

trait EmptyEnumUtils
{

	/** @return list<static> */
	public static function sortedCases(): array
	{
		$cases = self::cases();
		usort($cases, static fn (self $a, self $b): int => strcasecmp($a->name, $b->name));

		return $cases;
	}

}

enum NoCases
{

	use EmptyEnumUtils;

}

enum SomeCases
{

	use EmptyEnumUtils;

	case A;
	case B;

}
