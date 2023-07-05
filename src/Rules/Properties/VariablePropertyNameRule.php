<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<PropertyFetch>
 */
class VariablePropertyNameRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return PropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Expr) {
			return [];
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->name,
			'',
			static fn (Type $type): bool => !$type->toString() instanceof ErrorType,
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return [];
		}
		if (!$type->toString() instanceof ErrorType) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Access to property with non-string name expression %s.',
				$type->describe(VerbosityLevel::typeOnly()),
			))->identifier('property.nonStringName')->build(),
		];
	}

}
