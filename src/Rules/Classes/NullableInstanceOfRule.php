<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class NullableInstanceOfRule implements Rule
{

	public function __construct(
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			$classType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->class) : $scope->getNativeType($node->class);
			$nullType = new NullType();
			if (!$nullType->isSuperTypeOf($classType)->no()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Classname in instanceof cannot be null, %s given.',
						$classType->describe(VerbosityLevel::typeOnly()),
					))->build(),
				];
			}
		}

		return [];
	}

}
