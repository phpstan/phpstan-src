<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;

class StringAlwaysAcceptingObjectWithToStringType extends StringType
{

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->isSuperTypeOfWithReason($type)->result;
	}

	public function isSuperTypeOfWithReason(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOfWithReason($this);
		}

		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::isSuperTypeOfWithReason($type);
		}

		$result = IsSuperTypeOfResult::createNo();
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		foreach ($thatClassNames as $thatClassName) {
			if (!$reflectionProvider->hasClass($thatClassName)) {
				return IsSuperTypeOfResult::createNo();
			}

			$typeClass = $reflectionProvider->getClass($thatClassName);
			$result = $result->or(IsSuperTypeOfResult::createFromBoolean($typeClass->hasNativeMethod('__toString')));
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
