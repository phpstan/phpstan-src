<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeTraverser;
use function array_unique;
use function array_values;
use function count;
use function dirname;
use function implode;
use function in_array;
use function is_string;
use function spl_object_id;
use function sprintf;
use function str_starts_with;

/**
 * Keeps the choice between TypeTraverser::map() and TypeTraverser::mapMemoized()
 * in line with the mapMemoized() contract: the callback must not depend on where
 * in the traversed type, or how many times, a Type instance occurs.
 *
 * Only closure and arrow function callbacks are checked. A callback depends on
 * the occurrences when it:
 * - writes to a property or declares a static variable,
 * - appends to, increments or otherwise accumulates into a variable captured by reference,
 * - assigns a non-constant value to a variable captured by reference,
 * - passes a variable captured by reference to a call,
 * - reads a variable captured by reference that it also writes, because the state
 *   then changes what the callback does with later occurrences. Reading a flag
 *   is allowed when the mapped type is not used, because then only the side effects
 *   count, and setting a flag or writing an array by key has the same effect
 *   no matter how many times it runs.
 *
 * @implements Rule<StaticCall>
 */
final class TypeTraverserMapMemoizedRule implements Rule
{

	public function __construct(private FileHelper $fileHelper, private bool $skipTests = true)
	{
	}

	public function getNodeType(): string
	{
		return StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node->class instanceof Name
			|| $node->class->toString() !== TypeTraverser::class
			|| !$node->name instanceof Identifier
			|| !in_array($node->name->toLowerString(), ['map', 'mapmemoized'], true)
		) {
			return [];
		}

		if ($this->skipTests && str_starts_with($this->fileHelper->normalizePath($scope->getFile()), $this->fileHelper->normalizePath(dirname(__DIR__, 3) . '/tests'))) {
			return [];
		}

		// the result is not used when the call is a statement of its own;
		// the body of an arrow function also counts as one, which errs towards allowing mapMemoized()
		$error = $this->checkCall($node, $scope->isInFirstLevelStatement());
		if ($error === null) {
			return [];
		}

		return [$error];
	}

	private function checkCall(StaticCall $call, bool $isResultUnused): ?IdentifierRuleError
	{
		$args = $call->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$callback = $args[1]->value;
		if (!$callback instanceof Closure && !$callback instanceof ArrowFunction) {
			return null;
		}

		$reasons = $this->findOccurrenceDependencies($callback, $isResultUnused);
		if ($call->name instanceof Identifier && $call->name->toLowerString() === 'mapmemoized') {
			if (count($reasons) === 0) {
				return null;
			}

			return RuleErrorBuilder::message(sprintf(
				'Callback of TypeTraverser::mapMemoized() %s, so it might depend on where or how many times a Type instance occurs. Use TypeTraverser::map() instead.',
				implode(', ', $reasons),
			))
				->identifier('phpstan.typeTraverserMapMemoized')
				->line($call->getStartLine())
				->build();
		}

		if (count($reasons) > 0) {
			return null;
		}

		return RuleErrorBuilder::message('Callback of TypeTraverser::map() does not depend on where or how many times a Type instance occurs. Use TypeTraverser::mapMemoized() instead.')
			->identifier('phpstan.typeTraverserMap')
			->line($call->getStartLine())
			->fixNode($call, static function (StaticCall $node): StaticCall {
				$node->name = new Identifier('mapMemoized');
				return $node;
			})
			->build();
	}

	/**
	 * @return list<string>
	 */
	private function findOccurrenceDependencies(Closure|ArrowFunction $callback, bool $isResultUnused): array
	{
		/** @var array<string, true> $byRefNames */
		$byRefNames = [];
		if ($callback instanceof Closure) {
			foreach ($callback->uses as $use) {
				if (!$use->byRef || !is_string($use->var->name)) {
					continue;
				}

				$byRefNames[$use->var->name] = true;
			}
		}

		$nodeFinder = new NodeFinder();
		$reasons = [];

		/** @var array<string, bool> $writtenNames name => whether it is written only by setting a constant */
		$writtenNames = [];

		/** @var array<int, true> $writtenVariables */
		$writtenVariables = [];

		$nodeFinder->find($callback->getStmts(), function (Node $node) use ($byRefNames, &$reasons, &$writtenNames, &$writtenVariables): bool {
			if ($node instanceof Static_) {
				$reasons[] = 'declares a static variable';
				return false;
			}

			if ($node instanceof CallLike) {
				if ($node->isFirstClassCallable()) {
					return false;
				}

				foreach ($node->getArgs() as $arg) {
					$name = $this->getByRefVariableName($arg->value, $byRefNames);
					if ($name === null) {
						continue;
					}

					$reasons[] = sprintf('passes $%s captured by reference to a call', $name);
				}
				return false;
			}

			if ($node instanceof Unset_) {
				$targets = $node->vars;
			} elseif (
				$node instanceof Assign
				|| $node instanceof AssignRef
				|| $node instanceof AssignOp
				|| $node instanceof PreInc
				|| $node instanceof PreDec
				|| $node instanceof PostInc
				|| $node instanceof PostDec
			) {
				$targets = [$node->var];
			} else {
				return false;
			}

			foreach ($targets as $target) {
				$this->processWrite($node, $target, $byRefNames, $reasons, $writtenNames, $writtenVariables);
			}

			return false;
		});

		$nodeFinder->find($callback->getStmts(), static function (Node $node) use ($writtenNames, &$reasons, $writtenVariables, $isResultUnused): bool {
			if (
				!$node instanceof Variable
				|| !is_string($node->name)
				|| !isset($writtenNames[$node->name])
				|| isset($writtenVariables[spl_object_id($node)])
			) {
				return false;
			}

			if ($isResultUnused && $writtenNames[$node->name]) {
				return false;
			}

			$reasons[] = sprintf('reads $%s captured by reference that it also writes', $node->name);
			return false;
		});

		return array_values(array_unique($reasons));
	}

	/**
	 * @param array<string, true> $byRefNames
	 * @param list<string> $reasons
	 * @param array<string, bool> $writtenNames
	 * @param array<int, true> $writtenVariables
	 */
	private function processWrite(
		Node $node,
		Expr $target,
		array $byRefNames,
		array &$reasons,
		array &$writtenNames,
		array &$writtenVariables,
	): void
	{
		$root = $target;
		$appends = false;
		while ($root instanceof ArrayDimFetch) {
			if ($root->dim === null) {
				$appends = true;
			}
			$root = $root->var;
		}

		if ($root instanceof PropertyFetch || $root instanceof StaticPropertyFetch) {
			$reasons[] = 'writes to a property';
			return;
		}

		$name = $this->getByRefVariableName($root, $byRefNames);
		if ($name === null) {
			return;
		}

		$writtenVariables[spl_object_id($root)] = true;

		if ($appends) {
			$reasons[] = sprintf('appends to $%s captured by reference', $name);
		} elseif ($target !== $root) {
			// writing or unsetting by key
			if ($node instanceof Assign || $node instanceof Unset_) {
				$writtenNames[$name] = false;
			} else {
				$reasons[] = sprintf('modifies $%s captured by reference', $name);
			}
		} elseif (!$node instanceof Assign) {
			$reasons[] = sprintf('modifies $%s captured by reference', $name);
		} elseif (!$this->isConstant($node->expr)) {
			$reasons[] = sprintf('assigns a non-constant value to $%s captured by reference', $name);
		} else {
			$writtenNames[$name] ??= true;
		}
	}

	/**
	 * @param array<string, true> $byRefNames
	 */
	private function getByRefVariableName(Expr $expr, array $byRefNames): ?string
	{
		if (!$expr instanceof Variable || !is_string($expr->name) || !isset($byRefNames[$expr->name])) {
			return null;
		}

		return $expr->name;
	}

	private function isConstant(Expr $expr): bool
	{
		return $expr instanceof Scalar || $expr instanceof ConstFetch;
	}

}
