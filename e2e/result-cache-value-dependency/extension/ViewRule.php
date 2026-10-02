<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports view('name') when there's no views/name.html - whether there is depends on the views/
 * directory, not on a single file.
 *
 * @implements Rule<FuncCall>
 */
final class ViewRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name || $node->name->getLast() !== 'view' || !isset($node->getArgs()[0]) || !$node->getArgs()[0]->value instanceof String_) {
			return [];
		}

		$directory = dirname(__DIR__) . '/views';
		$scope->trackDirectoryDependency($directory, '*.html');

		$name = $node->getArgs()[0]->value->value;
		if (is_file($directory . '/' . $name . '.html')) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('View %s does not exist.', $name))->identifier('resultCacheE2E.view')->build(),
		];
	}

}
