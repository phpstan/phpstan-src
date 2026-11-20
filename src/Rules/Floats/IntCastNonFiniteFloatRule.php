<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * (int) of NAN, INF or a float out of the int range gives an unspecified
 * int, and PHP 8.5 warns that the float is not representable.
 *
 * @implements Rule<Cast\Int_>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class IntCastNonFiniteFloatRule implements Rule
{

	use NonFiniteFloatToIntTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return Cast\Int_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->checkToInt($this->helper, $scope, $node->expr, 'Casting to int');
	}

}
