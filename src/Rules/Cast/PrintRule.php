<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Print_>
 */
class PrintRule implements Rule
{

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Print_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$type = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'',
			static function (Type $type): bool {
				return !$type->toString() instanceof ErrorType;
			}
		)->getType();

		if ($type instanceof ErrorType) {
			return [];
		}

		if (
			$type->toString() instanceof ErrorType
			|| $type instanceof MixedType && $this->ruleLevelHelper->shouldCheckMixed($type)
		) {
			return [RuleErrorBuilder::message(sprintf(
				'Parameter %s of print cannot be converted to string.',
				$type->describe(VerbosityLevel::value())
			))->line($node->expr->getLine())->build()];
		}

		return [];
	}

}
