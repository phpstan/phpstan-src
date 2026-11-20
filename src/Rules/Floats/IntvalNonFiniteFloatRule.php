<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use function count;
use function strtolower;

/**
 * intval() of NAN, INF or a float out of the int range, see
 * IntCastNonFiniteFloatRule.
 *
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class IntvalNonFiniteFloatRule implements Rule
{

	use NonFiniteFloatToIntTrait;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private NonFiniteFloatHelper $helper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$name = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		if ($name === null || strtolower($name) !== 'intval') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) === 0 || $args[0]->unpack) {
			return [];
		}

		return $this->checkToInt($this->helper, $scope, $args[0]->value, 'Function intval()');
	}

}
