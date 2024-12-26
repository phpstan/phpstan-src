<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<InPropertyHookNode>
 */
final class SetPropertyHookParameterRule implements Rule
{

	public function __construct(
		private MissingTypehintCheck $missingTypehintCheck,
		private bool $checkPhpDocMethodSignatures,
		private bool $checkMissingTypehints,
	)
	{
	}

	public function getNodeType(): string
	{
		return InPropertyHookNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hookReflection = $node->getHookReflection();
		if (!$hookReflection->isPropertyHook()) {
			return [];
		}

		if ($hookReflection->getPropertyHookName() !== 'set') {
			return [];
		}

		$propertyReflection = $node->getPropertyReflection();
		$parameters = $hookReflection->getParameters();
		if (!isset($parameters[0])) {
			throw new ShouldNotHappenException();
		}

		$classReflection = $node->getClassReflection();

		$errors = [];
		$parameter = $parameters[0];
		if (!$propertyReflection->hasNativeType()) {
			if ($parameter->hasNativeType()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Parameter $%s of set hook has a native type but the property %s::$%s does not.',
					$parameter->getName(),
					$classReflection->getDisplayName(),
					$hookReflection->getHookedPropertyName(),
				))->identifier('propertySetHook.nativeParameterType')
					->nonIgnorable()
					->build();
			}
		} elseif (!$parameter->hasNativeType()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Parameter $%s of set hook does not have a native type but the property %s::$%s does.',
				$parameter->getName(),
				$classReflection->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
			))->identifier('propertySetHook.nativeParameterType')
				->nonIgnorable()
				->build();
		} else {
			if (!$parameter->getNativeType()->isSuperTypeOf($propertyReflection->getNativeType())->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Native type %s of set hook parameter $%s is not contravariant with native type %s of property %s::$%s.',
					$parameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
					$parameter->getName(),
					$propertyReflection->getNativeType()->describe(VerbosityLevel::typeOnly()),
					$classReflection->getDisplayName(),
					$hookReflection->getHookedPropertyName(),
				))->identifier('propertySetHook.nativeParameterType')
					->nonIgnorable()
					->build();
			}
		}

		if (!$this->checkPhpDocMethodSignatures || count($errors) > 0) {
			return $errors;
		}

		$parameterType = $parameter->getType();

		if (!$parameterType->isSuperTypeOf($propertyReflection->getReadableType())->yes()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Type %s of set hook parameter $%s is not contravariant with type %s of property %s::$%s.',
				$parameterType->describe(VerbosityLevel::value()),
				$parameter->getName(),
				$propertyReflection->getReadableType()->describe(VerbosityLevel::value()),
				$classReflection->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
			))->identifier('propertySetHook.parameterType')
				->build();
		}

		if (!$this->checkMissingTypehints) {
			return $errors;
		}

		if ($parameter->getNativeType()->equals($propertyReflection->getReadableType())) {
			return $errors;
		}

		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Set hook for property %s::$%s has parameter $%s with no value type specified in iterable type %s.',
				$classReflection->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
				$parameter->getName(),
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Set hook for property %s::$%s has parameter $%s with generic %s but does not specify its types: %s',
				$classReflection->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
				$parameter->getName(),
				$name,
				$genericTypeNames,
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($parameterType) as $callableType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Set hook for property %s::$%s has parameter $%s with no signature specified for %s.',
				$classReflection->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
				$parameter->getName(),
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $errors;
	}

}
