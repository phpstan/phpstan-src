<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Generic\TypeProjectionHelper;
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
		string $typeIsNotSubtypeMessage,
		string $typeProjectionHasConflictingVarianceMessage,
		string $typeProjectionIsRedundantMessage,
	): array
	{
		$genericTypes = $this->getGenericTypes($phpDocType);
		$messages = [];
		foreach ($genericTypes as $genericType) {
			$classReflection = $genericType->getClassReflection();
			if ($classReflection === null) {
				continue;
			}

			$classLikeDescription = 'class';
			if ($classReflection->isInterface()) {
				$classLikeDescription = 'interface';
			} elseif ($classReflection->isTrait()) {
				$classLikeDescription = 'trait';
			} elseif ($classReflection->isEnum()) {
				$classLikeDescription = 'enum';
			}
			if (!$classReflection->isGeneric()) {
				$messages[] = RuleErrorBuilder::message(sprintf($classNotGenericMessage, $genericType->describe(VerbosityLevel::typeOnly()), $classLikeDescription, $classReflection->getDisplayName()))->build();
				continue;
			}

			$templateTypes = array_values($classReflection->getTemplateTypeMap()->getTypes());

			$genericTypeTypes = $genericType->getTypes();
			$genericTypeVariances = $genericType->getVariances();
			$templateTypesCount = count($templateTypes);
			$genericTypeTypesCount = count($genericTypeTypes);
			if ($templateTypesCount > $genericTypeTypesCount) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$notEnoughTypesMessage,
					$genericType->describe(VerbosityLevel::typeOnly()),
					$classLikeDescription,
					$classReflection->getDisplayName(false),
					implode(', ', array_keys($classReflection->getTemplateTypeMap()->getTypes())),
				))->build();
			} elseif ($templateTypesCount < $genericTypeTypesCount) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$extraTypesMessage,
					$genericType->describe(VerbosityLevel::typeOnly()),
					$genericTypeTypesCount,
					$classLikeDescription,
					$classReflection->getDisplayName(false),
					$templateTypesCount,
					implode(', ', array_keys($classReflection->getTemplateTypeMap()->getTypes())),
				))->build();
			}

			$templateTypesCount = count($templateTypes);
			for ($i = 0; $i < $templateTypesCount; $i++) {
				if (!isset($genericTypeTypes[$i])) {
					continue;
				}

				$templateType = $templateTypes[$i];
				$genericTypeType = $genericTypeTypes[$i];

				$genericTypeVariance = $genericTypeVariances[$i] ?? TemplateTypeVariance::createInvariant();
				if ($templateType instanceof TemplateType && !$genericTypeVariance->invariant()) {
					if ($genericTypeVariance->equals($templateType->getVariance())) {
						$messages[] = RuleErrorBuilder::message(sprintf(
							$typeProjectionIsRedundantMessage,
							TypeProjectionHelper::describe($genericTypeType, $genericTypeVariance, VerbosityLevel::typeOnly()),
							$genericType->describe(VerbosityLevel::typeOnly()),
							$templateType->describe(VerbosityLevel::typeOnly()),
							$classLikeDescription,
							$classReflection->getDisplayName(false),
						))
							->identifier('generics.callSiteVarianceRedundant')
							->tip('You can safely remove the call-site variance annotation.')
							->build();
					} elseif (!$genericTypeVariance->validPosition($templateType->getVariance())) {
						$messages[] = RuleErrorBuilder::message(sprintf(
							$typeProjectionHasConflictingVarianceMessage,
							TypeProjectionHelper::describe($genericTypeType, $genericTypeVariance, VerbosityLevel::typeOnly()),
							$genericType->describe(VerbosityLevel::typeOnly()),
							$templateType->getVariance()->describe(),
							$templateType->describe(VerbosityLevel::typeOnly()),
							$classLikeDescription,
							$classReflection->getDisplayName(false),
						))->identifier('generics.callSiteVarianceConflict')->build();
					}
				}

				$boundType = TemplateTypeHelper::resolveToBounds($templateType);
				if ($boundType->isSuperTypeOf($genericTypeType)->yes()) {
					if (!$templateType instanceof TemplateType) {
						continue;
					}
					$map = $templateType->inferTemplateTypes($genericTypeType);
					for ($j = 0; $j < $templateTypesCount; $j++) {
						if ($i === $j) {
							continue;
						}

						$templateTypes[$j] = TemplateTypeHelper::resolveTemplateTypes(
							$templateTypes[$j],
							$map,
							TemplateTypeVarianceMap::createEmpty(),
							TemplateTypeVariance::createStatic(),
						);
					}
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$typeIsNotSubtypeMessage,
					$genericTypeType->describe(VerbosityLevel::typeOnly()),
					$genericType->describe(VerbosityLevel::typeOnly()),
					$templateType->describe(VerbosityLevel::typeOnly()),
					$classLikeDescription,
					$classReflection->getDisplayName(false),
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
