<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\Print_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Print_>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class PrintNanRule implements Rule
{

	use NanToStringHelperTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return Print_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->checkNanToString($this->helper, $scope, $node->expr, 'Print');
	}

}
