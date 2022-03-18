<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ArrayFilterRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
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

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);

		if ($functionName === null || strtolower($functionName) !== 'array_filter') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 1) {
			return [];
		}

		$arrayType = $scope->getType($args[0]->value);

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			$message = 'Parameter #1 $array (%s) to function array_filter is empty, call has no effect.';
			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$arrayType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		$falsyType = StaticTypeFactory::falsey();
		$isSuperType = $falsyType->isSuperTypeOf($arrayType->getIterableValueType());

		if ($isSuperType->no()) {
			$message = 'Parameter #1 $array (%s) to function array_filter cannot contain empty values, call has no effect.';
			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$arrayType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		if ($isSuperType->yes()) {
			$message = 'Parameter #1 $array (%s) to function array_filter can only contain empty values, call always results in [].';
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
