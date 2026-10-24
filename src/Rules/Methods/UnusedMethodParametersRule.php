<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Node\Method\MethodVariableWrites;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrors\TransformedRuleError;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * Reports unused parameters of private methods: a private method has no
 * callers outside the class, so its signature is not a contract - unlike a
 * public or protected one, whose parameters may be dictated by an interface,
 * a parent or an override. A private method the class hands out as a callable
 * (first-class callable syntax or a callable array) escapes the class, so it
 * is skipped as well.
 *
 * Errors are reported with the scope inside the method, as if the rule
 * listened to the method's VariableWritesNode, so a method declared in a
 * trait is reported in the trait's file.
 *
 * @implements Rule<ClassMethodsNode>
 */
final class UnusedMethodParametersRule implements Rule
{

	public function __construct(
		private UnusedParametersCheck $check,
		private RuleErrorTransformer $ruleErrorTransformer,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassMethodsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$referencedAsCallable = null;
		$errors = [];
		foreach ($node->getMethodVariableWrites() as $methodVariableWrites) {
			$methodErrors = $this->getMethodErrors($methodVariableWrites);
			if (count($methodErrors) === 0) {
				continue;
			}

			$referencedAsCallable ??= $this->getMethodsReferencedAsCallable($node);
			if ($referencedAsCallable === true) {
				return [];
			}

			$methodNode = $methodVariableWrites->getNode()->getFunctionLike();
			if (!$methodNode instanceof Node\Stmt\ClassMethod) {
				continue;
			}
			if (isset($referencedAsCallable[$methodNode->name->toLowerString()])) {
				continue;
			}

			foreach ($methodErrors as $methodError) {
				$errors[] = new TransformedRuleError($this->ruleErrorTransformer->transform(
					$methodError,
					$methodVariableWrites->getScope(),
					[],
					$methodVariableWrites->getNode(),
				));
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function getMethodErrors(MethodVariableWrites $methodVariableWrites): array
	{
		$variableWritesNode = $methodVariableWrites->getNode();
		$scope = $methodVariableWrites->getScope();
		$originalNode = $variableWritesNode->getFunctionLike();
		if (!$originalNode instanceof Node\Stmt\ClassMethod) {
			return [];
		}
		if (!$originalNode->isPrivate() || $originalNode->stmts === null || $this->check->isNoOpBody($originalNode->stmts)) {
			return [];
		}
		if (str_starts_with($originalNode->name->toString(), '__')) {
			// a magic method's signature is dictated by the engine, and the
			// constructor has its own rule
			return [];
		}
		if (count($originalNode->params) === 0) {
			return [];
		}
		if (!$scope->isInClass()) {
			return [];
		}
		$method = $scope->getFunction();
		if ($method === null) {
			return [];
		}

		$methodDescription = sprintf(
			'%s::%s()',
			SprintfHelper::escapeFormatString($scope->getClassReflection()->getDisplayName()),
			SprintfHelper::escapeFormatString($originalNode->name->toString()),
		);

		return $this->check->getUnusedParameterErrors(
			$variableWritesNode,
			$method,
			$originalNode->params,
			sprintf('Method %s has an unused parameter $%%s.', $methodDescription),
			'method.unusedParameter',
			true,
			sprintf('Method %s has a parameter $%%s that only flows into values that are never used.', $methodDescription),
			'method.unusedParameterFlow',
		);
	}

	/**
	 * Lowercased names of the methods the class references as callables.
	 * True when a first-class callable's method name cannot be resolved, so
	 * any method may be referenced.
	 *
	 * @return array<string, true>|true
	 */
	private function getMethodsReferencedAsCallable(ClassMethodsNode $node): array|bool
	{
		$methodNames = [];
		foreach ($node->getMethodCalls() as $methodCall) {
			$callNode = $methodCall->getNode();
			$callScope = $methodCall->getScope();
			if ($callNode instanceof Node\Expr\Array_) {
				$arrayType = $callScope->getType($callNode);
				if ($arrayType->isCallable()->no()) {
					continue;
				}
				foreach ($arrayType->getConstantArrays() as $constantArray) {
					foreach ($constantArray->findTypeAndMethodNames() as $typeAndMethod) {
						if ($typeAndMethod->isUnknown()) {
							continue;
						}
						$methodNames[strtolower($typeAndMethod->getMethod())] = true;
					}
				}
				continue;
			}

			if (!$callNode->isFirstClassCallable()) {
				continue;
			}
			if ($callNode->name instanceof Node\Identifier) {
				$methodNames[$callNode->name->toLowerString()] = true;
				continue;
			}

			$constantStrings = $callScope->getType($callNode->name)->getConstantStrings();
			if (count($constantStrings) === 0) {
				return true;
			}
			foreach ($constantStrings as $constantString) {
				$methodNames[strtolower($constantString->getValue())] = true;
			}
		}

		return $methodNames;
	}

}
