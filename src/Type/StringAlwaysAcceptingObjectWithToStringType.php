<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;

class StringAlwaysAcceptingObjectWithToStringType extends StringType
{

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::accepts($type, $strictTypes);
		}

		return TrinaryLogic::createNo()->lazyOr(
			$thatClassNames,
			static function (string $thatClassName) {
				$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
				if (!$reflectionProvider->hasClass($thatClassName)) {
					return TrinaryLogic::createNo();
				}

				$typeClass = $reflectionProvider->getClass($thatClassName);
				return TrinaryLogic::createFromBoolean(
					$typeClass->hasNativeMethod('__toString'),
				);
			},
		);
	}

}
