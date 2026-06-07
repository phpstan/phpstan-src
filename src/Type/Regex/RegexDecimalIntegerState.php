<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

/**
 * Tracks, while walking a regex that matches a decimal integer, whether the match
 * is guaranteed to be a *canonical* decimal integer string.
 *
 * A digit sequence is canonical only when it can never have a leading zero followed
 * by more digits (like "00" or "007"), nor be a negative zero ("-0"). Such values
 * stay strings when used as array keys, so they must be narrowed to numeric-string
 * rather than decimal-int-string.
 *
 * The idea (per the walk left to right): remember whether the leading digit can be a
 * zero; as soon as another digit follows such a zero-able lead, the match is no longer
 * a canonical decimal integer.
 *
 * @immutable
 */
final class RegexDecimalIntegerState
{

	public function __construct(
		// a leading minus sign has been seen, so "-0" would be possible together with a zero-able lead
		private bool $seenSign,
		// the leading digit is pinned down (a mandatory digit has been consumed)
		private bool $leadResolved,
		// the leading digit can be "0"
		private bool $leadCanBeZero,
		// a non-canonical shape (like "00") is possible
		private bool $nonCanonical,
		// pending quantifier of the next digit-producing atom: it may repeat
		private bool $atomRepeats,
		// pending quantifier of the next digit-producing atom: it is optional
		private bool $atomOptional,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self(false, false, false, false, false, false);
	}

	/** Remembers the quantifier of the atom that follows, to be consumed by the next digit. */
	public function withPendingQuantifier(bool $repeats, bool $optional): self
	{
		return new self($this->seenSign, $this->leadResolved, $this->leadCanBeZero, $this->nonCanonical, $repeats, $optional);
	}

	/** Consumes (clears) the pending quantifier flags without recording a digit. */
	public function consumePendingQuantifier(): self
	{
		return new self($this->seenSign, $this->leadResolved, $this->leadCanBeZero, $this->nonCanonical, false, false);
	}

	public function withSign(): self
	{
		return new self(true, $this->leadResolved, $this->leadCanBeZero, $this->nonCanonical, $this->atomRepeats, $this->atomOptional);
	}

	/**
	 * Records one digit character position, consuming any pending quantifier.
	 *
	 * @param bool $canBeZero whether this digit can be "0"
	 * @param bool $spansMultipleDigits whether the atom itself already holds more than one digit (e.g. the literal "12")
	 */
	public function withDigit(bool $canBeZero, bool $spansMultipleDigits): self
	{
		$mandatory = !$this->atomOptional;
		$repeats = $this->atomRepeats || $spansMultipleDigits;

		$leadResolved = $this->leadResolved;
		$leadCanBeZero = $this->leadCanBeZero;
		$nonCanonical = $this->nonCanonical;

		// a digit appears after an already zero-able leading digit: that is a leading-zero string like "00"
		if ($leadCanBeZero) {
			$nonCanonical = true;
		}

		// while the leading digit is not pinned down yet, this digit may become the lead
		if (!$leadResolved && $canBeZero) {
			$leadCanBeZero = true;
		}

		// a single zero-able digit repeated also produces a leading-zero string like "00"
		if ($repeats && $leadCanBeZero) {
			$nonCanonical = true;
		}

		if ($mandatory) {
			$leadResolved = true;
		}

		return new self($this->seenSign, $leadResolved, $leadCanBeZero, $nonCanonical, false, false);
	}

	/** Resets the per-branch state while keeping the pending quantifier the branch inherits. */
	public function forAlternationBranch(): self
	{
		return new self(false, false, false, false, $this->atomRepeats, $this->atomOptional);
	}

	/**
	 * Merges the branches of an alternation, treating it as a single digit position
	 * appended to the state seen before the alternation.
	 *
	 * @param array<self> $branches
	 */
	public function mergeAlternationBranches(array $branches): self
	{
		$branchNonCanonical = false;
		$branchLeadCanBeZero = false;
		$branchResolved = $branches !== [];
		foreach ($branches as $branch) {
			$branchNonCanonical = $branchNonCanonical || !$branch->isLeadingZeroSafe();
			$branchLeadCanBeZero = $branchLeadCanBeZero || $branch->leadCanBeZero;
			$branchResolved = $branchResolved && $branch->leadResolved;
		}

		// the alternation is unsafe if a branch is internally unsafe, or if a preceding
		// zero-able lead is now followed by these extra digits (when the branches contain
		// non-digits the whole match is not a decimal integer anyway, so this stays safe)
		$nonCanonical = $this->nonCanonical || $branchNonCanonical || $this->leadCanBeZero;
		$leadCanBeZero = $this->leadResolved
			? $this->leadCanBeZero
			: ($this->leadCanBeZero || $branchLeadCanBeZero);
		$leadResolved = $this->leadResolved || $branchResolved;

		return new self($this->seenSign, $leadResolved, $leadCanBeZero, $nonCanonical, false, false);
	}

	public function hasSeenSign(): bool
	{
		return $this->seenSign;
	}

	/**
	 * Whether the decimal-integer match is guaranteed to be canonical, i.e. it can
	 * never have a leading zero (like "00" or "007") nor be a negative zero ("-0").
	 */
	public function isLeadingZeroSafe(): bool
	{
		return !$this->nonCanonical && !($this->seenSign && $this->leadCanBeZero);
	}

}
