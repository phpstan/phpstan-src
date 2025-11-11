<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

#[AutowiredService]
final class UnresolvableTypeHelper
{

	public function containsUnresolvableType(Type $type): bool
	{
		$containsUnresolvable = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$containsUnresolvable): Type {
			if ($type instanceof ErrorType) {
				$containsUnresolvable = true;
				return $type;
			}
			if ($type->isNever()->yes() && $type->isExplicitNever()->no()) {
				$containsUnresolvable = true;
				return $type;
			}

			return $traverse($type);
		});

		return $containsUnresolvable;
	}

}
