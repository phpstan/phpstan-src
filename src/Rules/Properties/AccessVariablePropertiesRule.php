<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\PropertyFetch>
 */
class AccessVariablePropertiesRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private ExprPrinter $printer,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Variable) {
			return [];
		}

		$variableName = $node->name->name;
		$fetchType = $scope->getType($node->name)->toString();
		if (!$fetchType instanceof ErrorType) {
			return [];
		}

		$error = RuleErrorBuilder::message(sprintf(
			'Unsafe %s variable property access with a non-string value.',
			$this->printer->printExpr($node),
		));

		if (is_string($variableName)) {
			$propertyNode = new PropertyFetch($node->var, new Identifier($variableName));
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $propertyNode),
				'',
				static fn (Type $type): bool => $type->canAccessProperties()->yes() && $type->hasProperty($variableName)->yes(),
			);
			$type = $typeResult->getType();

			if (!$type instanceof ErrorType) {
				$error->tip(sprintf('Did you mean `%s`? The $ following -> is likely unnecessary.', $this->printer->printExpr($propertyNode)));
			}
		}

		return [
			$error->build(),
		];
	}

}
