<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

#[AutowiredService]
final class MixinCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private MissingTypehintCheck $missingTypehintCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		#[AutowiredParameter]
		private bool $checkClassCaseSensitivity,
		#[AutowiredParameter]
		private bool $checkMissingTypehints,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(Scope $scope, ClassReflection $classReflection, ClassLike $node): array
	{
		$errors = [];
		foreach ($this->checkInTraitDefinitionContext($classReflection) as $error) {
			$errors[] = $error;
		}

		foreach ($this->checkInTraitUseContext($scope, $classReflection, $classReflection, $node) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitDefinitionContext(ClassReflection $classReflection): array
	{
		$errors = [];
		foreach ($classReflection->getMixinTags() as $mixinTag) {
			$type = $mixinTag->getType();
			if (!$type->canCallMethods()->yes() || !$type->canAccessProperties()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('mixin.nonObject')
					->build();
				continue;
			}

			if (!$this->checkMissingTypehints) {
				continue;
			}

			foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($type) as $iterableType) {
				$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s %s has PHPDoc tag @mixin with no value type specified in iterable type %s.',
					$classReflection->getClassTypeDescription(),
					$classReflection->getDisplayName(),
					$iterableTypeDescription,
				))
					->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
					->identifier('missingType.iterableValue')
					->build();
			}

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($type) as [$innerName, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @mixin contains generic %s but does not specify its types: %s',
					$innerName,
					$genericTypeNames,
				))
					->identifier('missingType.generics')
					->build();
			}

			foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($type) as $callableType) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s %s has PHPDoc tag @mixin with no signature specified for %s.',
					$classReflection->getClassTypeDescription(),
					$classReflection->getDisplayName(),
					$callableType->describe(VerbosityLevel::typeOnly()),
				))->identifier('missingType.callable')->build();
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitUseContext(
		Scope $scope,
		ClassReflection $reflection,
		ClassReflection $implementingClassReflection,
		ClassLike $node,
	): array
	{
		if ($reflection->getNativeReflection()->getName() === $implementingClassReflection->getName()) {
			$phpDoc = $reflection->getResolvedPhpDoc();
		} else {
			$phpDoc = $reflection->getTraitContextResolvedPhpDoc($implementingClassReflection);
		}
		if ($phpDoc === null) {
			return [];
		}

		$errors = [];
		foreach ($phpDoc->getMixinTags() as $mixinTag) {
			$type = $mixinTag->getType();
			if (
				$this->unresolvableTypeHelper->containsUnresolvableType($type)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @mixin contains unresolvable type.')
					->identifier('mixin.unresolvableType')
					->build();
				continue;
			}

			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$type,
				'PHPDoc tag @mixin contains generic type %s but %s %s is not generic.',
				'Generic type %s in PHPDoc tag @mixin does not specify all template types of %s %s: %s',
				'Generic type %s in PHPDoc tag @mixin specifies %d template types, but %s %s supports only %d: %s',
				'Type %s in generic type %s in PHPDoc tag @mixin is not subtype of template type %s of %s %s.',
				'Call-site variance of %s in generic type %s in PHPDoc tag @mixin is in conflict with %s template type %s of %s %s.',
				'Call-site variance of %s in generic type %s in PHPDoc tag @mixin is redundant, template type %s of %s %s has the same variance.',
			));

			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains unknown class %s.', $class))
						->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$errors[] = $errorBuilder->build();
				} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains invalid type %s.', $class))
						->identifier('mixin.trait')
						->build();
				} else {
					$errors = array_merge(
						$errors,
						$this->classCheck->checkClassNames($scope, [
							new ClassNameNodePair($class, $node),
						], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_MIXIN), $this->checkClassCaseSensitivity),
					);
				}
			}
		}

		return $errors;
	}

}
