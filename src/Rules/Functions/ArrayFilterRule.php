<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
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

	public function __construct(
		private bool $treatPhpDocTypesAsCertain,
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

		$functionName = strtolower($node->name->toString());
		if ($functionName !== 'array_filter') {
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
			$message = 'Parameter #1 $array (%s) to function array_filter does not contain falsy values, the array will always stay the same.';
			return [
				RuleErrorBuilder::message(sprintf(
					$message,
					$arrayType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		if ($isSuperType->yes()) {
			$message = 'Parameter #1 $array (%s) to function array_filter contains falsy values only, the result will always be an empty array.';
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
