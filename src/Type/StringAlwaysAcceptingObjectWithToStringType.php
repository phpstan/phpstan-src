<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Turbo\CompiledIntoTurboExtension;

#[CompiledIntoTurboExtension(new: ['pt_string_always_accepting_object_with_to_string_type_new', 'out'])]
class StringAlwaysAcceptingObjectWithToStringType extends StringType
{

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::isSuperTypeOf($type);
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

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		$thatClassNames = $type->getObjectClassNames();
		if ($thatClassNames === []) {
			return parent::accepts($type, $strictTypes);
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
