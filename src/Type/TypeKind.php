<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * Bits of Type::getKindMask(): the kinds of values PHP tells apart by their
 * type. A value is of exactly one kind, so two types whose masks share no bit
 * share no value: their union cannot merge them into one member and their
 * intersection is never.
 *
 * @api
 */
final class TypeKind
{

	public const NULL = 1;
	public const FALSE = 2;
	public const TRUE = 4;
	public const INT = 8;
	public const FLOAT = 16;
	public const STRING = 32;
	public const ARRAY = 64;
	public const OBJECT = 128;
	public const RESOURCE = 256;

	public const BOOL = self::FALSE | self::TRUE;
	public const ALL = self::NULL | self::BOOL | self::INT | self::FLOAT | self::STRING | self::ARRAY | self::OBJECT | self::RESOURCE;

	/**
	 * Whether no value is of both masks' kinds. A mask without kinds, as of
	 * never, overlaps with every mask: a type of no values is a subtype of
	 * any other, which union() and intersect() still have to see.
	 */
	public static function areDisjoint(int $a, int $b): bool
	{
		return ($a & $b) === 0 && $a !== 0 && $b !== 0;
	}

}
