<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[ReferencedByTurboExtension(key: 'typeProjectionHelper')]
final class TypeProjectionHelper
{

	public static function describe(
		Type $type,
		?TemplateTypeVariance $variance,
		VerbosityLevel $level,
	): string
	{
		$describedType = $type->describe($level);

		if ($variance === null || $variance->invariant()) {
			return $describedType;
		}

		if ($variance->bivariant()) {
			return '*';
		}

		return sprintf('%s %s', $variance->describe(), $describedType);
	}

}
