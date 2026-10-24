<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;

/**
 * Reports unused parameters of private methods: a private method has no
 * callers outside the class, so its signature is not a contract - unlike a
 * public or protected one, whose parameters may be dictated by an interface,
 * a parent or an override. A private method referenced as a callable
 * (first-class callable syntax, a callable array or a callable string) escapes
 * the class, so it is skipped as well.
 *
 * @implements Rule<VariableWritesNode>
 */
final class UnusedMethodParametersRule implements Rule
{

	public function __construct(
		private UnusedParametersCheck $check,
		private Parser $parser,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getFunctionLike();
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

		$errors = $this->check->getUnusedParameterErrors(
			$node,
			$method,
			$originalNode->params,
			sprintf('Method %s has an unused parameter $%%s.', $methodDescription),
			'method.unusedParameter',
			true,
			sprintf('Method %s has a parameter $%%s that only flows into values that are never used.', $methodDescription),
			'method.unusedParameterFlow',
		);
		if (count($errors) === 0) {
			return [];
		}

		$methodName = $originalNode->name->toString();
		if ($this->isReferencedAsCallable($scope->getClassReflection(), $methodName)) {
			return [];
		}
		$traitReflection = $scope->getTraitReflection();
		if ($traitReflection !== null && $this->isReferencedAsCallable($traitReflection, $methodName)) {
			return [];
		}

		return $errors;
	}

	private function isReferencedAsCallable(ClassReflection $classReflection, string $methodName): bool
	{
		$fileName = $classReflection->getFileName();
		if ($fileName === null) {
			return false;
		}

		$startLine = $classReflection->getNativeReflection()->getStartLine();
		$nodeFinder = new NodeFinder();
		$classNodes = $nodeFinder->find(
			$this->parser->parseFile($fileName),
			static fn (Node $node): bool => $node instanceof Node\Stmt\ClassLike && $node->getStartLine() === $startLine,
		);

		$lowerMethodName = strtolower($methodName);
		foreach ($classNodes as $classNode) {
			$reference = $nodeFinder->findFirst($classNode, static function (Node $node) use ($lowerMethodName): bool {
				if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
					return $node->isFirstClassCallable()
						&& $node->name instanceof Node\Identifier
						&& $node->name->toLowerString() === $lowerMethodName;
				}

				if ($node instanceof Node\Expr\Array_) {
					return count($node->items) === 2
						&& $node->items[1]->value instanceof Node\Scalar\String_
						&& strtolower($node->items[1]->value->value) === $lowerMethodName;
				}

				if ($node instanceof Node\Scalar\String_) {
					$value = strtolower($node->value);
					return strlen($value) > strlen($lowerMethodName) + 2
						&& str_ends_with($value, '::' . $lowerMethodName);
				}

				return false;
			});
			if ($reference !== null) {
				return true;
			}
		}

		return false;
	}

}
