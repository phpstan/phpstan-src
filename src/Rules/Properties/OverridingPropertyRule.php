<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Methods\OverridingMethodCheck;
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

	private const VISIBILITY_PUBLIC = 0;
	private const VISIBILITY_PROTECTED = 1;
	private const VISIBILITY_PRIVATE = 2;

	public function __construct(
		private PhpVersion $phpVersion,
		private OverridingMethodCheck $overridingMethodCheck,
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
		#[AutowiredParameter]
		private bool $checkPhpDocMethodSignatures,
		#[AutowiredParameter(ref: '%reportMaybesInPropertyPhpDocTypes%')]
		private bool $reportMaybes,
		#[AutowiredParameter]
		private ?bool $checkMissingOverridePropertyAttribute,
		#[AutowiredParameter]
		private bool $checkMissingOverrideMethodAttribute,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$classReflection = $node->getClassReflection();
		$propertyReflection = $classReflection->getNativeProperty($node->getName());
		$prototypes = $this->findPrototypes($classReflection, $node->getName());

		$errors = [];
		foreach ($this->getDeclaredHooks($node) as $hookName => $hookNode) {
			// a backed property has a get operation, and a set operation unless it's readonly, even without hooks
			foreach ($prototypes as $prototype) {
				if ($hookName === 'get' ? $prototype->isReadable() : $prototype->isWritable()) {
					continue 2;
				}
			}

			$errors = array_merge($errors, $this->overridingMethodCheck->checkOverrideAttributeWithoutPrototype($propertyReflection->getHook($hookName), $hookNode));
		}

		if (count($prototypes) === 0) {
			if (OverridingMethodCheck::hasOverrideAttribute($node->getAttrGroups())) {
				$originalNode = $node->getOriginalNode();
				if ($originalNode instanceof Node\Stmt\Property) {
					/** @var Node\Stmt\Property $originalNode */
					$originalNode = $originalNode->getAttribute('originalPropertyStmt');
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s has #[\Override] attribute but does not override any property.',
					$node->getClassReflection()->getDisplayName(),
					$node->getName(),
				))
					->nonIgnorable()
					->identifier('property.override')
					->fixNode($originalNode, static function ($property) {
						$property->attrGroups = OverridingMethodCheck::filterOverrideAttribute($property->attrGroups);
						return $property;
					})
					->build();
			}

			return $errors;
		}

		if (
			(
				$this->checkMissingOverridePropertyAttribute === true
				|| (
					$this->checkMissingOverridePropertyAttribute === null
					&& $this->checkMissingOverrideMethodAttribute
					&& $scope->getPhpVersion()->supportsOverrideAttributeOnProperty()->yes()
				)
			)
			&& !$scope->isInTrait()
			&& !OverridingMethodCheck::hasOverrideAttribute($node->getAttrGroups())
		) {
			$originalNode = $node->getOriginalNode();
			if ($originalNode instanceof Node\Stmt\Property) {
				/** @var Node\Stmt\Property $originalNode */
				$originalNode = $originalNode->getAttribute('originalPropertyStmt');
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s overrides property %s::$%s but is missing the #[\Override] attribute.',
				$node->getClassReflection()->getDisplayName(),
				$node->getName(),
				$prototypes[0]->getDeclaringClass()->getDisplayName(),
				$prototypes[0]->getName(),
			))
				->identifier('property.missingOverride')
				->fixNode($originalNode, static function ($property) {
					$property->attrGroups[] = new Node\AttributeGroup([
						new Attribute(new Node\Name\FullyQualified('Override')),
					]);

					return $property;
				})
				->build();
		}

		$getHookNode = $this->getDeclaredHooks($node)['get'] ?? null;
		if (
			$this->phpVersion->supportsPropertyHooks()
			&& $getHookNode !== null
			&& $getHookNode->byRef
			&& $propertyReflection->isVirtual()->no()
			&& $propertyReflection->hasHook('set')
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Get hook of backed property %s::$%s with set hook cannot return by reference.',
				$classReflection->getDisplayName(),
				$node->getName(),
			))
				->line($getHookNode->getStartLine())
				->nonIgnorable()
				->identifier('property.backedGetHookByRef')
				->build();
		}

		foreach ($prototypes as $prototype) {
			$errors = array_merge($errors, $this->processPrototype($node, $prototype, $prototypes, $scope));
		}

		return $errors;
	}

	/**
	 * @param list<PhpPropertyReflection> $prototypes
	 * @return list<IdentifierRuleError>
	 */
	private function processPrototype(
		ClassPropertyNode $node,
		PhpPropertyReflection $prototype,
		array $prototypes,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
	): array
	{
		$classReflection = $node->getClassReflection();
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
		$setHookParameterErrors = [];
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

			foreach ($this->getDeclaredHooks($node) as $hookName => $hookNode) {
				if (!$prototype->hasHook($hookName)) {
					continue;
				}

				$hook = $propertyReflection->getHook($hookName);
				$prototypeHook = $prototype->getHook($hookName);

				// BetterReflection does not mark hooks of abstract properties abstract,
				// so OverridingMethodCheck::checkInheritance() does not see this
				if ($hookNode->body === null && $node->isAbstract() && $prototype->isAbstract()->no()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot make non-abstract %s abstract in class %s.',
						OverridingMethodCheck::describeMethod($prototypeHook, $prototypeHook->getDeclaringClass(), true),
						$classReflection->getDisplayName(),
					))
						->line($hookNode->getStartLine())
						->nonIgnorable()
						->identifier('property.abstractOverridingNonAbstractHook')
						->build();
				}

				// visibility is the visibility of the property, compared below
				$errors = array_merge($errors, $this->overridingMethodCheck->checkInheritance(
					$hook,
					$hookNode,
					$prototypeHook,
					$prototypeHook->getDeclaringClass(),
					$prototypeHook,
					$prototypeHook->getDeclaringClass(),
					$this->checkMissingOverrideMethodAttribute
						&& !$scope->isInTrait()
						&& $this->findFirstPrototypeWithHook($prototypes, $hookName) === $prototype,
				));

				// the get hook returns the property type, compared below
				if (
					$hookName !== 'set'
					|| !$this->isSetHookDeclarationValid($propertyReflection, $hook)
					|| !$this->isSetHookDeclarationValid($prototype, $prototypeHook)
				) {
					continue;
				}

				$setHookParameterErrors = array_merge(
					$setHookParameterErrors,
					$this->methodParameterComparisonHelper->compare($prototypeHook, $prototypeHook->getDeclaringClass(), $hook, $scope, false, $hookNode->getStartLine()),
				);
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

		if (
			($propertyReflection->isPrivateSet() || $propertyReflection->isProtectedSet())
			// a get-only virtual property does not have set visibility to respect
			&& !($prototype->isVirtual()->yes() && !$prototype->hasHook('set'))
			// already reported as readonly property overriding readwrite property
			&& !($node->isReadOnly() && !$prototype->isReadOnly())
		) {
			$prototypeSetVisibility = $this->getSetVisibility($prototype);
			$setVisibility = $this->getSetVisibility($propertyReflection);
			if ($setVisibility > $prototypeSetVisibility) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s(set) property %s::$%s overriding %s(set) property %s::$%s should %s.',
					$setVisibility === self::VISIBILITY_PRIVATE ? 'Private' : 'Protected',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototypeSetVisibility === self::VISIBILITY_PUBLIC ? 'public' : 'protected',
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
					$prototypeSetVisibility === self::VISIBILITY_PUBLIC ? 'also be public(set)' : 'be protected(set) or public(set)',
				))->identifier('property.setVisibility')->nonIgnorable()->build();
			}
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
				if (
					// iterable is the same type as array|Traversable
					!$prototype->getNativeType()->isSuperTypeOf($nativeType)->yes()
					|| !$nativeType->isSuperTypeOf($prototype->getNativeType())->yes()
				) {
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

		if (count($typeErrors) === 0) {
			// an implicit set hook parameter has the property type, so it's already reported
			$typeErrors = $setHookParameterErrors;
		}

		if (
			count($typeErrors) === 0
			&& $this->phpVersion->supportsPropertyHooks()
			&& $prototype->isAbstract()->yes()
			&& $prototype->hasHook('set')
			&& $propertyReflection->isVirtual()->no()
			&& !isset($this->getDeclaredHooks($node)['set'])
		) {
			// the backed property itself implements the abstract set hook, so it has to accept everything the hook does
			$prototypeSetType = $prototype->getHook('set')->getOnlyVariant()->getParameters()[0]->getNativeType();
			if (!$propertyReflection->getNativeType()->isSuperTypeOf($prototypeSetType)->yes()) {
				$typeErrors[] = RuleErrorBuilder::message(sprintf(
					'Type %s of property %s::$%s is not contravariant with set hook parameter type %s of overridden property %s::$%s.',
					$propertyReflection->getNativeType()->describe(VerbosityLevel::typeOnly()),
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototypeSetType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
				))->identifier('property.nativeType')->nonIgnorable()->build();
			}
		}

		$errors = array_merge($errors, $typeErrors);

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		if (count($typeErrors) > 0) {
			return $errors;
		}

		if (
			$prototype->getReadableType()->isSuperTypeOf($propertyReflection->getReadableType())->yes()
			&& $propertyReflection->getReadableType()->isSuperTypeOf($prototype->getReadableType())->yes()
		) {
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

	/**
	 * @return self::VISIBILITY_*
	 */
	private function getSetVisibility(PhpPropertyReflection $property): int
	{
		if ($property->isPrivateSet() || $property->isPrivate()) {
			return self::VISIBILITY_PRIVATE;
		}

		if ($property->isProtectedSet() || !$property->isPublic()) {
			return self::VISIBILITY_PROTECTED;
		}

		return self::VISIBILITY_PUBLIC;
	}

	/**
	 * Every property the declared property has to be compatible with: the parent class property,
	 * and the properties of interfaces the parent class does not already implement.
	 *
	 * @return list<PhpPropertyReflection>
	 */
	private function findPrototypes(ClassReflection $classReflection, string $propertyName): array
	{
		$prototypes = [];
		$parentInterfaces = [];
		$parentClass = $classReflection->getParentClass();
		if ($parentClass !== null) {
			$parentInterfaces = $parentClass->getInterfaces();
			if ($parentClass->hasNativeProperty($propertyName)) {
				$property = $parentClass->getNativeProperty($propertyName);
				if (!$property->isPrivate()) {
					$prototypes[] = $property;
				}
			}
		}

		foreach ($classReflection->getInterfaces() as $interfaceName => $interface) {
			if (isset($parentInterfaces[$interfaceName])) {
				continue;
			}
			if (!$interface->hasNativeProperty($propertyName)) {
				continue;
			}

			$property = $interface->getNativeProperty($propertyName);
			if ($property->getDeclaringClass()->getName() !== $interface->getName()) {
				// inherited from a parent interface which is checked separately
				continue;
			}

			$prototypes[] = $property;
		}

		return $prototypes;
	}

	/**
	 * @return array<'get'|'set', Node\PropertyHook>
	 */
	private function getDeclaredHooks(ClassPropertyNode $node): array
	{
		$hooks = [];
		foreach ($node->getHooks() as $hook) {
			$hookName = $hook->name->toLowerString();
			if ($hookName !== 'get' && $hookName !== 'set') {
				continue;
			}

			$hooks[$hookName] = $hook;
		}

		return $hooks;
	}

	/**
	 * @param list<PhpPropertyReflection> $prototypes
	 * @param 'get'|'set' $hookName
	 */
	private function findFirstPrototypeWithHook(array $prototypes, string $hookName): ?PhpPropertyReflection
	{
		foreach ($prototypes as $prototype) {
			if ($prototype->hasHook($hookName)) {
				return $prototype;
			}
		}

		return null;
	}

	/**
	 * A set hook wrong by itself is reported by other rules like SetPropertyHookParameterRule,
	 * comparing it with the overridden hook would report the same mistake again.
	 */
	private function isSetHookDeclarationValid(PhpPropertyReflection $property, ExtendedMethodReflection $setHook): bool
	{
		$parameters = $setHook->getOnlyVariant()->getParameters();
		if (count($parameters) !== 1) {
			return false;
		}

		$parameter = $parameters[0];
		if ($parameter->isOptional() || $parameter->isVariadic() || !$parameter->passedByReference()->no()) {
			return false;
		}

		return $parameter->getNativeType()->isSuperTypeOf($property->getNativeType())->yes();
	}

}
