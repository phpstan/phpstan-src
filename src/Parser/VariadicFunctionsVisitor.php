<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptor;
use function array_filter;
use function array_key_exists;
use function in_array;

#[AutowiredService]
final class VariadicFunctionsVisitor extends NodeVisitorAbstract
{

	private ?Node $topNode = null;

	private ?string $inNamespace = null;

	private ?string $inFunction = null;

	/** @var array<string, bool> */
	public static array $cache = [];

	/** @var array<string, bool> */
	private array $variadicFunctions = [];

	public const ATTRIBUTE_NAME = 'variadicFunctions';

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->topNode = null;
		$this->variadicFunctions = [];
		$this->inNamespace = null;
		$this->inFunction = null;

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		$this->topNode ??= $node;

		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = $node->name->toString();
		}

		if ($node instanceof Node\Stmt\Function_) {
			$this->inFunction = $this->inNamespace !== null ? $this->inNamespace . '\\' . $node->name->name : $node->name->name;
		}

		if (
			$this->inFunction !== null
			&& $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array((string) $node->name, ParametersAcceptor::VARIADIC_FUNCTIONS, true)
			&& !array_key_exists($this->inFunction, $this->variadicFunctions)
		) {
			$this->variadicFunctions[$this->inFunction] = true;
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Namespace_ && $node->name !== null) {
			$this->inNamespace = null;
		}

		if ($node instanceof Node\Stmt\Function_ && $this->inFunction !== null) {
			$this->variadicFunctions[$this->inFunction] ??= false;
			$this->inFunction = null;
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		if ($this->topNode !== null && $this->variadicFunctions !== []) {
			foreach ($this->variadicFunctions as $name => $variadic) {
				self::$cache[$name] = $variadic;
			}
			$functions = array_filter($this->variadicFunctions, static fn (bool $variadic) => $variadic);
			$this->topNode->setAttribute(self::ATTRIBUTE_NAME, $functions);
		}

		return null;
	}

}
