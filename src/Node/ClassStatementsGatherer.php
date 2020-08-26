<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflectionWithNode;

class ClassStatementsGatherer
{

	private ClassReflection $classReflection;

	private Scope $classScope;

	/** @var callable(\PhpParser\Node $node, Scope $scope): void */
	private $nodeCallback;

	/** @var \PhpParser\Node\Stmt\Property[] */
	private array $properties = [];

	/** @var \PhpParser\Node\Stmt\ClassMethod[] */
	private array $methods = [];

	/** @var \PHPStan\Node\Method\MethodCall[] */
	private array $methodCalls = [];

	/** @var array<int, PropertyWrite|PropertyRead> */
	private array $propertyUsages = [];

	/** @var \PhpParser\Node\Stmt\ClassConst[] */
	private array $constants = [];

	/** @var ClassConstantFetch[] */
	private array $constantFetches = [];

	/**
	 * @param ClassReflection $classReflection
	 * @param Scope $classScope
	 * @param callable(\PhpParser\Node $node, Scope $scope): void $nodeCallback
	 */
	public function __construct(
		ClassReflection $classReflection,
		Scope $classScope,
		callable $nodeCallback
	)
	{
		$this->classReflection = $classReflection;
		$this->classScope = $classScope;
		$this->nodeCallback = $nodeCallback;
	}

	/**
	 * @return \PhpParser\Node\Stmt\Property[]
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return \PhpParser\Node\Stmt\ClassMethod[]
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
	 * @return \PhpParser\Node\Stmt\ClassConst[]
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

	public function __invoke(\PhpParser\Node $node, Scope $scope): void
	{
		$nodeCallback = $this->nodeCallback;
		$nodeCallback($node, $scope);
		$this->gatherNodes($node, $scope);
	}

	private function gatherNodes(\PhpParser\Node $node, Scope $scope): void
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		if ($scope->getClassReflection()->getName() !== $this->classReflection->getName()) {
			return;
		}
		if ($node instanceof \PhpParser\Node\Stmt\Property && !$scope->isInTrait()) {
			$this->properties[] = $node;
			return;
		}
		if ($node instanceof \PhpParser\Node\Stmt\ClassMethod && !$scope->isInTrait()) {
			$this->methods[] = $node;
			return;
		}
		if ($node instanceof \PhpParser\Node\Stmt\ClassConst) {
			$this->constants[] = $node;
			return;
		}
		if ($node instanceof MethodCall || $node instanceof StaticCall) {
			$this->methodCalls[] = new \PHPStan\Node\Method\MethodCall($node, $scope);
			if ($node instanceof MethodCall && $node->name instanceof \PhpParser\Node\Identifier) {
				$calleeType = $scope->getType($node->var);
				$methodName = $node->name->toString();
				if ($calleeType->hasMethod($methodName)->yes()) {
					$methodReflection = $calleeType->getMethod($methodName, $scope);
					if (
						$methodReflection instanceof MethodReflectionWithNode
						&& $methodReflection->getDeclaringClass()->getName() === $this->classReflection->getName()
						&& $methodReflection->getNode() !== null
					) {
						//$this->processNodes([$methodReflection->getNode()], $this->classScope, $this);
					}
				}
			}
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
		if (!$node instanceof Expr) {
			return;
		}
		if ($node instanceof Expr\AssignOp\Coalesce) {
			$this->gatherNodes($node->var, $scope);
			return;
		}
		if ($node instanceof \PhpParser\Node\Scalar\EncapsedStringPart) {
			return;
		}
		$inAssign = $scope->isInExpressionAssign($node);
		while ($node instanceof ArrayDimFetch) {
			$node = $node->var;
		}
		if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch) {
			return;
		}

		if ($inAssign) {
			$this->propertyUsages[] = new PropertyWrite($node, $scope);
		} else {
			$this->propertyUsages[] = new PropertyRead($node, $scope);
		}
	}

}
