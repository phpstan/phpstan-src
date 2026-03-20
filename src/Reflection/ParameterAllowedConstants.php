<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use function count;
use function in_array;

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
		private array $exclusiveGroups = [],
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

	private function resolveConstantName(ConstantReflection $constant): string
	{
		if ($constant instanceof ClassConstantReflection) {
			return $constant->getDeclaringClass()->getName() . '::' . $constant->getName();
		}

		return $constant->getName();
	}

	/**
	 * @param list<ConstantReflection> $constants
	 */
	public function check(array $constants): AllowedConstantsResult
	{
		$disallowed = [];
		$names = [];

		foreach ($constants as $constant) {
			$name = $this->resolveConstantName($constant);
			$names[] = $name;

			if (in_array($name, $this->constants, true)) {
				continue;
			}

			$disallowed[] = $constant;
		}

		$violated = [];
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

		return new AllowedConstantsResult($disallowed, $violated);
	}

}
