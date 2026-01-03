<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use function debug_backtrace;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

final class BeforeScopeForExprRequest
{

	public ?string $originFile = null;

	public ?int $originLine = null;

	public function __construct(public readonly Expr $expr, public readonly MutatingScope $scope)
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$this->originFile = $trace[1]['file'] ?? null;
		$this->originLine = $trace[1]['line'] ?? null;
	}

}
