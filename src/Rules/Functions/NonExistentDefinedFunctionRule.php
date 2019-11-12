<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Function_>
 */
class NonExistentDefinedFunctionRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	public function __construct(Broker $broker)
	{
		$this->broker = $broker;
	}

	public function getNodeType(): string
	{
		return Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionName = $node->name->name;
		if (isset($node->namespacedName)) {
			$functionName = (string) $node->namespacedName;
		}
		$functionNameName = new Name($functionName);
		if ($this->broker->hasFunction($functionNameName, null)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Function %s not found while trying to analyse it - autoloading is probably not configured properly.',
				$functionName
			))->build(),
		];
	}

}
