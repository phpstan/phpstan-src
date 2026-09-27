<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function array_pop;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;

/**
 * Types a `static $x` variable of a function-like body from what the body does
 * with it instead of `mixed`: every call starts where a previous one left the
 * variable, so its type at the `static` statement is the default joined with
 * every type the variable takes in the body - at its end, at every return,
 * throw and yield, and at every call that could run the function again.
 *
 * The two-pass driver (StatementsHandler::processBodyStmtNodesTwoPass())
 * walks the body with the default, collects the types the variable took, and
 * walks the statements that read it again until the type at the `static`
 * statement converges - before the template arguments and the closure
 * signatures of the body are resolved, so they observe the converged type.
 *
 * A body whose variables can change behind the analysis' back (`$$name`,
 * extract(), include, eval), a generator (a suspended call keeps the
 * variable in any state), and a variable typed by `@var` or taken by reference
 * keep today's behaviour.
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/StaticVariableInference.cpp')]
final class StaticVariableInference
{

	private const SITES_ATTRIBUTE = 'staticVariableInferenceSites';

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.staticVariablesFromUsages%')]
		private bool $enabled,
	)
	{
	}

	/**
	 * The `static` variables of the body whose type is inferred, with the index
	 * of the top-level statement holding each and the variable's name.
	 *
	 * @param Node\Stmt[] $stmts
	 * @return list<array{Expr\Variable, int, string}>
	 */
	public function getSites(Node $functionLike, array $stmts): array
	{
		if (!$this->enabled) {
			return [];
		}

		/** @var list<array{Expr\Variable, int, string}>|null $cached */
		$cached = $functionLike->getAttribute(self::SITES_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$sites = self::scanSites($stmts);
		$functionLike->setAttribute(self::SITES_ATTRIBUTE, $sites);

		return $sites;
	}

	/** Whether the current walk infers the type of the `static` variable. */
	public function isInferred(MutatingScope $scope, Expr\Variable $var): bool
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();
		if ($frame === null) {
			return false;
		}
		$body = $frame->getClosureSignatureBody();
		if ($body === null) {
			return false;
		}
		foreach ($this->getSites($body, $frame->getClosureSignatureStmts()) as [$site]) {
			if ($site === $var) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The [phpdoc, native] types the driver resolved for the `static` variable,
	 * null while observing.
	 *
	 * @return array{Type, Type}|null
	 */
	public function getResolvedTypes(MutatingScope $scope, Expr\Variable $var): ?array
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();

		return $frame !== null ? $frame->getStaticVariableTypes($var) : null;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @return list<array{Expr\Variable, int, string}>
	 */
	private static function scanSites(array $stmts): array
	{
		/** @var list<array{Expr\Variable, int}> $candidates */
		$candidates = [];
		$excludedNames = [];
		foreach ($stmts as $index => $stmt) {
			$stack = [$stmt];
			while (count($stack) > 0) {
				$node = array_pop($stack);
				if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
					continue;
				}
				if (
					$node instanceof Expr\Yield_
					|| $node instanceof Expr\YieldFrom
					|| $node instanceof Expr\Include_
					|| $node instanceof Expr\Eval_
				) {
					return [];
				}
				if ($node instanceof Expr\Variable && !is_string($node->name)) {
					return [];
				}
				if (
					$node instanceof Expr\FuncCall
					&& $node->name instanceof Node\Name
					&& in_array($node->name->toLowerString(), ['extract', 'parse_str'], true)
				) {
					return [];
				}
				if ($node instanceof Expr\AssignRef) {
					self::collectRootNames($node->var, $excludedNames);
					self::collectRootNames($node->expr, $excludedNames);
				}
				if ($node instanceof Node\Stmt\Global_) {
					foreach ($node->vars as $var) {
						self::collectRootNames($var, $excludedNames);
					}
				}
				if ($node instanceof Node\Stmt\Static_ && !self::hasVarTag($node)) {
					foreach ($node->vars as $var) {
						$candidates[] = [$var->var, $index];
					}
				}
				foreach ($node->getSubNodeNames() as $subNodeName) {
					$subNode = $node->$subNodeName;
					if ($subNode instanceof Node) {
						$stack[] = $subNode;
					} elseif (is_array($subNode)) {
						foreach ($subNode as $item) {
							if (!$item instanceof Node) {
								continue;
							}
							$stack[] = $item;
						}
					}
				}
			}
		}

		$sites = [];
		foreach ($candidates as [$var, $index]) {
			if (!is_string($var->name) || isset($excludedNames[$var->name])) {
				continue;
			}
			$sites[] = [$var, $index, $var->name];
		}

		return $sites;
	}

	/**
	 * @param array<string, true> $names
	 */
	private static function collectRootNames(Expr $expr, array &$names): void
	{
		while (
			$expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\NullsafePropertyFetch
			|| $expr instanceof Expr\StaticPropertyFetch
		) {
			if ($expr instanceof Expr\StaticPropertyFetch) {
				return;
			}
			$expr = $expr->var;
		}
		if ($expr instanceof Expr\Variable && is_string($expr->name)) {
			$names[$expr->name] = true;
			return;
		}
		if (!$expr instanceof Expr\List_ && !$expr instanceof Expr\Array_) {
			return;
		}

		foreach ($expr->items as $item) {
			if ($item === null) {
				continue;
			}
			self::collectRootNames($item->value, $names);
		}
	}

	private static function hasVarTag(Node\Stmt\Static_ $stmt): bool
	{
		$docComment = $stmt->getDocComment();

		return $docComment !== null && preg_match('~@(?:phpstan-|psalm-)?var\s~', $docComment->getText()) === 1;
	}

}
