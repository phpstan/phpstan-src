<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\PropertyHook;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;

/**
 * @api
 */
final class PropertyHookReturnStatementsNode extends NodeAbstract implements ReturnStatementsNode
{

	/**
	 * @param list<ReturnStatement> $returnStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param ImpurePoint[] $impurePoints
	 */
	public function __construct(
		private PropertyHook $hook,
		private array $returnStatements,
		private StatementResult $statementResult,
		private array $executionEnds,
		private array $impurePoints,
		private ClassReflection $classReflection,
		private PhpMethodFromParserNodeReflection $hookReflection,
		private PhpPropertyReflection $propertyReflection,
	)
	{
		parent::__construct($hook->getAttributes());
	}

	public function getPropertyHookNode(): PropertyHook
	{
		return $this->hook;
	}

	public function returnsByRef(): bool
	{
		return $this->hook->byRef;
	}

	public function hasNativeReturnTypehint(): bool
	{
		return false;
	}

	public function getYieldStatements(): array
	{
		return [];
	}

	public function isGenerator(): bool
	{
		return false;
	}

	public function getReturnStatements(): array
	{
		return $this->returnStatements;
	}

	public function getStatementResult(): StatementResult
	{
		return $this->statementResult;
	}

	public function getExecutionEnds(): array
	{
		return $this->executionEnds;
	}

	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	public function getClassReflection(): ClassReflection
	{
		return $this->classReflection;
	}

	public function getHookReflection(): PhpMethodFromParserNodeReflection
	{
		return $this->hookReflection;
	}

	public function getPropertyReflection(): PhpPropertyReflection
	{
		return $this->propertyReflection;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_PropertyHookReturnStatementsNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
