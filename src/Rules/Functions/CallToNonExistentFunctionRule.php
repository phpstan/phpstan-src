<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class CallToNonExistentFunctionRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var bool */
	private $checkFunctionNameCase;

	public function __construct(
		Broker $broker,
		bool $checkFunctionNameCase
	)
	{
		$this->broker = $broker;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}

		if (!$this->broker->hasFunction($node->name, $scope)) {
			return [
				RuleErrorBuilder::message(sprintf('Function %s not found.', (string) $node->name))->build(),
			];
		}

		$function = $this->broker->getFunction($node->name, $scope);
		$name = (string) $node->name;

		if ($this->checkFunctionNameCase) {
			/** @var string $calledFunctionName */
			$calledFunctionName = $this->broker->resolveFunctionName($node->name, $scope);
			if (
				strtolower($function->getName()) === strtolower($calledFunctionName)
				&& $function->getName() !== $calledFunctionName
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Call to function %s() with incorrect case: %s',
						$function->getName(),
						$name
					))->build(),
				];
			}
		}

		return [];
	}

}
