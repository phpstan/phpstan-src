<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(level: 0)]
final class OverridingPropertyRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		#[AutowiredParameter]
		private bool $checkPhpDocMethodSignatures,
		#[AutowiredParameter(ref: '%reportMaybesInPropertyPhpDocTypes%')]
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$prototype = $this->findPrototype($classReflection, $node->getName());
		if ($prototype === null) {
			return [];
		}

		$errors = [];
		if ($prototype->isStatic()) {
			if (!$node->isStatic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Non-static property %s::$%s overrides static property %s::$%s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.nonStatic')->nonIgnorable()->build();
			}
		} elseif ($node->isStatic()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Static property %s::$%s overrides non-static property %s::$%s.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.static')->nonIgnorable()->build();
		}

		if ($prototype->isReadOnly()) {
			if (!$node->isReadOnly()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Readwrite property %s::$%s overrides readonly property %s::$%s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.readWrite')->nonIgnorable()->build();
			}
		} elseif ($node->isReadOnly()) {
			if (
				!$this->phpVersion->supportsPropertyHooks()
				|| $prototype->isWritable()
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Readonly property %s::$%s overrides readwrite property %s::$%s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.readOnly')->nonIgnorable()->build();
			}
		}

		$propertyReflection = $classReflection->getNativeProperty($node->getName());
		if ($this->phpVersion->supportsPropertyHooks()) {
			if ($prototype->isReadable()) {
				if (!$propertyReflection->isReadable()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Property %s::$%s overriding readable property %s::$%s also has to be readable.',
						$classReflection->getDisplayName(),
						$node->getName(),
						$prototype->getDeclaringClass()->getDisplayName(),
						$node->getName(),
					))->identifier('property.notReadable')->nonIgnorable()->build();
				}
			}
			if ($prototype->isWritable()) {
				if (!$propertyReflection->isWritable()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Property %s::$%s overriding writable property %s::$%s also has to be writable.',
						$classReflection->getDisplayName(),
						$node->getName(),
						$prototype->getDeclaringClass()->getDisplayName(),
						$node->getName(),
					))->identifier('property.notWritable')->nonIgnorable()->build();
				}
			}
		}

		if ($prototype->isPublic()) {
			if (!$node->isPublic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s property %s::$%s overriding public property %s::$%s should also be public.',
					$node->isPrivate() ? 'Private' : 'Protected',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.visibility')->nonIgnorable()->build();
			}
		} elseif ($node->isPrivate()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Private property %s::$%s overriding protected property %s::$%s should be protected or public.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.visibility')->nonIgnorable()->build();
		}

		if ($prototype->isFinalByKeyword()->yes()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s overrides final property %s::$%s.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.parentPropertyFinal')
				->nonIgnorable()
				->build();
		} elseif ($prototype->isFinal()->yes()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s overrides @final property %s::$%s.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.parentPropertyFinalByPhpDoc')
				->build();
		}

		$typeErrors = [];
		$nativeType = $node->getNativeType();
		if ($prototype->hasNativeType()) {
			if ($nativeType === null) {
				$typeErrors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s overriding property %s::$%s (%s) should also have native type %s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
					$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
					$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
				))->identifier('property.missingNativeType')->nonIgnorable()->build();
			} else {
				if (!$prototype->getNativeType()->equals($nativeType)) {
					if (
						$this->phpVersion->supportsPropertyHooks()
						&& ($prototype->isVirtual()->yes() || $prototype->isAbstract()->yes())
						&& (!$prototype->isReadable() || !$prototype->isWritable())
					) {
						if (!$prototype->isReadable()) {
							if (!$nativeType->isSuperTypeOf($prototype->getNativeType())->yes()) {
								$typeErrors[] = RuleErrorBuilder::message(sprintf(
									'Type %s of property %s::$%s is not contravariant with type %s of overridden property %s::$%s.',
									$nativeType->describe(VerbosityLevel::typeOnly()),
									$classReflection->getDisplayName(),
									$node->getName(),
									$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
									$prototype->getDeclaringClass()->getDisplayName(),
									$node->getName(),
								))->identifier('property.nativeType')->nonIgnorable()->build();
							}
						} elseif (!$prototype->getNativeType()->isSuperTypeOf($nativeType)->yes()) {
							$typeErrors[] = RuleErrorBuilder::message(sprintf(
								'Type %s of property %s::$%s is not covariant with type %s of overridden property %s::$%s.',
								$nativeType->describe(VerbosityLevel::typeOnly()),
								$classReflection->getDisplayName(),
								$node->getName(),
								$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
								$prototype->getDeclaringClass()->getDisplayName(),
								$node->getName(),
							))->identifier('property.nativeType')->nonIgnorable()->build();
						}
					} else {
						$typeErrors[] = RuleErrorBuilder::message(sprintf(
							'Type %s of property %s::$%s is not the same as type %s of overridden property %s::$%s.',
							$nativeType->describe(VerbosityLevel::typeOnly()),
							$classReflection->getDisplayName(),
							$node->getName(),
							$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
							$prototype->getDeclaringClass()->getDisplayName(),
							$node->getName(),
						))->identifier('property.nativeType')->nonIgnorable()->build();
					}
				}
			}
		} elseif ($nativeType !== null) {
			$typeErrors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s (%s) overriding property %s::$%s should not have a native type.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$nativeType->describe(VerbosityLevel::typeOnly()),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName(),
			))->identifier('property.extraNativeType')->nonIgnorable()->build();
		}

		$errors = array_merge($errors, $typeErrors);

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		if (count($typeErrors) > 0) {
			return $errors;
		}

		if ($prototype->getReadableType()->equals($propertyReflection->getReadableType())) {
			return $errors;
		}

		$verbosity = VerbosityLevel::getRecommendedLevelByType($prototype->getReadableType(), $propertyReflection->getReadableType());

		if (
			$this->phpVersion->supportsPropertyHooks()
			&& ($prototype->isVirtual()->yes() || $prototype->isAbstract()->yes())
			&& (!$prototype->isReadable() || !$prototype->isWritable())
		) {
			if (!$prototype->isReadable()) {
				if (!$propertyReflection->getReadableType()->isSuperTypeOf($prototype->getReadableType())->yes()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc type %s of property %s::$%s is not contravariant with PHPDoc type %s of overridden property %s::$%s.',
						$propertyReflection->getReadableType()->describe($verbosity),
						$classReflection->getDisplayName(),
						$node->getName(),
						$prototype->getReadableType()->describe($verbosity),
						$prototype->getDeclaringClass()->getDisplayName(),
						$node->getName(),
					))->identifier('property.phpDocType')->tip(sprintf(
						"You can fix 3rd party PHPDoc types with stub files:\n   %s",
						'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
					))->build();
				}
			} elseif (!$prototype->getReadableType()->isSuperTypeOf($propertyReflection->getReadableType())->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc type %s of property %s::$%s is not covariant with PHPDoc type %s of overridden property %s::$%s.',
					$propertyReflection->getReadableType()->describe($verbosity),
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getReadableType()->describe($verbosity),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.phpDocType')->tip(sprintf(
					"You can fix 3rd party PHPDoc types with stub files:\n   %s",
					'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
				))->build();
			}

			return $errors;
		}

		$isSuperType = $prototype->getReadableType()->isSuperTypeOf($propertyReflection->getReadableType());
		$canBeTurnedOffError = RuleErrorBuilder::message(sprintf(
			'PHPDoc type %s of property %s::$%s is not the same as PHPDoc type %s of overridden property %s::$%s.',
			$propertyReflection->getReadableType()->describe($verbosity),
			$classReflection->getDisplayName(),
			$node->getName(),
			$prototype->getReadableType()->describe($verbosity),
			$prototype->getDeclaringClass()->getDisplayName(),
			$node->getName(),
		))->identifier('property.phpDocType')->tip(sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s\n   This error can be turned off by setting\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
			'<fg=cyan>reportMaybesInPropertyPhpDocTypes: false</> in your <fg=cyan>%configurationFile%</>.',
		))->build();
		$cannotBeTurnedOffError = RuleErrorBuilder::message(sprintf(
			'PHPDoc type %s of property %s::$%s is %s PHPDoc type %s of overridden property %s::$%s.',
			$propertyReflection->getReadableType()->describe($verbosity),
			$classReflection->getDisplayName(),
			$node->getName(),
			$this->reportMaybes ? 'not the same as' : 'not covariant with',
			$prototype->getReadableType()->describe($verbosity),
			$prototype->getDeclaringClass()->getDisplayName(),
			$node->getName(),
		))->identifier('property.phpDocType')->tip(sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
		))->build();
		if ($this->reportMaybes) {
			if (!$isSuperType->yes()) {
				$errors[] = $cannotBeTurnedOffError;
			} else {
				$errors[] = $canBeTurnedOffError;
			}
		} else {
			if (!$isSuperType->yes()) {
				$errors[] = $cannotBeTurnedOffError;
			}
		}

		return $errors;
	}

	private function findPrototype(ClassReflection $classReflection, string $propertyName): ?PhpPropertyReflection
	{
		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return $this->findPrototypeInInterfaces($classReflection, $propertyName);
		}

		if (!$parentClass->hasNativeProperty($propertyName)) {
			return $this->findPrototypeInInterfaces($classReflection, $propertyName);
		}

		$property = $parentClass->getNativeProperty($propertyName);
		if ($property->isPrivate()) {
			return $this->findPrototypeInInterfaces($classReflection, $propertyName);
		}

		return $property;
	}

	private function findPrototypeInInterfaces(ClassReflection $classReflection, string $propertyName): ?PhpPropertyReflection
	{
		foreach ($classReflection->getInterfaces() as $interface) {
			if (!$interface->hasNativeProperty($propertyName)) {
				continue;
			}

			return $interface->getNativeProperty($propertyName);
		}

		return null;
	}

}
