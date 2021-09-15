<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<InFunctionNode>
 */
final class MissingFunctionParameterTypehintRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\MissingTypehintCheck $missingTypehintCheck;

	public function __construct(
		MissingTypehintCheck $missingTypehintCheck
	)
	{
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionReflection = $scope->getFunction();
		if (!$functionReflection instanceof PhpFunctionFromParserNodeReflection) {
			return [];
		}

		$messages = [];

		foreach (ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters() as $parameterReflection) {
			foreach ($this->checkFunctionParameter($functionReflection, $parameterReflection) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}
		}

		return $messages;
	}

	/**
	 * @param \PHPStan\Reflection\FunctionReflection $functionReflection
	 * @param \PHPStan\Reflection\ParameterReflection $parameterReflection
	 * @return \PHPStan\Rules\RuleError[]
	 */
	private function checkFunctionParameter(FunctionReflection $functionReflection, ParameterReflection $parameterReflection): array
	{
		$parameterType = $parameterReflection->getType();

		if ($parameterType instanceof MixedType && !$parameterType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Function %s() has parameter $%s with no type specified.',
					$functionReflection->getName(),
					$parameterReflection->getName()
				))->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has parameter $%s with no value type specified in iterable type %s.',
				$functionReflection->getName(),
				$parameterReflection->getName(),
				$iterableTypeDescription
			))->tip(MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has parameter $%s with generic %s but does not specify its types: %s',
				$functionReflection->getName(),
				$parameterReflection->getName(),
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($parameterType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has parameter $%s with no signature specified for %s.',
				$functionReflection->getName(),
				$parameterReflection->getName(),
				$callableType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		return $messages;
	}

}
