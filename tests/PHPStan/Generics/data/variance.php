<?php

namespace PHPStan\Generics\Variance;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleError;

/** @template T */
interface InvariantIter {
	/** @return T */
	public function next();
}


/** @template-covariant T */
interface Iter {
	/** @return T */
	public function next();
}

/** @param InvariantIter<\DateTime> $it */
function acceptInvariantIterOfDateTime($it): void {
}

/** @param InvariantIter<\DateTimeInterface> $it */
function acceptInvariantIterOfDateTimeInterface($it): void {
}

/** @param Iter<\DateTime> $it */
function acceptIterOfDateTime($it): void {
}

/** @param Iter<\DateTimeInterface> $it */
function acceptIterOfDateTimeInterface($it): void {
}

/**
 * @param Iter<\DateTime> $itOfDateTime
 * @param InvariantIter<\DateTime> $invariantItOfDateTime
 */
function test($itOfDateTime, $invariantItOfDateTime): void {
	acceptInvariantIterOfDateTime($invariantItOfDateTime);
	acceptInvariantIterOfDateTimeInterface($invariantItOfDateTime);

	acceptIterOfDateTime($itOfDateTime);
	acceptIterOfDateTimeInterface($itOfDateTime);
}
