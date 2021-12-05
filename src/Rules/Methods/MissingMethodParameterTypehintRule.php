<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<InClassMethodNode>
 */
final class MissingMethodParameterTypehintRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\MissingTypehintCheck $missingTypehintCheck;

	public function __construct(MissingTypehintCheck $missingTypehintCheck)
	{
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $scope->getFunction();
		if (!$methodReflection instanceof MethodReflection) {
			return [];
		}

		$messages = [];

		foreach (ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters() as $parameterReflection) {
			foreach ($this->checkMethodParameter($methodReflection, $parameterReflection) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}
		}

		return $messages;
	}

	/**
	 * @return \PHPStan\Rules\RuleError[]
	 */
	private function checkMethodParameter(MethodReflection $methodReflection, ParameterReflection $parameterReflection): array
	{
		$parameterType = $parameterReflection->getType();

		if ($parameterType instanceof MixedType && !$parameterType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Method %s::%s() has parameter $%s with no type specified.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName(),
					$parameterReflection->getName()
				))->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has parameter $%s with no value type specified in iterable type %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$parameterReflection->getName(),
				$iterableTypeDescription
			))->tip(MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has parameter $%s with generic %s but does not specify its types: %s',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$parameterReflection->getName(),
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($parameterType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has parameter $%s with no signature specified for %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$parameterReflection->getName(),
				$callableType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		return $messages;
	}

}
