<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use RuntimeException;

/** A construct outside the compiled subset, or a twin the compiler cannot prove safe to lower. */
final class CompileError extends RuntimeException
{

}
