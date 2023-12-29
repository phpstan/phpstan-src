<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Throwable;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Throw_>
 */
class ThrowExprTypeRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Throw_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$throwableType = new ObjectType(Throwable::class);
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'Throwing object of an unknown class %s.',
			static fn (Type $type): bool => $throwableType->isSuperTypeOf($type)->yes(),
		);

		$foundType = $typeResult->getType();
		if ($foundType instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$isSuperType = $throwableType->isSuperTypeOf($foundType);
		if ($isSuperType->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Invalid type %s to throw.',
				$foundType->describe(VerbosityLevel::typeOnly()),
			))->identifier('throw.notThrowable')->build(),
		];
	}

}
