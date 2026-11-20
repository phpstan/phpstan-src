<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Cast\String_>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class StringCastNanRule implements Rule
{

	use NanToStringHelperTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return Cast\String_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->checkNanToString($this->helper, $scope, $node->expr, 'Casting to string');
	}

}
