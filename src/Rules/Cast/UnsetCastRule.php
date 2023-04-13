<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Cast\Unset_>
 */
class UnsetCastRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Cast\Unset_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->phpVersion->supportsUnsetCast()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('The (unset) cast is no longer supported in PHP 8.0 and later.')
				->identifier('cast.unset')
				->nonIgnorable()
				->build(),
		];
	}

}
