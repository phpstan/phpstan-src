<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function implode;
use function sprintf;

/**
 * @implements Rule<InFunctionNode>
 */
final class MissingFunctionParameterTypehintRule implements Rule
{

	public function __construct(
		private MissingTypehintCheck $missingTypehintCheck,
		private bool $paramOut,
	)
	{
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionReflection = $node->getFunctionReflection();
		$messages = [];

		foreach (ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters() as $parameterReflection) {
			foreach ($this->checkFunctionParameter($functionReflection, sprintf('parameter $%s', $parameterReflection->getName()), $parameterReflection->getType()) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}

			if ($parameterReflection->getClosureThisType() !== null) {
				foreach ($this->checkFunctionParameter($functionReflection, sprintf('@param-closure-this PHPDoc tag for parameter $%s', $parameterReflection->getName()), $parameterReflection->getClosureThisType()) as $parameterMessage) {
					$messages[] = $parameterMessage;
				}
			}

			if (!$this->paramOut) {
				continue;
			}
			if ($parameterReflection->getOutType() === null) {
				continue;
			}

			foreach ($this->checkFunctionParameter($functionReflection, sprintf('@param-out PHPDoc tag for parameter $%s', $parameterReflection->getName()), $parameterReflection->getOutType()) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}
		}

		return $messages;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkFunctionParameter(FunctionReflection $functionReflection, string $parameterMessage, Type $parameterType): array
	{
		if ($parameterType instanceof MixedType && !$parameterType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Function %s() has %s with no type specified.',
					$functionReflection->getName(),
					$parameterMessage,
				))->identifier('missingType.parameter')->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has %s with no value type specified in iterable type %s.',
				$functionReflection->getName(),
				$parameterMessage,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has %s with generic %s but does not specify its types: %s',
				$functionReflection->getName(),
				$parameterMessage,
				$name,
				implode(', ', $genericTypeNames),
			))
				->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($parameterType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Function %s() has %s with no signature specified for %s.',
				$functionReflection->getName(),
				$parameterMessage,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $messages;
	}

}
