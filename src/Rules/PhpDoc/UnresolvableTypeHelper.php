<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_unique;
use function array_values;

#[AutowiredService]
final class UnresolvableTypeHelper
{

	public function getUnresolvableType(Type $type): ?UnresolvableTypeResult
	{
		$containsUnresolvable = false;
		$reasons = [];
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$containsUnresolvable, &$reasons): Type {
			$reason = null;
			if ($type instanceof ErrorType) {
				$containsUnresolvable = true;
				$reason = $type->getReason();
			}
			if ($type instanceof NeverType && !$type->isExplicit()) {
				$containsUnresolvable = true;
				$reason = $type->getReason();
			}

			if ($reason !== null) {
				$reasons[] = $reason;
			}

			return $containsUnresolvable ? $type : $traverse($type);
		});

		if (!$containsUnresolvable) {
			return null;
		}

		return new UnresolvableTypeResult(array_values(array_unique($reasons)));
	}

}
