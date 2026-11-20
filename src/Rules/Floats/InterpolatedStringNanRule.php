<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<InterpolatedString>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class InterpolatedStringNanRule implements Rule
{

	use NanToStringHelperTrait;

	public function __construct(private NonFiniteFloatHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return InterpolatedString::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->parts as $part) {
			if (!$part instanceof Node\Expr) {
				continue;
			}
			foreach ($this->checkNanToString($this->helper, $scope, $part, 'String interpolation') as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

}
