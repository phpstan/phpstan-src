<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use function count;
use function in_array;

/**
 * Describes which constants a function/method parameter accepts.
 *
 * Parameters are either 'single' (exactly one constant, e.g. `array_unique($flags)`)
 * or 'bitmask' (constants combinable with `|`, e.g. `json_encode($flags)`).
 * Bitmask parameters may have exclusive groups — subsets of constants
 * that are mutually exclusive even within a bitmask.
 *
 * Populated from resources/constantToFunctionParameterMap.php and
 * available via ExtendedParameterReflection::getAllowedConstants().
 *
 * @api
 */
final class ParameterAllowedConstants
{

	/**
	 * @param 'single'|'bitmask' $type
	 * @param list<string> $constants
	 * @param list<list<string>> $exclusiveGroups
	 */
	public function __construct(
		private string $type,
		private array $constants,
		private array $exclusiveGroups,
	)
	{
	}

	public function isBitmask(): bool
	{
		return $this->type === 'bitmask';
	}

	/**
	 * @return list<list<string>>
	 */
	public function getExclusiveGroups(): array
	{
		return $this->exclusiveGroups;
	}

	public function equals(self $other): bool
	{
		return $this->type === $other->type
			&& $this->constants === $other->constants
			&& $this->exclusiveGroups === $other->exclusiveGroups;
	}

	/**
	 * @param list<ConstantReflection> $constants
	 */
	public function check(array $constants): AllowedConstantsResult
	{
		$bitmaskNotAllowed = !$this->isBitmask() && count($constants) > 1;

		$disallowed = [];
		$names = [];

		foreach ($constants as $constant) {
			if ($constant->isBuiltin()->no()) {
				continue;
			}

			$name = $constant->describe();
			$names[] = $name;

			if (in_array($name, $this->constants, true)) {
				continue;
			}

			$disallowed[] = $constant;
		}

		$violated = [];
		if ($this->isBitmask()) {
			foreach ($this->exclusiveGroups as $group) {
				$matched = [];
				foreach ($names as $name) {
					if (!in_array($name, $group, true)) {
						continue;
					}

					$matched[] = $name;
				}

				if (count($matched) < 2) {
					continue;
				}

				$violated[] = $matched;
			}
		}

		return new AllowedConstantsResult($disallowed, $violated, $bitmaskNotAllowed);
	}

}
