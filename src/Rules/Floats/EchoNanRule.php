<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Echo_>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class EchoNanRule implements Rule
{

	use NanToStringHelperTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->exprs as $expr) {
			foreach ($this->checkNanToString($this->helper, $scope, $expr, 'Echo') as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

}
