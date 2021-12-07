<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function array_values;
use function count;
use function implode;
use function sprintf;

class GenericObjectTypeCheck
{

	/**
	 * @return RuleError[]
	 */
	public function check(
		Type $phpDocType,
		string $classNotGenericMessage,
		string $notEnoughTypesMessage,
		string $extraTypesMessage,
		string $typeIsNotSubtypeMessage
	): array
	{
		$genericTypes = $this->getGenericTypes($phpDocType);
		$messages = [];
		foreach ($genericTypes as $genericType) {
			$classReflection = $genericType->getClassReflection();
			if ($classReflection === null) {
				continue;
			}
			if (!$classReflection->isGeneric()) {
				$messages[] = RuleErrorBuilder::message(sprintf($classNotGenericMessage, $genericType->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName()))->build();
				continue;
			}

			$templateTypes = array_values($classReflection->getTemplateTypeMap()->getTypes());

			$genericTypeTypes = $genericType->getTypes();
			$templateTypesCount = count($templateTypes);
			$genericTypeTypesCount = count($genericTypeTypes);
			if ($templateTypesCount > $genericTypeTypesCount) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$notEnoughTypesMessage,
					$genericType->describe(VerbosityLevel::typeOnly()),
					$classReflection->getDisplayName(false),
					implode(', ', array_keys($classReflection->getTemplateTypeMap()->getTypes()))
				))->build();
			} elseif ($templateTypesCount < $genericTypeTypesCount) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$extraTypesMessage,
					$genericType->describe(VerbosityLevel::typeOnly()),
					$genericTypeTypesCount,
					$classReflection->getDisplayName(false),
					$templateTypesCount,
					implode(', ', array_keys($classReflection->getTemplateTypeMap()->getTypes()))
				))->build();
			}

			$templateTypesCount = count($templateTypes);
			for ($i = 0; $i < $templateTypesCount; $i++) {
				if (!isset($genericTypeTypes[$i])) {
					continue;
				}

				$templateType = $templateTypes[$i];
				$boundType = TemplateTypeHelper::resolveToBounds($templateType);
				$genericTypeType = $genericTypeTypes[$i];
				if ($boundType->isSuperTypeOf($genericTypeType)->yes()) {
					if (!$templateType instanceof TemplateType) {
						continue;
					}
					$map = $templateType->inferTemplateTypes($genericTypeType);
					for ($j = 0; $j < $templateTypesCount; $j++) {
						if ($i === $j) {
							continue;
						}

						$templateTypes[$j] = TemplateTypeHelper::resolveTemplateTypes($templateTypes[$j], $map);
					}
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$typeIsNotSubtypeMessage,
					$genericTypeType->describe(VerbosityLevel::typeOnly()),
					$genericType->describe(VerbosityLevel::typeOnly()),
					$templateType->describe(VerbosityLevel::typeOnly()),
					$classReflection->getDisplayName(false)
				))->build();
			}
		}

		return $messages;
	}

	/**
	 * @return GenericObjectType[]
	 */
	private function getGenericTypes(Type $phpDocType): array
	{
		$genericObjectTypes = [];
		TypeTraverser::map($phpDocType, static function (Type $type, callable $traverse) use (&$genericObjectTypes): Type {
			if ($type instanceof GenericObjectType) {
				$resolvedType = TemplateTypeHelper::resolveToBounds($type);
				if (!$resolvedType instanceof GenericObjectType) {
					throw new ShouldNotHappenException();
				}
				$genericObjectTypes[] = $resolvedType;
				$traverse($type);
				return $type;
			}
			$traverse($type);
			return $type;
		});

		return $genericObjectTypes;
	}

}
