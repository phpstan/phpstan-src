<?php declare(strict_types = 1);

namespace PHPStan\Build;

use Override;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function in_array;
use function strtolower;

/**
 * Detects the minimum PHP version a file requires to be parsed, based on the
 * syntactic features it uses. Used to verify that analyser test fixtures carry
 * a matching `// lint >= X.Y` comment so they get skipped on older PHP versions
 * in CI instead of producing a hard parse error.
 */
final class RequiredPhpVersionVisitor extends NodeVisitorAbstract
{

	public const PHP_8_0 = 80000;

	private const PHP_8_1 = 80100;
	private const PHP_8_2 = 80200;
	private const PHP_8_3 = 80300;
	private const PHP_8_4 = 80400;
	private const PHP_8_5 = 80500;

	private ?int $requiredVersionId = null;

	private ?string $reason = null;

	private ?int $reasonLine = null;

	public function getRequiredVersionId(): ?int
	{
		return $this->requiredVersionId;
	}

	public function getReason(): ?string
	{
		return $this->reason;
	}

	public function getReasonLine(): ?int
	{
		return $this->reasonLine;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Enum_) {
			$this->require(self::PHP_8_1, 'enums', $node);
		}

		if ($node instanceof Node\Expr\BinaryOp\Pipe) {
			$this->require(self::PHP_8_5, 'the pipe operator', $node);
		}

		if ($node instanceof Node\PropertyHook) {
			$this->require(self::PHP_8_4, 'property hooks', $node);
		}

		if ($node instanceof Node\IntersectionType) {
			$this->require(self::PHP_8_1, 'pure intersection types', $node);
		}

		if ($node instanceof Node\UnionType) {
			$this->require(self::PHP_8_0, 'union types', $node);
			foreach ($node->types as $innerType) {
				if ($innerType instanceof Node\IntersectionType) {
					$this->require(self::PHP_8_2, 'disjunctive normal form types', $innerType);
				}
				if (!($innerType instanceof Node\Identifier) || strtolower($innerType->name) !== 'true') {
					continue;
				}

				$this->require(self::PHP_8_2, 'the standalone "true" type', $innerType);
			}
		}

		if ($node instanceof Node\Stmt\Class_ && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(self::PHP_8_2, 'readonly classes', $node);
		}

		if ($node instanceof Node\Stmt\Property && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(self::PHP_8_1, 'readonly properties', $node);
		}

		if ($node instanceof Node\Param && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(self::PHP_8_1, 'readonly promoted properties', $node);
		}

		if (
			($node instanceof Node\Param || $node instanceof Node\Stmt\Property)
			&& ($node->flags & Modifiers::VISIBILITY_SET_MASK) !== 0
		) {
			$this->require(self::PHP_8_4, 'asymmetric visibility', $node);
		}

		if ($node instanceof Node\Stmt\ClassConst && $node->type !== null) {
			$this->require(self::PHP_8_3, 'typed class constants', $node);
		}

		if ($node instanceof Node\Expr\ClassConstFetch && $node->name instanceof Node\Expr) {
			$this->require(self::PHP_8_3, 'dynamic class constant fetch', $node);
		}

		if (
			$node instanceof Node\Expr\FuncCall
			|| $node instanceof Node\Expr\MethodCall
			|| $node instanceof Node\Expr\NullsafeMethodCall
			|| $node instanceof Node\Expr\StaticCall
		) {
			foreach ($node->args as $arg) {
				if ($arg instanceof Node\VariadicPlaceholder) {
					$this->require(self::PHP_8_1, 'first-class callable syntax', $arg);
					break;
				}
			}
		}

		$this->checkStandaloneType($node);
		$this->checkMixedType($node);

		return null;
	}

	private function checkStandaloneType(Node $node): void
	{
		$type = $this->getDeclaredType($node);
		if (!$type instanceof Node\Identifier) {
			return;
		}

		if (!in_array(strtolower($type->name), ['null', 'false', 'true'], true)) {
			return;
		}

		$this->require(self::PHP_8_2, 'standalone "null", "false" or "true" types', $type);
	}

	private function checkMixedType(Node $node): void
	{
		$type = $this->getDeclaredType($node);
		if (!$type instanceof Node\Identifier) {
			return;
		}

		if (strtolower($type->name) !== 'mixed') {
			return;
		}

		$this->require(self::PHP_8_0, 'the mixed type', $type);
	}

	private function getDeclaredType(Node $node): ?Node
	{
		if (
			$node instanceof Node\Param
			|| $node instanceof Node\Stmt\Property
			|| $node instanceof Node\Stmt\ClassConst
		) {
			return $node->type;
		}

		if (
			$node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
			|| $node instanceof Node\Expr\Closure
			|| $node instanceof Node\Expr\ArrowFunction
		) {
			return $node->returnType;
		}

		return null;
	}

	private function require(int $versionId, string $reason, Node $node): void
	{
		if ($this->requiredVersionId !== null && $this->requiredVersionId >= $versionId) {
			return;
		}

		$this->requiredVersionId = $versionId;
		$this->reason = $reason;
		$this->reasonLine = $node->getStartLine();
	}

}
