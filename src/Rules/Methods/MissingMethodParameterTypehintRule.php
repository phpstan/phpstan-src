<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\MethodReflection;
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
 * @implements Rule<InClassMethodNode>
 */
final class MissingMethodParameterTypehintRule implements Rule
{

	public function __construct(
		private MissingTypehintCheck $missingTypehintCheck,
		private bool $paramOut,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $node->getMethodReflection();
		$messages = [];

		foreach (ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters() as $parameterReflection) {
			foreach ($this->checkMethodParameter($methodReflection, sprintf('parameter $%s', $parameterReflection->getName()), $parameterReflection->getType()) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}

			if ($parameterReflection->getClosureThisType() !== null) {
				foreach ($this->checkMethodParameter($methodReflection, sprintf('@param-closure-this PHPDoc tag for parameter $%s', $parameterReflection->getName()), $parameterReflection->getClosureThisType()) as $parameterMessage) {
					$messages[] = $parameterMessage;
				}
			}

			if (!$this->paramOut) {
				continue;
			}
			if ($parameterReflection->getOutType() === null) {
				continue;
			}

			foreach ($this->checkMethodParameter($methodReflection, sprintf('@param-out PHPDoc tag for parameter $%s', $parameterReflection->getName()), $parameterReflection->getOutType()) as $parameterMessage) {
				$messages[] = $parameterMessage;
			}
		}

		return $messages;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkMethodParameter(MethodReflection $methodReflection, string $parameterMessage, Type $parameterType): array
	{
		if ($parameterType instanceof MixedType && !$parameterType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Method %s::%s() has %s with no type specified.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName(),
					$parameterMessage,
				))->identifier('missingType.parameter')->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($parameterType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s with no value type specified in iterable type %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$parameterMessage,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($parameterType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s with generic %s but does not specify its types: %s',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
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
				'Method %s::%s() has %s with no signature specified for %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$parameterMessage,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $messages;
	}

}
