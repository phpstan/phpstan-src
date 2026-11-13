<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use function count;
use function in_array;
use function sprintf;
use const E_USER_ERROR;

/**
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 0),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class TriggerErrorUserErrorRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->deprecatesTriggerErrorWithUserError()->no()) {
			return [];
		}

		if (!$node->name instanceof Node\Name || count($node->getArgs()) < 2) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (!in_array($functionReflection->getName(), ['trigger_error', 'user_error'], true)) {
			return [];
		}

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$functionReflection->getVariants(),
				$functionReflection->getNamedArgumentsVariants(),
			),
			$node,
		);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (!isset($args[1]) || !(new ConstantIntegerType(E_USER_ERROR))->isSuperTypeOf($scope->getType($args[1]->value))->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Passing E_USER_ERROR to %s() is deprecated in PHP 8.4.',
				$functionReflection->getName(),
			))
				->tip('Throw an exception or call exit() with a string message instead.')
				->identifier('argument.deprecatedUserError')
				->build(),
		];
	}

}
