<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\FuncCall;

/**
 * Records that a tracked expression (a variable) currently holds the result of
 * the given pure information-carrying call (count(), gettype(), get_class(), ...),
 * so a comparison on the variable can narrow through the call's own comparison
 * machinery as if the call had been compared directly.
 *
 * Entries live in MutatingScope::$resultProvenance keyed by the target
 * expression's key; they are dropped when the target or anything the call
 * reads is invalidated, and merges keep only entries identical on both sides.
 */
final class ResultProvenance
{

	public function __construct(
		private FuncCall $call,
		private string $callExprString,
	)
	{
	}

	public function getCall(): FuncCall
	{
		return $this->call;
	}

	public function getCallExprString(): string
	{
		return $this->callExprString;
	}

}
