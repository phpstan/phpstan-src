<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

class CrossCheckInterfacesHelper
{

	/**
	 * @return RuleError[]
	 */
	public function check(ClassReflection $classReflection): array
	{
		$interfaceTemplateTypeMaps = [];
		$errors = [];
		$check = static function (ClassReflection $classReflection) use (&$interfaceTemplateTypeMaps, &$check, &$errors): void {
			foreach ($classReflection->getInterfaces() as $interface) {
				if (!$interface->isGeneric()) {
					continue;
				}

				if (array_key_exists($interface->getName(), $interfaceTemplateTypeMaps)) {
					$otherMap = $interfaceTemplateTypeMaps[$interface->getName()];
					foreach ($interface->getActiveTemplateTypeMap()->getTypes() as $name => $type) {
						$otherType = $otherMap->getType($name);
						if ($otherType === null) {
							continue;
						}

						if ($type->equals($otherType)) {
							continue;
						}

						$errors[] = RuleErrorBuilder::message(sprintf(
							'%s specifies template type %s of interface %s as %s but it\'s already specified as %s.',
							$classReflection->isInterface() ? sprintf('Interface %s', $classReflection->getName()) : sprintf('Class %s', $classReflection->getName()),
							$name,
							$interface->getName(),
							$type->describe(VerbosityLevel::value()),
							$otherType->describe(VerbosityLevel::value())
						))->build();
					}
					continue;
				}

				$interfaceTemplateTypeMaps[$interface->getName()] = $interface->getActiveTemplateTypeMap();
			}

			$parent = $classReflection->getParentClass();
			while ($parent !== false) {
				$check($parent);
				$parent = $parent->getParentClass();
			}

			foreach ($classReflection->getInterfaces() as $interface) {
				$check($interface);
			}
		};

		$check($classReflection);

		return $errors;
	}

}
