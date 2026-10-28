<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * Optional parameters are allowed only in @api methods and functions, where
 * adding a parameter must not break existing callers. Internal code should
 * pass every argument explicitly.
 *
 * A class-level @api tag covers public methods (and protected methods of non-final classes),
 * but not the constructor, which is covered only by its own @api tag.
 *
 * @implements Rule<Node\FunctionLike>
 */
final class NoOptionalParameterRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$positionsOptionalInAncestors = [];
		if ($node instanceof Node\Stmt\ClassMethod) {
			$methodName = $node->name->toString();
			$classReflection = $scope->getClassReflection();
			$declaringReflection = $scope->isInTrait() ? $scope->getTraitReflection() : $classReflection;
			if ($classReflection === null || $declaringReflection === null || $this->isApiMethod($node, $declaringReflection)) {
				return [];
			}
			$description = sprintf('method %s::%s()', $declaringReflection->getDisplayName(), $methodName);
			$positionsOptionalInAncestors = $this->getPositionsOptionalInAncestors($classReflection, $methodName);
		} elseif ($node instanceof Node\Stmt\Function_) {
			if ($this->hasApiTag($node)) {
				return [];
			}
			$description = sprintf('function %s()', $node->namespacedName !== null ? $node->namespacedName->toString() : $node->name->toString());
		} else {
			return [];
		}

		$errors = [];
		foreach ($node->getParams() as $position => $param) {
			if ($param->default === null) {
				continue;
			}
			if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
				continue;
			}
			if (isset($positionsOptionalInAncestors[$position])) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Parameter $%s of %s must not be optional. Optional parameters are allowed only in @api code.',
				$param->var->name,
				$description,
			))
				->identifier('phpstan.optionalParameter')
				->line($param->getStartLine())
				->build();
		}

		return $errors;
	}

	private function isApiMethod(Node\Stmt\ClassMethod $node, ClassReflection $classReflection): bool
	{
		if ($this->hasApiTag($node)) {
			return true;
		}

		if (strtolower($node->name->toString()) === '__construct') {
			return false;
		}

		if ($node->isPrivate()) {
			return false;
		}

		if ($node->isProtected() && $classReflection->isFinal()) {
			return false;
		}

		$resolvedPhpDoc = $classReflection->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return false;
		}

		foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
			if (count($phpDocNode->getTagsByName('@api')) > 0) {
				return true;
			}
		}

		return false;
	}

	private function hasApiTag(Node $node): bool
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return false;
		}

		return preg_match('~@api(?![\w-])~', $docComment->getText()) === 1;
	}

	/**
	 * PHP requires an overriding method to keep these parameters optional.
	 *
	 * @return array<int, true>
	 */
	private function getPositionsOptionalInAncestors(ClassReflection $classReflection, string $methodName): array
	{
		$positions = [];
		foreach ($classReflection->getAncestors() as $ancestor) {
			if ($ancestor->getName() === $classReflection->getName() || $ancestor->isTrait() || !$ancestor->hasNativeMethod($methodName)) {
				continue;
			}

			foreach ($ancestor->getNativeMethod($methodName)->getVariants() as $variant) {
				foreach ($variant->getParameters() as $position => $parameter) {
					if (!$parameter->isOptional()) {
						continue;
					}

					$positions[$position] = true;
				}
			}
		}

		return $positions;
	}

}
