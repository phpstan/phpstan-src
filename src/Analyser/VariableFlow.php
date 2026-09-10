<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;
use function count;
use function in_array;

/**
 * Immutable source execution fragment, composed with expression and statement
 * results. Liveness is resolved at the body boundary, independently of types.
 */
abstract class VariableFlow
{

	public const SEQUENCE = 'sequence';
	public const CHOICE = 'choice';
	public const LOOP = 'loop';
	public const TRY_CATCH = 'try';
	public const SWITCH = 'switch';
	public const READ = 'read';
	public const WRITE = 'write';
	public const ESCAPE = 'escape';
	public const MENTION = 'mention';
	public const READ_ALL = 'readAll';
	public const MENTION_ALL = 'mentionAll';
	public const OPAQUE = 'opaque';
	public const DEAD = 'dead';
	public const RETURN = 'return';
	public const BREAK = 'break';
	public const CONTINUE = 'continue';
	public const THROW = 'throw';
	public const STOP = 'stop';
	public const ARROW = 'arrow';

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
		return new VariableControlFlow(self::ARROW, [$body, $outputs], arrow: $arrow);
	}

	public static function read(string $name): ?self
	{
		if ($name === 'this' || in_array($name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			return null;
		}

		return new VariableAccessFlow(self::READ, $name);
	}

	public static function conditional(?self $condition, ?self $if, ?self $else, ?bool $truthy): ?self
	{
		if ($truthy === true) {
			$branch = self::sequence($if, self::dead($else));
		} elseif ($truthy === false) {
			$branch = self::sequence(self::dead($if), $else);
		} else {
			$branch = self::choice($if, $else);
		}
		return self::sequence($condition, $branch);
	}

	/** @param list<array{self|null, self|null, bool}> $cases */
	public static function switch(?self $condition, array $cases, bool $exhaustive): self
	{
		return new VariableControlFlow(self::SWITCH, [$condition], canExit: !$exhaustive, cases: $cases);
	}

	public static function write(VariableWrite $write, ?Type $redundantType = null): self
	{
		return new VariableAccessFlow(self::WRITE, $write->getVariableName(), $write, $redundantType);
	}

	public static function escape(string $name): self
	{
		return new VariableAccessFlow(self::ESCAPE, $name);
	}

	public static function mention(string $name): self
	{
		return new VariableAccessFlow(self::MENTION, $name);
	}

	/** @param self::READ_ALL|self::MENTION_ALL|self::OPAQUE $kind */
	public static function all(string $kind): self
	{
		return new VariableControlFlow($kind);
	}

	/** @param self::RETURN|self::BREAK|self::CONTINUE|self::STOP $kind */
	public static function exit(string $kind, int $level = 1, ?string $name = null): self
	{
		return new VariableControlFlow($kind, name: $name, level: $level);
	}

	public static function throwing(Type $type, bool $canContinue, bool $canContainAnyThrowable = false): self
	{
		return new VariableControlFlow(self::THROW, type: $type, canExit: $canContinue, canContainAnyThrowable: $canContainAnyThrowable);
	}

	public static function dead(?self $flow): ?self
	{
		return $flow === null ? null : new VariableControlFlow(self::DEAD, [$flow]);
	}

	public static function loop(?self $condition, ?self $body, ?self $update, bool $atLeastOnce, bool $canExit, bool $canRepeat = true): self
	{
		return new VariableControlFlow(self::LOOP, [$condition, $body, $update], atLeastOnce: $atLeastOnce, canExit: $canExit, canRepeat: $canRepeat);
	}

	/** @param list<array{Type, self|null}> $catches */
	public static function tryCatch(?self $body, array $catches, ?self $finally): self
	{
		return new VariableControlFlow(self::TRY_CATCH, [$body, $finally], catches: $catches);
	}

}
