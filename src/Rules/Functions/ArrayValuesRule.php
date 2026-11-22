<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 5)]
final class ArrayValuesRule implements Rule
{

	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private readonly bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($functionReflection->getName() !== 'array_values') {
			return [];
		}

		$arrayArg = ArgumentsNormalizer::getArgByPositionOrName($node->getArgs(), 0, 'array');
		if ($arrayArg === null) {
			return [];
		}

		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($arrayArg->value);
		} else {
			$arrayType = $scope->getNativeType($arrayArg->value);
		}

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			$message = 'Parameter #1 $array (%s) to function array_values is empty, call has no effect.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayValues.empty');
			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($arrayArg->value);
				if ($this->treatPhpDocTypesAsCertainTip && !$nativeArrayType->isIterableAtLeastOnce()->no()) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		if ($arrayType->isList()->yes()) {
			$message = 'Parameter #1 $array (%s) of array_values is already a list, call has no effect.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayValues.list');
			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($arrayArg->value);
				if ($this->treatPhpDocTypesAsCertainTip && !$nativeArrayType->isList()->yes()) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		return [];
	}

}
