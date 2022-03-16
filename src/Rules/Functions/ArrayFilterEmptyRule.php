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
class ArrayFilterEmptyRule implements Rule
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

		if (strtolower($this->reflectionProvider->resolveFunctionName($node->name, $scope)) !== 'array_filter') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 1) {
			return [];
		}

		$arrayType = $scope->getType($args[0]->value);
		$falsyType = StaticTypeFactory::falsey();

		if ($falsyType->isSuperTypeOf($arrayType->getIterableValueType())->no()) {
			$message = 'Parameter #1 $array (%s) to function array_filter cannot contain empty values, call has no effect.';
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
