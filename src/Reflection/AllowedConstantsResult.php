<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

final class AllowedConstantsResult
{

	/**
	 * @param list<ConstantReflection> $disallowedConstants
	 * @param list<list<string>> $violatedExclusiveGroups
	 */
	public function __construct(
		private array $disallowedConstants,
		private array $violatedExclusiveGroups,
		private bool $bitmaskNotAllowed = false,
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
