<?php declare(strict_types = 1);

namespace PHPStan\Rules\String;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\DeprecatedInterpolatedStringVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;

/**
 * @implements Rule<InterpolatedString>
 */
#[RegisteredRule(level: 0)]
final class InterpolatedStringRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return InterpolatedString::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesStringInterpolation()) {
			return [];
		}

		if ($node->getAttribute(DeprecatedInterpolatedStringVisitor::ATTRIBUTE_NAME) !== true) {
			return [];
		}

		foreach ($node->parts as $part) {
			if (!$part instanceof Variable && !($part instanceof ArrayDimFetch && $part->var instanceof Variable)) {
				continue;
			}

			if ($part instanceof ArrayDimFetch || (is_string($part->name))) {
				$deprecatedMessage = 'Using ${var} in strings is deprecated in PHP 8.2. Use {$var} instead.';
			} else {
				$deprecatedMessage = 'Using ${expr} (variable variables) in strings is deprecated in PHP 8.2. Use {${expr}} instead.';
			}

			return [
				RuleErrorBuilder::message($deprecatedMessage)
					->identifier('interpolatedstring.deprecated')
					->line($node->getStartLine())
					->fixNode($node, static fn () => new InterpolatedString($node->parts, $node->getAttributes()))
					->build(),
			];
		}

		return [];
	}

}
