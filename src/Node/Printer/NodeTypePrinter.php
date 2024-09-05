<?php declare(strict_types = 1);

namespace PHPStan\Node\Printer;

use PhpParser\Node;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function implode;

final class NodeTypePrinter
{

	public static function printType(Node\Name|Node\Identifier|Node\ComplexType|null $type): ?string
	{
		if ($type === null) {
			return null;
		}

		if ($type instanceof Node\NullableType) {
			return '?' . self::printType($type->type);
		}

		if ($type instanceof Node\UnionType) {
			return implode('|', array_map(static function ($innerType): string {
				$printedType = self::printType($innerType);
				if ($printedType === null) {
					throw new ShouldNotHappenException();
				}

				return $printedType;
			}, $type->types));
		}

		if ($type instanceof Node\IntersectionType) {
			return implode('&', array_map(static function ($innerType): string {
				$printedType = self::printType($innerType);
				if ($printedType === null) {
					throw new ShouldNotHappenException();
				}

				return $printedType;
			}, $type->types));
		}

		if ($type instanceof Node\Identifier || $type instanceof Node\Name) {
			return $type->toString();
		}

		throw new ShouldNotHappenException();
	}

}
