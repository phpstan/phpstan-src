<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\TraitUse>
 */
final class ApiTraitUseRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\TraitUse::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);
		foreach ($node->traits as $traitName) {
			$traitName = $traitName->toString();
			if (!$this->reflectionProvider->hasClass($traitName)) {
				continue;
			}

			$traitReflection = $this->reflectionProvider->getClass($traitName);
			if (!$this->apiRuleHelper->isPhpStanCode($scope, $traitReflection->getName(), $traitReflection->getFileName())) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Using %s is not covered by backward compatibility promise. The trait might change in a minor PHPStan version.',
				$traitReflection->getDisplayName(),
			))->identifier('phpstanApi.trait')->tip($tip)->build();
		}

		return $errors;
	}

}
