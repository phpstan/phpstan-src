<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ArrayValuesRule implements Rule
{

	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly bool $treatPhpDocTypesAsCertain,
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

		if (AccessoryArrayListType::isListTypeEnabled() === false) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);

		if ($functionName === null || strtolower($functionName) !== 'array_values') {
			return [];
		}

		$args = $node->getArgs();

		if (count($args) !== 1) {
			return [];
		}

		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($args[0]->value);
		} else {
			$arrayType = $scope->getNativeType($args[0]->value);
		}

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			$message = 'Parameter #1 $array (%s) to function array_values is empty, call has no effect.';

			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$arrayType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		if ($arrayType->isList()->yes() && $arrayType->isIterable()->yes()) {
			$message = 'Parameter #1 $array (%s) of array_values is already a list, call has no effect.';

			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$arrayType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		return [];
	}

}
