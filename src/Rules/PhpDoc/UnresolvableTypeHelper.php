<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_values;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/UnresolvableTypeHelper.cpp')]
final class UnresolvableTypeHelper
{

	public function getUnresolvableType(Type $type): ?UnresolvableTypeResult
	{
		$containsUnresolvable = false;
		$reasons = [];
		TypeTraverser::mapMemoized($type, static function (Type $type, callable $traverse) use (&$containsUnresolvable, &$reasons): Type {
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
				$reasons[$reason] = $reason;
			}

			return $containsUnresolvable ? $type : $traverse($type);
		});

		if (!$containsUnresolvable) {
			return null;
		}

		return new UnresolvableTypeResult(array_values($reasons));
	}

}
