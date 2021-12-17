<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;

class StringAlwaysAcceptingObjectWithToStringType extends StringType
{

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof TypeWithClassName) {
			$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
			if (!$reflectionProvider->hasClass($type->getClassName())) {
				return TrinaryLogic::createNo();
			}

			$typeClass = $reflectionProvider->getClass($type->getClassName());
			return TrinaryLogic::createFromBoolean(
				$typeClass->hasNativeMethod('__toString'),
			);
		}

		return parent::accepts($type, $strictTypes);
	}

}
