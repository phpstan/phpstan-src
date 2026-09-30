<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
final class ServiceRule implements Rule
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
		if (!$node->name instanceof Name || $node->name->getLast() !== 'service' || !isset($node->getArgs()[0]) || !$node->getArgs()[0]->value instanceof String_) {
			return [];
		}

		$id = $node->getArgs()[0]->value->value;
		$scope->valueDependency(HasServiceValueExtension::class, $id);
		if (Container::getService($id) !== null) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Service %s does not exist.', $id))->identifier('resultCacheE2E.service')->build(),
		];
	}

}
