<?php declare(strict_types = 1);

namespace PHPStan\Build;

use Override;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Php\PhpVersion;
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
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsEnums(), 'enums', $node);
		}

		if ($node instanceof Node\Expr\BinaryOp\Pipe) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsPipeOperator(), 'the pipe operator', $node);
		}

		if ($node instanceof Node\PropertyHook) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsPropertyHooks(), 'property hooks', $node);
		}

		if ($node instanceof Node\IntersectionType) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsPureIntersectionTypes(), 'pure intersection types', $node);
		}

		if ($node instanceof Node\UnionType) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsNativeUnionTypes(), 'union types', $node);
			foreach ($node->types as $innerType) {
				if ($innerType instanceof Node\IntersectionType) {
					$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsDisjunctiveNormalForm(), 'disjunctive normal form types', $innerType);
				}
				if (!($innerType instanceof Node\Identifier) || strtolower($innerType->name) !== 'true') {
					continue;
				}

				$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsTrueFalseNullStandaloneType(), 'the standalone "true" type', $innerType);
			}
		}

		if ($node instanceof Node\Stmt\Class_ && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsReadOnlyClasses(), 'readonly classes', $node);
		}

		if ($node instanceof Node\Stmt\Property && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsReadOnlyProperties(), 'readonly properties', $node);
		}

		if ($node instanceof Node\Param && $node->flags !== 0) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsPromotedProperties(), 'promoted properties', $node);
		}

		if ($node instanceof Node\Param && ($node->flags & Modifiers::READONLY) !== 0) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsReadOnlyProperties(), 'readonly promoted properties', $node);
		}

		if (
			($node instanceof Node\Param || $node instanceof Node\Stmt\Property)
			&& ($node->flags & Modifiers::VISIBILITY_SET_MASK) !== 0
		) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsAsymmetricVisibility(), 'asymmetric visibility', $node);
		}

		if ($node instanceof Node\Stmt\ClassConst && $node->type !== null) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsNativeTypesInClassConstants(), 'typed class constants', $node);
		}

		if ($node instanceof Node\Expr\ClassConstFetch && $node->name instanceof Node\Expr) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsDynamicClassConstantFetch(), 'dynamic class constant fetch', $node);
		}

		if (
			$node instanceof Node\Expr\FuncCall
			|| $node instanceof Node\Expr\MethodCall
			|| $node instanceof Node\Expr\NullsafeMethodCall
			|| $node instanceof Node\Expr\StaticCall
		) {
			foreach ($node->args as $arg) {
				if ($arg instanceof Node\VariadicPlaceholder) {
					$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsFirstClassCallables(), 'first-class callable syntax', $arg);
					break;
				}
			}
		}

		if ($node instanceof Node\Arg && $node->name !== null) {
			$this->require(fn(PhpVersion $phpVersion) => $phpVersion->supportsNamedArguments(), 'named arguments', $node);
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

		$this->require(
			fn(PhpVersion $phpVersion) => $phpVersion->supportsTrueFalseNullStandaloneType(),
			'standalone "null", "false" or "true" types',
			$type
		);
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

		$this->require(
			fn(PhpVersion $phpVersion) => $phpVersion->supportsNativeMixed(),
			'the mixed type',
			$type
		);
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

	/**
	 * @param callable(PhpVersion $phpVersion): bool $callable
	 */
	private function require(callable $callable, string $reason, Node $node): void
	{
		$versionId = $this->findPhpVersion($callable);

		if ($this->requiredVersionId !== null && $this->requiredVersionId >= $versionId) {
			return;
		}

		$this->requiredVersionId = $versionId;
		$this->reason = $reason;
		$this->reasonLine = $node->getStartLine();
	}

	/**
	 * @param callable(PhpVersion $phpVersion): bool $callable
	 */
	private function findPhpVersion(callable $callable): int
	{
		$phpVersionIds = [
			new PhpVersion(70400),
			new PhpVersion(80000),
			new PhpVersion(80100),
			new PhpVersion(80200),
			new PhpVersion(80300),
			new PhpVersion(80400),
			new PhpVersion(80500)
		];

		foreach($phpVersionIds as $phpVersion) {
			if ($callable($phpVersion)) {
				return $phpVersion->getVersionId();
			}
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

}
