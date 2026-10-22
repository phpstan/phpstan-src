<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function sprintf;
use function strtolower;

final class RestrictedInternalPropertyUsageExtension implements RestrictedPropertyUsageExtension
{

	public function __construct(private RestrictedInternalUsageHelper $helper)
	{
	}

	public function isRestrictedPropertyUsage(
		ExtendedPropertyReflection $propertyReflection,
		Scope $scope,
	): ?RestrictedUsage
	{
		$isPropertyInternal = $propertyReflection->isInternal()->yes();
		$declaringClass = $propertyReflection->getDeclaringClass();
		$isDeclaringClassInternal = $declaringClass->isInternal();
		if (!$isPropertyInternal && !$isDeclaringClassInternal) {
			return null;
		}

		if (!$this->helper->shouldClassBeReported($scope, $declaringClass)) {
			return null;
		}

		$namespace = $this->helper->getRootNamespace($declaringClass);
		if ($namespace === null) {
			if (!$isPropertyInternal) {
				return RestrictedUsage::create(
					sprintf(
						'Access to %sproperty $%s of internal %s %s.',
						$propertyReflection->isStatic() ? 'static ' : '',
						$propertyReflection->getName(),
						strtolower($propertyReflection->getDeclaringClass()->getClassTypeDescription()),
						$propertyReflection->getDeclaringClass()->getDisplayName(),
					),
					sprintf(
						'%s.internal%s',
						$propertyReflection->isStatic() ? 'staticProperty' : 'property',
						$propertyReflection->getDeclaringClass()->getClassTypeDescription(),
					),
				);
			}

			return RestrictedUsage::create(
				sprintf(
					'Access to internal %sproperty %s::$%s.',
					$propertyReflection->isStatic() ? 'static ' : '',
					$propertyReflection->getDeclaringClass()->getDisplayName(),
					$propertyReflection->getName(),
				),
				sprintf('%s.internal', $propertyReflection->isStatic() ? 'staticProperty' : 'property'),
			);
		}

		if (!$isPropertyInternal) {
			return RestrictedUsage::create(
				sprintf(
					'Access to %sproperty $%s of internal %s %s from outside its root namespace %s.',
					$propertyReflection->isStatic() ? 'static ' : '',
					$propertyReflection->getName(),
					strtolower($propertyReflection->getDeclaringClass()->getClassTypeDescription()),
					$propertyReflection->getDeclaringClass()->getDisplayName(),
					$namespace,
				),
				sprintf(
					'%s.internal%s',
					$propertyReflection->isStatic() ? 'staticProperty' : 'property',
					$propertyReflection->getDeclaringClass()->getClassTypeDescription(),
				),
			);
		}

		return RestrictedUsage::create(
			sprintf(
				'Access to internal %sproperty %s::$%s from outside its root namespace %s.',
				$propertyReflection->isStatic() ? 'static ' : '',
				$propertyReflection->getDeclaringClass()->getDisplayName(),
				$propertyReflection->getName(),
				$namespace,
			),
			sprintf('%s.internal', $propertyReflection->isStatic() ? 'staticProperty' : 'property'),
		);
	}

}
