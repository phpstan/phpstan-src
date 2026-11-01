<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;

/**
 * Immutable source execution fragment, composed with expression and statement
 * results. Liveness is resolved at the body boundary, independently of types.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/VariableFlow.cpp')]
abstract class VariableFlow
{

	public const SEQUENCE = 'sequence';
	public const CHOICE = 'choice';
	public const LOOP = 'loop';
	public const TRY_CATCH = 'try';
	public const SWITCH = 'switch';
	public const READ = 'read';
	public const WRITE = 'write';
	public const DEFINE = 'define';
	public const DISCARD = 'discard';
	public const ESCAPE = 'escape';
	public const MENTION = 'mention';
	public const READ_ALL = 'readAll';
	public const MENTION_ALL = 'mentionAll';
	public const OPAQUE = 'opaque';
	public const RETURN = 'return';
	public const BREAK = 'break';
	public const CONTINUE = 'continue';
	public const THROW = 'throw';
	public const STOP = 'stop';
	public const ARROW = 'arrow';
	public const LOOP_STATEMENT = 'loopStatement';

	/** @param self::* $kind */
	protected function __construct(public readonly string $kind)
	{
	}

	public static function sequence(?self ...$flows): ?self
	{
		$nonEmpty = [];
		foreach ($flows as $flow) {
			if ($flow === null) {
				continue;
			}
			$nonEmpty[] = $flow;
		}
		if (count($nonEmpty) === 0) {
			return null;
		}
		if (count($nonEmpty) === 1) {
			return $nonEmpty[0];
		}

		return new VariableSequenceFlow(self::SEQUENCE, $nonEmpty);
	}

	/** @no-named-arguments */
	public static function choice(?self ...$branches): ?self
	{
		if (count($branches) === 0) {
			return null;
		}
		if (count($branches) === 1 || (count($branches) === 2 && $branches[0] === $branches[1])) {
			return $branches[0];
		}

		return new VariableSequenceFlow(self::CHOICE, $branches);
	}

	public static function arrow(ArrowFunction $arrow, ?self $body, ?self $outputs): self
	{
		return new VariableControlFlow(self::ARROW, [$body, $outputs], arrow: $arrow, name: null, type: null, level: 1, atLeastOnce: false, canExit: true, catches: [], cases: [], canRepeat: true, canContainAnyThrowable: false, stmt: null, bindings: [], ownWrites: []);
	}

	/** @param int|string|null $offset */
	public static function read(string $name, ?int $targetId, bool $container, $offset): ?self
	{
		if ($name === 'this' || in_array($name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			return null;
		}

		return new VariableAccessFlow(self::READ, $name, targetId: $targetId, container: $container, offset: $offset, write: null, type: null);
	}

	public static function conditional(?self $condition, ?self $if, ?self $else): ?self
	{
		return self::sequence($condition, self::choice($if, $else));
	}

	/** @param list<array{self|null, self|null, bool}> $cases */
	public static function switch(?self $condition, array $cases, bool $exhaustive): self
	{
		return new VariableControlFlow(self::SWITCH, [$condition], canExit: !$exhaustive, cases: $cases, name: null, type: null, level: 1, atLeastOnce: false, catches: [], arrow: null, canRepeat: true, canContainAnyThrowable: false, stmt: null, bindings: [], ownWrites: []);
	}

	public static function write(VariableWrite $write, ?Type $redundantType): self
	{
		return new VariableAccessFlow($write->getParentId() === null ? self::WRITE : self::DEFINE, $write->getVariableName(), $write, $redundantType, null, false, null);
	}

	public static function discard(VariableWrite $write): self
	{
		return new VariableAccessFlow(self::DISCARD, $write->getVariableName(), $write, null, null, false, null);
	}

	/** The enclosing expression consumes a write's inputs, without reading its target. */
	public static function inputs(int $writeId, ?int $targetId): self
	{
		return new VariableInputFlow($writeId, $targetId);
	}

	public static function escape(string $name): self
	{
		return new VariableAccessFlow(self::ESCAPE, $name, null, null, null, false, null);
	}

	public static function mention(string $name): self
	{
		return new VariableAccessFlow(self::MENTION, $name, null, null, null, false, null);
	}

	/** @param self::READ_ALL|self::MENTION_ALL|self::OPAQUE $kind */
	public static function all(string $kind): self
	{
		return new VariableControlFlow($kind, [], null, null, 1, false, true, [], null, [], true, false, null, [], []);
	}

	/** @param self::RETURN|self::BREAK|self::CONTINUE|self::STOP $kind */
	public static function exit(string $kind, int $level, ?string $name): self
	{
		return new VariableControlFlow($kind, name: $name, level: $level, children: [], type: null, atLeastOnce: false, canExit: true, catches: [], arrow: null, cases: [], canRepeat: true, canContainAnyThrowable: false, stmt: null, bindings: [], ownWrites: []);
	}

	public static function throwing(Type $type, bool $canContinue, bool $canContainAnyThrowable): self
	{
		return new VariableControlFlow(self::THROW, type: $type, canExit: $canContinue, canContainAnyThrowable: $canContainAnyThrowable, children: [], name: null, level: 1, atLeastOnce: false, catches: [], arrow: null, cases: [], canRepeat: true, stmt: null, bindings: [], ownWrites: []);
	}

	public static function loop(?self $condition, ?self $body, ?self $update, bool $atLeastOnce, bool $canExit, bool $canRepeat): self
	{
		return new VariableControlFlow(self::LOOP, [$condition, $body, $update], atLeastOnce: $atLeastOnce, canExit: $canExit, canRepeat: $canRepeat, name: null, type: null, level: 1, catches: [], arrow: null, cases: [], canContainAnyThrowable: false, stmt: null, bindings: [], ownWrites: []);
	}

	/**
	 * A loop statement whose head binds variables - a foreach key/value or
	 * a for-loop initial assignment. The bindings are checked for reusing a
	 * variable that is assigned before the statement and read after it;
	 * the statement's own writes (the bindings and a for-loop update) are
	 * the only assignments allowed in between.
	 *
	 * @param list<VariableWrite> $bindings
	 * @param list<VariableWrite> $ownWrites
	 */
	public static function loopStatement(Foreach_|For_ $stmt, ?self $flow, array $bindings, array $ownWrites): ?self
	{
		if ($bindings === []) {
			return $flow;
		}

		return new VariableControlFlow(self::LOOP_STATEMENT, [$flow], stmt: $stmt, bindings: $bindings, ownWrites: $ownWrites, name: null, type: null, level: 1, atLeastOnce: false, canExit: true, catches: [], arrow: null, cases: [], canRepeat: true, canContainAnyThrowable: false);
	}

	/** @param list<array{Type, self|null}> $catches */
	public static function tryCatch(?self $body, array $catches, ?self $finally): self
	{
		return new VariableControlFlow(self::TRY_CATCH, [$body, $finally], catches: $catches, name: null, type: null, level: 1, atLeastOnce: false, canExit: true, arrow: null, cases: [], canRepeat: true, canContainAnyThrowable: false, stmt: null, bindings: [], ownWrites: []);
	}

}
