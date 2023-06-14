<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;

class StringAlwaysAcceptingObjectWithToStringType extends StringType
{

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::isSuperTypeOf($type);
		}

		$result = TrinaryLogic::createNo();
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($thatClassNames as $thatClassName) {
			if (!$reflectionProvider->hasClass($thatClassName)) {
				return TrinaryLogic::createNo();
			}

			$typeClass = $reflectionProvider->getClass($thatClassName);
			$result = $result->or(TrinaryLogic::createFromBoolean($typeClass->hasNativeMethod('__toString')));
		}

		return $result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::acceptsWithReason($type, $strictTypes);
		}

		$result = AcceptsResult::createNo();
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($thatClassNames as $thatClassName) {
			if (!$reflectionProvider->hasClass($thatClassName)) {
				return AcceptsResult::createNo();
			}

			$typeClass = $reflectionProvider->getClass($thatClassName);
			$result = $result->or(AcceptsResult::createFromBoolean($typeClass->hasNativeMethod('__toString')));
		}

		return $result;
	}

}
