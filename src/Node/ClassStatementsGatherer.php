<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use function count;
use function in_array;
use function strtolower;

class ClassStatementsGatherer
{

	private const PROPERTY_ENUMERATING_FUNCTIONS = [
		'get_object_vars',
		'array_walk',
	];

	/** @var callable(Node $node, Scope $scope): void */
	private $nodeCallback;

	/** @var ClassPropertyNode[] */
	private array $properties = [];

	/** @var ClassMethod[] */
	private array $methods = [];

	/** @var \PHPStan\Node\Method\MethodCall[] */
	private array $methodCalls = [];

	/** @var array<int, PropertyWrite|PropertyRead> */
	private array $propertyUsages = [];

	/** @var Node\Stmt\ClassConst[] */
	private array $constants = [];

	/** @var ClassConstantFetch[] */
	private array $constantFetches = [];

	/** @var array<string, MethodReturnStatementsNode> */
	private array $returnStatementNodes = [];

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function __construct(
		private ClassReflection $classReflection,
		callable $nodeCallback,
	)
	{
		$this->nodeCallback = $nodeCallback;
	}

	/**
	 * @return ClassPropertyNode[]
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return ClassMethod[]
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}

	/**
	 * @return Method\MethodCall[]
	 */
	public function getMethodCalls(): array
	{
		return $this->methodCalls;
	}

	/**
	 * @return array<int, PropertyWrite|PropertyRead>
	 */
	public function getPropertyUsages(): array
	{
		return $this->propertyUsages;
	}

	/**
	 * @return Node\Stmt\ClassConst[]
	 */
	public function getConstants(): array
	{
		return $this->constants;
	}

	/**
	 * @return ClassConstantFetch[]
	 */
	public function getConstantFetches(): array
	{
		return $this->constantFetches;
	}

	/**
	 * @return array<string, MethodReturnStatementsNode>
	 */
	public function getReturnStatementsNodes(): array
	{
		return $this->returnStatementNodes;
	}

	public function __invoke(Node $node, Scope $scope): void
	{
		$nodeCallback = $this->nodeCallback;
		$nodeCallback($node, $scope);
		$this->gatherNodes($node, $scope);
	}

	private function gatherNodes(Node $node, Scope $scope): void
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		if ($scope->getClassReflection()->getName() !== $this->classReflection->getName()) {
			return;
		}
		if ($node instanceof ClassPropertyNode) {
			$this->properties[] = $node;
			if ($node->isPromoted()) {
				$this->propertyUsages[] = new PropertyWrite(
					new PropertyFetch(new Expr\Variable('this'), new Identifier($node->getName())),
					$scope,
					true,
				);
			}
			return;
		}
		if ($node instanceof Node\Stmt\ClassMethod) {
			$this->methods[] = new ClassMethod($node, $scope->isInTrait());
			return;
		}
		if ($node instanceof Node\Stmt\ClassConst) {
			$this->constants[] = $node;
			return;
		}
		if ($node instanceof MethodCall || $node instanceof StaticCall) {
			$this->methodCalls[] = new \PHPStan\Node\Method\MethodCall($node, $scope);
			return;
		}
		if ($node instanceof MethodCallableNode || $node instanceof StaticMethodCallableNode) {
			$this->methodCalls[] = new \PHPStan\Node\Method\MethodCall($node->getOriginalNode(), $scope);
			return;
		}
		if ($node instanceof MethodReturnStatementsNode) {
			$this->returnStatementNodes[strtolower($node->getMethodName())] = $node;
			return;
		}
		if (
			$node instanceof Expr\FuncCall
			&& $node->name instanceof Node\Name
			&& in_array($node->name->toLowerString(), self::PROPERTY_ENUMERATING_FUNCTIONS, true)
		) {
			$this->tryToApplyPropertyReads($node, $scope);
			return;
		}
		if ($node instanceof Array_ && count($node->items) === 2) {
			$this->methodCalls[] = new \PHPStan\Node\Method\MethodCall($node, $scope);
			return;
		}
		if ($node instanceof Expr\ClassConstFetch) {
			$this->constantFetches[] = new ClassConstantFetch($node, $scope);
			return;
		}
		if ($node instanceof PropertyAssignNode) {
			$this->propertyUsages[] = new PropertyWrite($node->getPropertyFetch(), $scope, false);
			return;
		}
		if (!$node instanceof Expr) {
			return;
		}
		if ($node instanceof Expr\AssignOp\Coalesce) {
			$this->gatherNodes($node->var, $scope);
			return;
		}
		if ($node instanceof Expr\AssignRef) {
			if (!$node->expr instanceof PropertyFetch && !$node->expr instanceof StaticPropertyFetch) {
				$this->gatherNodes($node->expr, $scope);
				return;
			}

			$this->propertyUsages[] = new PropertyRead($node->expr, $scope);
			$this->propertyUsages[] = new PropertyWrite($node->expr, $scope, false);
			return;
		}
		if ($node instanceof FunctionCallableNode) {
			$node = $node->getOriginalNode();
		} elseif ($node instanceof InstantiationCallableNode) {
			$node = $node->getOriginalNode();
		}

		$inAssign = $scope->isInExpressionAssign($node);
		if ($inAssign) {
			return;
		}

		while ($node instanceof ArrayDimFetch) {
			$node = $node->var;
		}
		if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch) {
			return;
		}

		$this->propertyUsages[] = new PropertyRead($node, $scope);
	}

	private function tryToApplyPropertyReads(Expr\FuncCall $node, Scope $scope): void
	{
		$args = $node->getArgs();
		if (count($args) === 0) {
			return;
		}

		$firstArgValue = $args[0]->value;
		if (TypeUtils::findThisType($scope->getType($firstArgValue)) === null) {
			return;
		}

		$classProperties = $this->classReflection->getNativeReflection()->getProperties();
		foreach ($classProperties as $property) {
			if ($property->isStatic()) {
				continue;
			}
			$this->propertyUsages[] = new PropertyRead(
				new PropertyFetch(new Expr\Variable('this'), new Identifier($property->getName())),
				$scope,
			);
		}
	}

}
