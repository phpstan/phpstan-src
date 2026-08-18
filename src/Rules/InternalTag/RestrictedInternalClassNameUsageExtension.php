<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function sprintf;
use function strtolower;

final class RestrictedInternalClassNameUsageExtension implements RestrictedClassNameUsageExtension
{

	public function __construct(
		private RestrictedInternalUsageHelper $helper,
	)
	{
	}

	public function isRestrictedClassNameUsage(
		ClassReflection $classReflection,
		Scope $scope,
		ClassNameUsageLocation $location,
	): ?RestrictedUsage
	{
		if (!$classReflection->isInternal()) {
			return null;
		}

		if (!$this->helper->shouldClassBeReported($scope, $classReflection)) {
			return null;
		}

		if ($location->value === ClassNameUsageLocation::STATIC_METHOD_CALL) {
			$method = $location->getMethod();
			if ($method !== null) {
				if ($method->isInternal()->yes() || $method->getDeclaringClass()->isInternal()) {
					return null;
				}
			}
		}

		if ($location->value === ClassNameUsageLocation::STATIC_PROPERTY_ACCESS) {
			$property = $location->getProperty();
			if ($property !== null) {
				if ($property->isInternal()->yes() || $property->getDeclaringClass()->isInternal()) {
					return null;
				}
			}
		}

		if ($location->value === ClassNameUsageLocation::CLASS_CONSTANT_ACCESS) {
			$constant = $location->getClassConstant();
			if ($constant !== null) {
				if ($constant->isInternal()->yes() || $constant->getDeclaringClass()->isInternal()) {
					return null;
				}
			}
		}

		return RestrictedUsage::create(
			$location->createMessage(sprintf('internal %s %s', strtolower($classReflection->getClassTypeDescription()), $classReflection->getDisplayName())),
			$location->createIdentifier(sprintf('internal%s', $classReflection->getClassTypeDescription())),
		);
	}

}
