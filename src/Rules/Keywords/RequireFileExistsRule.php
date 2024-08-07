<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function constant;
use function defined;
use function dirname;
use function file_exists;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Include_>
 */
final class RequireFileExistsRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Include_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->shouldProcessNode($node)) {
			$filePath = $this->resolveFilePath($node->expr, $scope);
			if (is_string($filePath) && !is_file($filePath)) {
				return [
					RuleErrorBuilder::message(
						sprintf(
							'Required file "%s" does not exist.',
							$filePath,
						),
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
