<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Print_>
 */
class PrintRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Print_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'',
			static fn (Type $type): bool => !$type->toString() instanceof ErrorType,
		);

		if (!$typeResult->getType() instanceof ErrorType
			&& $typeResult->getType()->toString() instanceof ErrorType
		) {
			return [RuleErrorBuilder::message(sprintf(
				'Parameter %s of print cannot be converted to string.',
				$typeResult->getType()->describe(VerbosityLevel::value()),
			))->identifier('print.nonString')->line($node->expr->getStartLine())->build()];
		}

		return [];
	}

}
