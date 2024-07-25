<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Clone_>
 */
final class VariableCloningRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Clone_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'Cloning object of an unknown class %s.',
			static fn (Type $type): bool => $type->isCloneable()->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}
		if ($type->isCloneable()->yes()) {
			return [];
		}

		if ($node->expr instanceof Variable && is_string($node->expr->name)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot clone non-object variable $%s of type %s.',
					$node->expr->name,
					$type->describe(VerbosityLevel::typeOnly()),
				))->identifier('clone.nonObject')->build(),
			];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Cannot clone %s.',
				$type->describe(VerbosityLevel::typeOnly()),
			))->identifier('clone.nonObject')->build(),
		];
	}

}
