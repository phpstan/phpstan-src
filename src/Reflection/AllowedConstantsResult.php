<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * Result of checking constants passed to a parameter against its allowed set.
 *
 * Returned by ExtendedParameterReflection::checkAllowedConstants(). Reports
 * three kinds of problems: constants not in the allowed list, mutually exclusive
 * constants combined in a bitmask, and bitmask usage on a single-value parameter.
 *
 * @api
 */
final class AllowedConstantsResult
{

	/**
	 * @param list<ConstantReflection> $disallowedConstants
	 * @param list<list<string>> $violatedExclusiveGroups
	 */
	public function __construct(
		private array $disallowedConstants,
		private array $violatedExclusiveGroups,
		private bool $bitmaskNotAllowed,
	)
	{
	}

	public function isOk(): bool
	{
		return $this->disallowedConstants === [] && $this->violatedExclusiveGroups === [] && !$this->bitmaskNotAllowed;
	}

	public function isBitmaskNotAllowed(): bool
	{
		return $this->bitmaskNotAllowed;
	}

	/**
	 * @return list<ConstantReflection>
	 */
	public function getDisallowedConstants(): array
	{
		return $this->disallowedConstants;
	}

	/**
	 * @return list<list<string>>
	 */
	public function getViolatedExclusiveGroups(): array
	{
		return $this->violatedExclusiveGroups;
	}

}
