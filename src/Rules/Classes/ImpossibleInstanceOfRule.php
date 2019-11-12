<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Instanceof_>
 */
class ImpossibleInstanceOfRule implements \PHPStan\Rules\Rule
{

	/** @var bool */
	private $checkAlwaysTrueInstanceof;

	public function __construct(bool $checkAlwaysTrueInstanceof)
	{
		$this->checkAlwaysTrueInstanceof = $checkAlwaysTrueInstanceof;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$instanceofType = $scope->getType($node);
		$expressionType = $scope->getType($node->expr);

		if ($node->class instanceof Node\Name) {
			$className = $scope->resolveName($node->class);
			$classType = new ObjectType($className);
		} else {
			$classType = $scope->getType($node->class);
			$allowed = TypeCombinator::union(
				new StringType(),
				new ObjectWithoutClassType()
			);
			if (!$allowed->accepts($classType, true)->yes()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Instanceof between %s and %s results in an error.',
						$expressionType->describe(VerbosityLevel::typeOnly()),
						$classType->describe(VerbosityLevel::typeOnly())
					))->build(),
				];
			}
		}

		if (!$instanceofType instanceof ConstantBooleanType) {
			return [];
		}

		if (!$instanceofType->getValue()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Instanceof between %s and %s will always evaluate to false.',
					$expressionType->describe(VerbosityLevel::typeOnly()),
					$classType->describe(VerbosityLevel::typeOnly())
				))->build(),
			];
		} elseif ($this->checkAlwaysTrueInstanceof) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Instanceof between %s and %s will always evaluate to true.',
					$expressionType->describe(VerbosityLevel::typeOnly()),
					$classType->describe(VerbosityLevel::typeOnly())
				))->build(),
			];
		}

		return [];
	}

}
