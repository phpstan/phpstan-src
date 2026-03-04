<?php

namespace PossiblyImpureTip;

// --- Function calls ---

function maybeImpureFunction(): int
{
	return rand(1, 100);
}

/** @phpstan-pure */
function pureFunction(): int
{
	return 42;
}

/** @phpstan-impure */
function impureFunction(): int
{
	echo 'hello';
	return rand(1, 100);
}

function voidFunction(): void
{
	echo 'hello';
}

function testFunctionCalls(): void
{
	// maybe-impure function: tip should appear
	if (maybeImpureFunction() === 1) {
		if (maybeImpureFunction() === 2) { // always false, tip expected
		}
	}

	// pure function: error occurs but no impure tip (hasSideEffects()->no())
	if (pureFunction() === 1) {
		if (pureFunction() === 2) { // always false, no tip because function is pure
		}
	}

	// impure function: no error (value not remembered because hasSideEffects()->yes())
	if (impureFunction() === 1) {
		if (impureFunction() === 1) { // no error, impure invalidates
		}
	}

	// void function: hasSideEffects()->yes(), can't appear in === comparisons
}

// --- Method calls ---

class MethodCallTest
{

	public function maybeImpureMethod(): int
	{
		return rand(1, 100);
	}

	/** @phpstan-pure */
	public function pureMethod(): int
	{
		return 42;
	}

	/** @phpstan-impure */
	public function impureMethod(): int
	{
		echo 'hello';
		return rand(1, 100);
	}

	public function voidMethod(): void
	{
		echo 'hello';
	}

	public function testMaybeImpureMethod(): void
	{
		// maybe-impure method: tip should appear
		if ($this->maybeImpureMethod() === 1) {
			if ($this->maybeImpureMethod() === 2) { // always false, tip expected
			}
		}
	}

	public function testPureMethod(): void
	{
		// pure method: error occurs but no impure tip (hasSideEffects()->no())
		if ($this->pureMethod() === 1) {
			if ($this->pureMethod() === 2) { // always false, no tip because method is pure
			}
		}
	}

	public function testImpureMethod(): void
	{
		// impure method: hasSideEffects()->yes() invalidates $this
		// so no "always true/false" error occurs at all
		if ($this->impureMethod() === 1) {
			if ($this->impureMethod() === 1) {
				// Not "always true" because impure invalidates
			}
		}
	}

	public function testVoidMethod(): void
	{
		// void method: hasSideEffects()->yes() invalidates $this
		// so no "always true/false" from strict comparison occurs
		if ($this->voidMethod() === null) {
		}
		if ($this->maybeImpureMethod() === 1) {
			// voidMethod() invalidated $this, so maybeImpureMethod()
			// is evaluated fresh
		}
	}

}

// --- Static method calls ---

class StaticCallTest
{

	public static function maybeImpureStatic(): int
	{
		return rand(1, 100);
	}

	/** @phpstan-pure */
	public static function pureStatic(): int
	{
		return 42;
	}

	/** @phpstan-impure */
	public static function impureStatic(): int
	{
		echo 'hello';
		return rand(1, 100);
	}

	public static function voidStatic(): void
	{
		echo 'hello';
	}

	public function testMaybeImpureStatic(): void
	{
		// maybe-impure static method: tip should appear
		if (self::maybeImpureStatic() === 1) {
			if (self::maybeImpureStatic() === 2) { // always false, tip expected
			}
		}
	}

	public function testPureStatic(): void
	{
		// pure static method: error occurs but no impure tip (hasSideEffects()->no())
		if (self::pureStatic() === 1) {
			if (self::pureStatic() === 2) { // always false, no tip because method is pure
			}
		}
	}

	public function testImpureStatic(): void
	{
		// impure static method: hasSideEffects()->yes() invalidates $this
		// so no "always true/false" error occurs at all
		if (self::impureStatic() === 1) {
			if (self::impureStatic() === 1) {
				// Not "always true" because impure invalidates $this
			}
		}
	}

	public function testVoidStatic(): void
	{
		// void static method: hasSideEffects()->yes() invalidates $this
		// so any previously-tracked maybe-impure static call is cleared
		self::voidStatic();
		if (self::maybeImpureStatic() === 1) {
			// voidStatic() invalidated $this
		}
	}

}

// --- Object not invalidated by maybe-impure intermediate call ---

class ObjectInvalidationTest
{

	/** @phpstan-pure */
	public function getValue(): int
	{
		return 42;
	}

	public function maybeImpureIntermediate(): int
	{
		return rand(1, 100);
	}

	/** @phpstan-pure */
	public function pureIntermediate(): int
	{
		return 42;
	}

	/** @phpstan-impure */
	public function impureIntermediate(): int
	{
		echo 'hello';
		return rand(1, 100);
	}

	public function voidIntermediate(): void
	{
		echo 'hello';
	}

	public function testMaybeImpureIntermediate(): void
	{
		// getValue() narrowed to 1, maybeImpureIntermediate() doesn't invalidate $this
		// tip should point to maybeImpureIntermediate()
		if ($this->getValue() === 1) {
			$this->maybeImpureIntermediate();
			if ($this->getValue() === 2) { // always false, tip for maybeImpureIntermediate()
			}
		}
	}

	public function testPureIntermediate(): void
	{
		// getValue() narrowed to 1, pureIntermediate() doesn't invalidate $this
		// no tip because pureIntermediate() is @phpstan-pure
		if ($this->getValue() === 1) {
			$this->pureIntermediate();
			if ($this->getValue() === 2) { // always false, no tip
			}
		}
	}

	public function testImpureIntermediate(): void
	{
		// getValue() narrowed to 1, impureIntermediate() invalidates $this
		// no error because $this is invalidated
		if ($this->getValue() === 1) {
			$this->impureIntermediate();
			if ($this->getValue() === 2) { // no error, $this invalidated
			}
		}
	}

	public function testVoidIntermediate(): void
	{
		// getValue() narrowed to 1, voidIntermediate() invalidates $this
		// no error because $this is invalidated
		if ($this->getValue() === 1) {
			$this->voidIntermediate();
			if ($this->getValue() === 2) { // no error, $this invalidated
			}
		}
	}

}

// --- Intermediate maybe-impure call takes priority over direct call ---

class IntermediateCallPriority
{

	public function fetch(): int
	{
		return rand(1, 100);
	}

	public function next(): bool
	{
		return true;
	}

	public function testIntermediateCallTipPriority(): void
	{
		// fetch() narrowed to 1, next() is intermediate maybe-impure call
		// tip should point to next(), not fetch()
		if ($this->fetch() === 1) {
			$this->next();
			if ($this->fetch() === 2) { // always false, tip for next()
			}
		}
	}

	public function testNoIntermediateCall(): void
	{
		// No intermediate call: tip should point to fetch() itself
		if ($this->fetch() === 1) {
			if ($this->fetch() === 2) { // always false, tip for fetch()
			}
		}
	}

}

// --- No tip when return type alone explains the error ---

class NoTipWhenReturnTypeExplains
{

	public function returnsString(): string
	{
		return 'foo';
	}

	public function test(): void
	{
		// returnsString() always returns string, so string === null
		// is always false regardless of purity. No tip needed.
		if ($this->returnsString() === null) {
		}

		// string !== null is always true regardless of purity.
		if ($this->returnsString() !== null) {
		}
	}

}
