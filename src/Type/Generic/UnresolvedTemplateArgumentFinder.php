<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeTraverserCallable;

/** Finds an UnresolvedTemplateArgumentType anywhere in a type. */
#[ReferencedByTurboExtension(key: 'unresolvedTemplateArgumentFinder')]
final class UnresolvedTemplateArgumentFinder implements TypeTraverserCallable
{

	public bool $found = false;

	public static function contains(Type $type): bool
	{
		$finder = new self();
		TypeTraverser::map($type, $finder);

		return $finder->found;
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if ($this->found) {
			return $type;
		}

		if ($type instanceof UnresolvedTemplateArgumentType) {
			$this->found = true;
			return $type;
		}

		return $traverse($type);
	}

}
