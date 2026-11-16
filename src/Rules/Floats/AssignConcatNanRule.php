<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\AssignOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Concat>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class AssignConcatNanRule implements Rule
{

	use NanToStringHelperTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return Concat::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->checkNanToString($this->helper, $scope, $node->expr, 'Concatenation');
	}

}
