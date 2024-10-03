<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function array_key_exists;

final class VariadicFunctionsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	/** @var array<string, TrinaryLogic> */
	private array $variadicFunctions = [];

	public const ATTRIBUTE_NAME = 'variadicFunctions';

	public function __construct(
		private FunctionCallStatementFinder $functionCallStatementFinder,
	)
	{
	}

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicFunctions = [];
		$this->inNamespace = null;

		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if ($this->topNode === null) {
			$this->topNode = $node;
		}

		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = $node->name->toString();
		}

		if ($node instanceof Node\Stmt\Function_) {
			$functionName = $this->inNamespace !== null ? $this->inNamespace . '\\' . $node->name->name : $node->name->name;

			if (!array_key_exists($functionName, $this->variadicFunctions)) {
				$this->variadicFunctions[$functionName] = TrinaryLogic::createMaybe();
			}

			$isVariadic = $this->functionCallStatementFinder->findFunctionCallInStatements(ParametersAcceptor::VARIADIC_FUNCTIONS, $node->getStmts()) !== null;
			$this->variadicFunctions[$functionName] = $this->variadicFunctions[$functionName]->and(TrinaryLogic::createFromBoolean($isVariadic));
		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
		}

		return null;
	}

	public function afterTraverse(array $nodes): ?array
	{
		if ($this->topNode !== null && $this->variadicFunctions !== []) {
			$this->topNode->setAttribute(self::ATTRIBUTE_NAME, $this->variadicFunctions);
		}

		return null;
	}

}
