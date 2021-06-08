<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class UnresolvableTypeHelper
{

	private bool $deepInspectTypes;

	public function __construct(bool $deepInspectTypes)
	{
		$this->deepInspectTypes = $deepInspectTypes;
	}

	public function containsUnresolvableType(Type $type): bool
	{
		if ($this->deepInspectTypes) {
			$containsUnresolvable = false;
			TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$containsUnresolvable): Type {
				if ($type instanceof ErrorType) {
					$containsUnresolvable = true;
					return $type;
				}
				if ($type instanceof NeverType && !$type->isExplicit()) {
					$containsUnresolvable = true;
					return $type;
				}

				return $traverse($type);
			});

			return $containsUnresolvable;
		}

		if ($type instanceof ErrorType) {
			return true;
		}

		return $type instanceof NeverType && !$type->isExplicit();
	}

}
