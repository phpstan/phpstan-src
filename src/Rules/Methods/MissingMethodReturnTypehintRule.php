<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function implode;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class MissingMethodReturnTypehintRule implements Rule
{

	public function __construct(private MissingTypehintCheck $missingTypehintCheck)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $node->getMethodReflection();
		if ($scope->isInTrait()) {
			$methodNode = $node->getOriginalNode();
			$originalMethodName = $methodNode->getAttribute('originalTraitMethodName');
			if ($originalMethodName === '__construct') {
				return [];
			}
		}
		$returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

		if ($returnType instanceof MixedType && !$returnType->isExplicitMixed()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Method %s::%s() has no return type specified.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName(),
				))->identifier('missingType.return')->build(),
			];
		}

		$messages = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($returnType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() return type has no value type specified in iterable type %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($returnType) as [$name, $genericTypeNames]) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() return type with generic %s does not specify its types: %s',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$name,
				implode(', ', $genericTypeNames),
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($returnType) as $callableType) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() return type has no signature specified for %s.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $messages;
	}

}
