<?php // lint >= 8.1

declare(strict_types = 1);

namespace GenericBackedEnumAcceptance;

use BackedEnum;

enum StringEnum: string
{

	case A = 'a';

}

enum IntEnum: int
{

	case One = 1;

}

interface HasLabel extends BackedEnum
{

}

/**
 * @extends BackedEnum<string>
 */
interface StringBackedInterface extends BackedEnum
{

}

enum ViaInterface: string implements HasLabel
{

	case A = 'a';

}

enum ViaStringInterface: string implements StringBackedInterface
{

	case A = 'a';

}

/**
 * @param BackedEnum<string> $e
 */
function acceptsStringBacked(BackedEnum $e): void
{
}

function test(StringEnum $a, ViaInterface $b, ViaStringInterface $c, IntEnum $d, HasLabel $e): void
{
	acceptsStringBacked($a);
	acceptsStringBacked($b);
	acceptsStringBacked($c);
	acceptsStringBacked($d);
	acceptsStringBacked($e);
}
