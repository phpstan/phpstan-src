<?php declare(strict_types = 1);

namespace ResultCacheE2EFileDependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports calls to checkConfig() when src/Config.php asks for it. The file is read whether an
 * error is reported or not, so the dependency is declared either way.
 *
 * @implements Rule<FuncCall>
 */
final class ConfigRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	/**
	 * @param Scope&DependencyEmitter $scope
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name || $node->name->getLast() !== 'checkConfig') {
			return [];
		}

		$configFile = dirname(__DIR__) . '/src/Config.php';
		$scope->fileDependency($configFile);

		if (!is_file($configFile)) {
			return [];
		}

		$contents = file_get_contents($configFile);
		if ($contents === false || !str_contains($contents, 'report')) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Config asks for an error.')->identifier('resultCacheE2E.config')->build(),
		];
	}

}
