<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13875;

use RuntimeException;
use Throwable;

/** @phpstan-template T of Throwable */
interface MatchCondition {}

/** @implements MatchCondition<Throwable> */
class ThrowableMatchCondition implements MatchCondition {}

/** @implements MatchCondition<RuntimeException> */
class RuntimeExceptionMatchCondition implements MatchCondition {}

/** @phpstan-template T of Throwable */
interface ExceptionFormatter {}

/** @implements ExceptionFormatter<Throwable> */
class ThrowableExceptionFormatter implements ExceptionFormatter {}

/** @implements ExceptionFormatter<RuntimeException> */
class RuntimeExceptionFormatter implements ExceptionFormatter {}

/**
 * @template T of Throwable
 *
 * @param T $exception
 * @param MatchCondition<contravariant T> $condition
 * @param ExceptionFormatter<contravariant T> $formatter
 */
function accept(
    Throwable $exception,
    MatchCondition $condition = new ThrowableMatchCondition(),
    ExceptionFormatter $formatter = new ThrowableExceptionFormatter()
): void {
}

// works fine
accept(new RuntimeException());

// fails
accept(new RuntimeException(), new ThrowableMatchCondition(), new RuntimeExceptionFormatter());

// fails
accept(new RuntimeException(), new RuntimeExceptionMatchCondition(), new ThrowableExceptionFormatter());

// works fine
accept(new RuntimeException(), new RuntimeExceptionMatchCondition(), new RuntimeExceptionFormatter());

accept(new RuntimeException(), formatter: new RuntimeExceptionFormatter());

accept(new RuntimeException(), condition: new RuntimeExceptionMatchCondition());
