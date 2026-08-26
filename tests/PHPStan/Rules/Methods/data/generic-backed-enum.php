<?php // lint >= 8.1

declare(strict_types = 1);

namespace GenericBackedEnumStaticCall;

use BackedEnum;

enum StringEnum: string
{

	case A = 'a';

}

/**
 * @param BackedEnum<int> $intBacked
 * @param BackedEnum<string> $stringBacked
 */
function backedEnumInterface(BackedEnum $intBacked, BackedEnum $stringBacked, BackedEnum $bare, int $i, string $s): void
{
	$intBacked::from($i);
	$intBacked::from($s);
	$stringBacked::tryFrom($i);
	$stringBacked::tryFrom($s);
	$bare::from($i);
	$bare::from($s);
}

/**
 * @template T of BackedEnum<string>
 * @param class-string<T> $className
 */
function stringBackedClassString(string $className, int $i, string $s): void
{
	$className::from($i);
	$className::from($s);
}

function concreteEnum(string $s): void
{
	StringEnum::from($s);
	StringEnum::tryFrom($s);
}
