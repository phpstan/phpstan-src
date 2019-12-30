<?php declare(strict_types=1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BinaryOp\Coalesce>
 */
class InvalidNullCoalesce implements Rule
{

	/**
	 * @phpstan-return class-string<\PhpParser\Node\Expr\BinaryOp\Coalesce>
	 * @return string
	 */
	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp\Coalesce::class;
	}

	/**
	 * @phpstan-param \PhpParser\Node\Expr\BinaryOp\Coalesce $node
	 * @param \PhpParser\Node $node
	 * @param \PHPStan\Analyser\Scope $scope
	 * @return RuleError[] errors
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$type = $scope->getType($node->left);
		$onlyNull = $type instanceof NullType;
		if($onlyNull || $type->accepts(new NullType(), $scope->isDeclareStrictTypes())->no()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Null coalesce on type %s is always %s.',
					$type->describe(VerbosityLevel::value()),
					$onlyNull ? 'true' : 'false'
				))->line($node->left->getLine())->build(),
			];
		}
		return [];
	}
}
