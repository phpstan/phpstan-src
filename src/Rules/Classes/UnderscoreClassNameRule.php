<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Stmt\ClassLike>
 */
#[RegisteredRule(level: 0)]
final class UnderscoreClassNameRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->deprecatesUnderscoreClassName()) {
			return [];
		}

		if ($node->name === null || $node->name->toString() !== '_') {
			return [];
		}

		if ($node instanceof Stmt\Interface_) {
			$kind = 'an interface';
		} elseif ($node instanceof Stmt\Trait_) {
			$kind = 'a trait';
		} elseif ($node instanceof Stmt\Enum_) {
			$kind = 'an enum';
		} else {
			$kind = 'a class';
		}

		return [
			RuleErrorBuilder::message(sprintf('Using "_" as %s name is deprecated in PHP 8.4.', $kind))
				->identifier('classLike.underscoreName')
				->build(),
		];
	}

}
