<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Include_>
 */
class RequireFileExistsRule implements Rule
{
	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Include_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->shouldProcessNode($node)) {
			$filePath = $this->resolveFilePath($node->expr, $scope);
			if ($filePath !== null && !file_exists($filePath)) {
				return [
					RuleErrorBuilder::message(
						sprintf(
							'Required file "%s" does not exist.',
							$filePath
						)
					)->build(),
				];
			}
		}

		return [];
	}

	private function shouldProcessNode(Node $node): bool
	{
		return $node instanceof Include_ && (
				$node->type === Include_::TYPE_REQUIRE
				|| $node->type === Include_::TYPE_REQUIRE_ONCE
			);
	}

	private function resolveFilePath(Node $node, Scope $scope): ?string
	{
		if ($node instanceof String_) {
			return $node->value;
		}

		if ($node instanceof Dir) {
			return dirname($scope->getFile());
		}

		if ($node instanceof ClassConstFetch) {
			return $this->resolveClassConstant($node);
		}

		if ($node instanceof ConstFetch) {
			return $this->resolveConstant($node);
		}

		if ($node instanceof Concat) {
			$left = $this->resolveFilePath($node->left, $scope);
			$right = $this->resolveFilePath($node->right, $scope);
			if ($left !== null && $right !== null) {
				return $left . $right;
			}
		}

		return null;
	}

	private function resolveClassConstant(ClassConstFetch $node): ?string
	{
		if ($node->class instanceof Name && $node->name instanceof Identifier) {
			$className = (string) $node->class;
			$constantName = $node->name->toString();

			if ($this->reflectionProvider->hasClass($className)) {
				$classReflection = $this->reflectionProvider->getClass($className);
				if ($classReflection->hasConstant($constantName)) {
					$constantReflection = $classReflection->getConstant($constantName);
					$constantValue = $constantReflection->getValue();
					if (is_string($constantValue)) {
						return $constantValue;
					}
				}
			}
		}
		return null;
	}

	private function resolveConstant(ConstFetch $node): ?string
	{
		if ($node->name instanceof Name) {
			$constantName = (string) $node->name;
			if (defined($constantName)) {
				$constantValue = constant($constantName);
				if (is_string($constantValue)) {
					return $constantValue;
				}
			}
		}
		return null;
	}
}
