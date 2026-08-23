<?php // lint >= 8.1

namespace ClosurePassedToTypeNodeCallbackScope;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * Regression tests for closure parameter type inference in NodeCallbackScope.
 * @see https://github.com/phpstan/phpstan/issues/13993
 *
 * These tests verify that closure parameter types are properly inferred from
 * expected callable types when using NodeCallbackScope.
 */

// ============================================================================
// Example 1: Closure parameter inference with array destructuring
// ============================================================================

class DateRange
{
	public function format(): string
	{
		return '2024-01-01';
	}
}

class Context {}

class Loader
{
	/**
	 * @param Closure(Context, non-empty-array<array{DateRange, list<int>}>): iterable<array{int, string}, string> $loader
	 */
	public function __construct(
		private Closure $loader,
	) {}
}

/**
 * Test: Closure parameter inference with array destructuring in constructor
 * When a closure is passed to a constructor, the parameter types should be
 * inferred from the expected Closure type, including array destructuring.
 */
$loader = new Loader(
	loader: function (Context $context, array $items): iterable {
		assertType('non-empty-array<array{ClosurePassedToTypeNodeCallbackScope\DateRange, list<int>}>', $items);
		foreach ($items as [$dateRange, $ids]) {
			assertType('ClosurePassedToTypeNodeCallbackScope\DateRange', $dateRange);
			assertType('list<int>', $ids);
			foreach ($ids as $id) {
				assertType('int', $id);
				yield [$id, $dateRange->format()] => 'value';
			}
		}
	},
);

// ============================================================================
// Example 2: Generic callable parameter resolution
// ============================================================================

/**
 * @template T
 */
class Vote
{
	/**
	 * @param T $subject
	 */
	public function __construct(
		public bool $granted,
		public mixed $subject,
	) {}
}

/**
 * @template TSubject
 */
class Decision
{
	/**
	 * @param list<Vote<TSubject>> $votes
	 */
	public function __construct(
		private array $votes,
	) {}

	/**
	 * @template U
	 * @template K of array-key
	 *
	 * @param callable(Vote<TSubject> $vote): iterable<K, U> $fn
	 *
	 * @return array<K, U>
	 */
	public function collect(callable $fn): array
	{
		$result = [];
		foreach ($this->votes as $vote) {
			foreach ($fn($vote) as $key => $value) {
				$result[$key] = $value;
			}
		}
		return $result;
	}
}

class Subject
{
	public function id(): int
	{
		return 42;
	}
}

/**
 * Test: Generic callable parameter resolution
 * When passing a closure to Decision<Subject>::collect(),
 * the Vote parameter should be inferred as Vote<Subject>.
 */
$decision = new Decision([new Vote(granted: true, subject: new Subject())]);
$result = $decision->collect(static function (Vote $vote): iterable {
	assertType('ClosurePassedToTypeNodeCallbackScope\Vote<ClosurePassedToTypeNodeCallbackScope\Subject>', $vote);
	assertType('ClosurePassedToTypeNodeCallbackScope\Subject', $vote->subject);
	if ($vote->granted) {
		yield $vote->subject->id() => $vote->subject;
	}
});
assertType('array<int, ClosurePassedToTypeNodeCallbackScope\Subject>', $result);
