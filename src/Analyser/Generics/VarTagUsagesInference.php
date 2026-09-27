<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_fill_keys;
use function array_keys;
use function array_pop;
use function count;
use function is_array;
use function is_string;
use function preg_match;
use function spl_object_id;

/**
 * Finds the `@var` declarations of a function-like body whose type the
 * assigned value does not decide - `null`, `[]`, a scalar, a `new` of a
 * generic class, and any `static` variable - and the writes to those
 * variables after them. Such a tag is the declared type of the variable:
 * every value the body writes to it has to fit, and the value of a generic
 * `new` has to fit with the template arguments the body infers when the tag
 * is left out (see StatementsHandler::processBodyStmtNodesTwoPass()).
 * VarTagReflectsUsagesRule checks both.
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/VarTagUsagesInference.cpp')]
final class VarTagUsagesInference
{

	private const DECLARATIONS_ATTRIBUTE = 'varTagUsagesDeclarations';

	private const WRITES_ATTRIBUTE = 'varTagUsagesWrites';

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.varTagsReflectUsages%')]
		private bool $enabled,
	)
	{
	}

	/**
	 * The top-level statements `/** @var T $x *\/ $x = <value>;` of the body
	 * whose value does not decide the type and `/** @var T *\/ static $x;`,
	 * with their index and the variable's name.
	 *
	 * @param Node\Stmt[] $stmts
	 * @return list<array{Node\Stmt, int, string}>
	 */
	public function getDeclarations(Node $functionLike, array $stmts): array
	{
		if (!$this->enabled) {
			return [];
		}

		/** @var list<array{Node\Stmt, int, string}>|null $cached */
		$cached = $functionLike->getAttribute(self::DECLARATIONS_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$declarations = [];
		foreach ($stmts as $index => $stmt) {
			$name = self::getDeclaredVariableName($stmt);
			if ($name === null) {
				continue;
			}
			$docComment = $stmt->getDocComment();
			if ($docComment === null || preg_match('~@(?:phpstan-|psalm-)?var\s~', $docComment->getText()) !== 1) {
				continue;
			}
			$declarations[] = [$stmt, $index, $name];
		}
		$functionLike->setAttribute(self::DECLARATIONS_ATTRIBUTE, $declarations);

		return $declarations;
	}

	/**
	 * The writes to the declared variables after their declarations: the
	 * assignments, compound assignments, increments and decrements of the
	 * variable, of an offset in it, or destructuring into it - also in a
	 * closure that uses the variable by reference - with the key of the
	 * declaration in getDeclarations() and the variable's name.
	 *
	 * @param Node\Stmt[] $stmts
	 * @return list<array{Expr, int, string}>
	 */
	public function getWrites(Node $functionLike, array $stmts): array
	{
		$declarations = $this->getDeclarations($functionLike, $stmts);
		if ($declarations === []) {
			return [];
		}

		/** @var list<array{Expr, int, string}>|null $cached */
		$cached = $functionLike->getAttribute(self::WRITES_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$declarationsByName = [];
		$declaringAssigns = [];
		foreach ($declarations as $key => [$stmt, , $name]) {
			$declarationsByName[$name][] = [$stmt->getEndFilePos(), $key];
			if (!$stmt instanceof Node\Stmt\Expression) {
				continue;
			}
			$declaringAssigns[spl_object_id($stmt->expr)] = true;
		}
		$writes = [];
		$stack = [];
		$names = array_fill_keys(array_keys($declarationsByName), true);
		foreach ($stmts as $stmt) {
			$stack[] = [$stmt, $names];
		}
		while (count($stack) > 0) {
			[$node, $visibleNames] = array_pop($stack);
			if ($node instanceof Expr\Closure) {
				// its own variables, except those it uses by reference
				$byRefNames = [];
				foreach ($node->uses as $use) {
					if (!$use->byRef || !is_string($use->var->name) || !isset($visibleNames[$use->var->name])) {
						continue;
					}
					$byRefNames[$use->var->name] = true;
				}
				if ($byRefNames === []) {
					continue;
				}
				foreach ($node->stmts as $closureStmt) {
					$stack[] = [$closureStmt, $byRefNames];
				}
				continue;
			}
			if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
				continue;
			}
			$target = null;
			if (
				$node instanceof Expr\Assign
				|| $node instanceof Expr\AssignOp
				|| $node instanceof Expr\PreInc
				|| $node instanceof Expr\PreDec
				|| $node instanceof Expr\PostInc
				|| $node instanceof Expr\PostDec
			) {
				$target = $node->var;
			}
			if ($target !== null && !isset($declaringAssigns[spl_object_id($node)])) {
				foreach (self::getWrittenNames($target) as $name) {
					if (!isset($visibleNames[$name])) {
						continue;
					}
					$declarationKey = null;
					foreach ($declarationsByName[$name] as [$declarationEnd, $key]) {
						if ($declarationEnd >= $node->getStartFilePos()) {
							continue;
						}
						$declarationKey = $key;
					}
					if ($declarationKey === null) {
						continue;
					}
					$writes[] = [$node, $declarationKey, $name];
				}
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = [$subNode, $visibleNames];
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = [$item, $visibleNames];
					}
				}
			}
		}
		$functionLike->setAttribute(self::WRITES_ATTRIBUTE, $writes);

		return $writes;
	}

	/**
	 * The variables a write target writes: the variable, an offset in it, or
	 * the destructured ones.
	 *
	 * @return list<string>
	 */
	private static function getWrittenNames(Expr $target): array
	{
		while ($target instanceof Expr\ArrayDimFetch) {
			$target = $target->var;
		}
		if ($target instanceof Expr\Variable) {
			return is_string($target->name) ? [$target->name] : [];
		}
		if (!$target instanceof Expr\List_ && !$target instanceof Expr\Array_) {
			return [];
		}

		$names = [];
		foreach ($target->items as $item) {
			if ($item === null) {
				continue;
			}
			foreach (self::getWrittenNames($item->value) as $name) {
				$names[] = $name;
			}
		}

		return $names;
	}

	/** Whether the walk leaves the @var tags of the statement out. */
	public function isSuppressed(MutatingScope $scope, Node\Stmt $stmt): bool
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();

		return $frame !== null && $frame->isVarTagSuppressed($stmt);
	}

	private static function getDeclaredVariableName(Node\Stmt $stmt): ?string
	{
		if ($stmt instanceof Node\Stmt\Static_) {
			// what the previous calls left decides the type, not the default
			if (count($stmt->vars) !== 1 || !is_string($stmt->vars[0]->var->name)) {
				return null;
			}

			return $stmt->vars[0]->var->name;
		}

		if (
			!$stmt instanceof Node\Stmt\Expression
			|| !$stmt->expr instanceof Expr\Assign
			|| !$stmt->expr->var instanceof Expr\Variable
			|| !is_string($stmt->expr->var->name)
			|| !self::isUndecidingValue($stmt->expr->expr)
		) {
			return null;
		}

		return $stmt->expr->var->name;
	}

	private static function isUndecidingValue(Expr $expr): bool
	{
		return $expr instanceof Expr\ConstFetch
			|| $expr instanceof Node\Scalar
			|| ($expr instanceof Expr\Array_ && $expr->items === [])
			|| $expr instanceof Expr\New_;
	}

}
