<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\Tag\PropertyTag;
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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

#[AutowiredService]
final class PropertyTagCheck
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
	public function check(
		Scope $scope,
		ClassReflection $classReflection,
		ClassLike $node,
	): array
	{
		$errors = [];
		foreach ($classReflection->getPropertyTags() as $propertyName => $propertyTag) {
			[$types, $tagName] = $this->getTypesAndTagName($propertyTag);
			foreach ($types as $type) {
				foreach ($this->checkPropertyTypeInTraitDefinitionContext($classReflection, $propertyName, $tagName, $type) as $error) {
					$errors[] = $error;
				}
				foreach ($this->checkPropertyTypeInTraitUseContext($scope, $classReflection, $propertyName, $tagName, $type, $node) as $error) {
					$errors[] = $error;
				}
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitDefinitionContext(ClassReflection $classReflection): array
	{
		$errors = [];
		foreach ($classReflection->getPropertyTags() as $propertyName => $propertyTag) {
			[$types, $tagName] = $this->getTypesAndTagName($propertyTag);
			foreach ($types as $type) {
				foreach ($this->checkPropertyTypeInTraitDefinitionContext($classReflection, $propertyName, $tagName, $type) as $error) {
					$errors[] = $error;
				}
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitUseContext(
		Scope $scope,
		ClassReflection $classReflection,
		ClassReflection $implementingClass,
		ClassLike $node,
	): array
	{
		$phpDoc = $classReflection->getTraitContextResolvedPhpDoc($implementingClass);
		if ($phpDoc === null) {
			return [];
		}

		$errors = [];
		foreach ($phpDoc->getPropertyTags() as $propertyName => $propertyTag) {
			[$types, $tagName] = $this->getTypesAndTagName($propertyTag);
			foreach ($types as $type) {
				foreach ($this->checkPropertyTypeInTraitUseContext($scope, $classReflection, $propertyName, $tagName, $type, $node) as $error) {
					$errors[] = $error;
				}
			}
		}

		return $errors;
	}

	/**
	 * @return array{list<Type>, string}
	 */
	private function getTypesAndTagName(PropertyTag $propertyTag): array
	{
		$readableType = $propertyTag->getReadableType();
		$writableType = $propertyTag->getWritableType();

		$types = [];
		$tagName = '@property';
		if ($readableType !== null) {
			if ($writableType !== null) {
				if ($writableType->equals($readableType)) {
					$types[] = $readableType;
				} else {
					$types[] = $readableType;
					$types[] = $writableType;
				}
			} else {
				$tagName = '@property-read';
				$types[] = $readableType;
			}
		} elseif ($writableType !== null) {
			$tagName = '@property-write';
			$types[] = $writableType;
		} else {
			throw new ShouldNotHappenException();
		}

		return [$types, $tagName];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkPropertyTypeInTraitDefinitionContext(ClassReflection $classReflection, string $propertyName, string $tagName, Type $type): array
	{
		if (!$this->checkMissingTypehints) {
			return [];
		}

		$errors = [];

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($type) as [$innerName, $genericTypeNames]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag %s for property %s::$%s contains generic %s but does not specify its types: %s',
				$tagName,
				$classReflection->getDisplayName(),
				$propertyName,
				$innerName,
				$genericTypeNames,
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($type) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s has PHPDoc tag %s for property $%s with no value type specified in iterable type %s.',
				$classReflection->getClassTypeDescription(),
				$classReflection->getDisplayName(),
				$tagName,
				$propertyName,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($type) as $callableType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s has PHPDoc tag %s for property $%s with no signature specified for %s.',
				$classReflection->getClassTypeDescription(),
				$classReflection->getDisplayName(),
				$tagName,
				$propertyName,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkPropertyTypeInTraitUseContext(Scope $scope, ClassReflection $classReflection, string $propertyName, string $tagName, Type $type, ClassLike $node): array
	{
		$errors = [];
		foreach ($type->getReferencedClasses() as $class) {
			if (!$this->reflectionProvider->hasClass($class)) {
				$errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag %s for property %s::$%s contains unknown class %s.', $tagName, $classReflection->getDisplayName(), $propertyName, $class))
					->identifier('class.notFound');

				if ($this->discoveringSymbolsTip) {
					$errorBuilder->discoveringSymbolsTip();
				}

				$errors[] = $errorBuilder->build();
			} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag %s for property %s::$%s contains invalid type %s.', $tagName, $classReflection->getDisplayName(), $propertyName, $class))
					->identifier('propertyTag.trait')
					->build();
			} else {
				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames($scope, [
						new ClassNameNodePair($class, $node),
					], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_PROPERTY, [
						'propertyTagName' => $propertyName,
					]), $this->checkClassCaseSensitivity),
				);
			}
		}

		if ($this->unresolvableTypeHelper->containsUnresolvableType($type)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag %s for property %s::$%s contains unresolvable type.',
				$tagName,
				$classReflection->getDisplayName(),
				$propertyName,
			))->identifier('propertyTag.unresolvableType')->build();
		}

		$escapedClassName = SprintfHelper::escapeFormatString($classReflection->getDisplayName());
		$escapedPropertyName = SprintfHelper::escapeFormatString($propertyName);
		$escapedTagName = SprintfHelper::escapeFormatString($tagName);

		return array_merge(
			$errors,
			$this->genericObjectTypeCheck->check(
				$type,
				sprintf('PHPDoc tag %s for property %s::$%s contains generic type %%s but %%s %%s is not generic.', $escapedTagName, $escapedClassName, $escapedPropertyName),
				sprintf('Generic type %%s in PHPDoc tag %s for property %s::$%s does not specify all template types of %%s %%s: %%s', $escapedTagName, $escapedClassName, $escapedPropertyName),
				sprintf('Generic type %%s in PHPDoc tag %s for property %s::$%s specifies %%d template types, but %%s %%s supports only %%d: %%s', $escapedTagName, $escapedClassName, $escapedPropertyName),
				sprintf('Type %%s in generic type %%s in PHPDoc tag %s for property %s::$%s is not subtype of template type %%s of %%s %%s.', $escapedTagName, $escapedClassName, $escapedPropertyName),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag %s for property %s::$%s is in conflict with %%s template type %%s of %%s %%s.', $escapedTagName, $escapedClassName, $escapedPropertyName),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag %s for property %s::$%s is redundant, template type %%s of %%s %%s has the same variance.', $escapedTagName, $escapedClassName, $escapedPropertyName),
			),
		);
	}

}
