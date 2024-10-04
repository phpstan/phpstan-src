<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function array_key_exists;
use function in_array;

final class VariadicFunctionsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	private ?string $inFunction = null;

	/** @var array<string, TrinaryLogic> */
	private array $variadicFunctions = [];

	public const ATTRIBUTE_NAME = 'variadicFunctions';

	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicFunctions = [];
		$this->inNamespace = null;
		$this->inFunction = null;

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
			$this->inFunction = $this->inNamespace !== null ? $this->inNamespace . '\\' . $node->name->name : $node->name->name;

			foreach ($node->params as $parameter) {
				if (!$parameter->variadic) {
					continue;
				}

				if (!array_key_exists($this->inFunction, $this->variadicFunctions)) {
					$this->variadicFunctions[$this->inFunction] = TrinaryLogic::createYes();
				} else {
					$this->variadicFunctions[$this->inFunction]->and(TrinaryLogic::createYes());
				}
			}
		}

		if (
			$this->inFunction !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			if (!array_key_exists($this->inFunction, $this->variadicFunctions)) {
				$this->variadicFunctions[$this->inFunction] = TrinaryLogic::createYes();
			} else {
				$this->variadicFunctions[$this->inFunction]->and(TrinaryLogic::createYes());
			}
		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
		}

		if ($node instanceof Node\Stmt\Function_ && $this->inFunction !== null) {
			$this->variadicFunctions[$this->inFunction] ??= TrinaryLogic::createNo();
			$this->inFunction = null;
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
