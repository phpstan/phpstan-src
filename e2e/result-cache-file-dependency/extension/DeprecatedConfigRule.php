<?php declare(strict_types = 1);

namespace ResultCacheE2EFileDependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Like ConfigRule, with the deprecated RuleErrorBuilder::fileDependency(), which can declare the
 * dependency only along with an error.
 *
 * @implements Rule<FuncCall>
 */
final class DeprecatedConfigRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name || $node->name->getLast() !== 'checkDeprecatedConfig') {
			return [];
		}

		$configFile = dirname(__DIR__) . '/data/deprecated.txt';
		$contents = @file_get_contents($configFile);
		if ($contents === false || !str_contains($contents, 'report')) {
			return [];
		}

		return [
			// @phpstan-ignore method.deprecated
			RuleErrorBuilder::message('Deprecated config asks for an error.')->identifier('resultCacheE2E.deprecatedConfig')->fileDependency($configFile)->build(),
		];
	}

}
