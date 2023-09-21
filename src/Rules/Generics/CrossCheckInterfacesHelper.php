<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function sprintf;

class CrossCheckInterfacesHelper
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(ClassReflection $classReflection): array
	{
		$interfaceTemplateTypeMaps = [];
		$errors = [];
		$check = static function (ClassReflection $classReflection, bool $first) use (&$interfaceTemplateTypeMaps, &$check, &$errors): void {
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
							$otherType->describe(VerbosityLevel::value()),
						))->identifier('generics.interfaceConflict')->build();
					}
					continue;
				}

				$interfaceTemplateTypeMaps[$interface->getName()] = $interface->getActiveTemplateTypeMap();
			}

			$parent = $classReflection->getParentClass();
			$checkParents = true;
			if ($first && $parent !== null) {
				$extendsTags = $classReflection->getExtendsTags();
				if (!array_key_exists($parent->getName(), $extendsTags)) {
					$checkParents = false;
				}
			}

			if ($checkParents) {
				while ($parent !== null) {
					$check($parent, false);
					$parent = $parent->getParentClass();
				}
			}

			$interfaceTags = [];
			if ($first) {
				if ($classReflection->isInterface()) {
					$interfaceTags = $classReflection->getExtendsTags();
				} else {
					$interfaceTags = $classReflection->getImplementsTags();
				}
			}
			foreach ($classReflection->getInterfaces() as $interface) {
				if ($first) {
					if (!array_key_exists($interface->getName(), $interfaceTags)) {
						continue;
					}
				}
				$check($interface, false);
			}
		};

		$check($classReflection, true);

		return $errors;
	}

}
