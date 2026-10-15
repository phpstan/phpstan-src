<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PHPStan\Rules\Registry;
use function get_class;

/**
 * The rules of the wrapped registry whose class is one of the given ones.
 */
final class RuleScopedRegistry implements Registry
{

	/** @var array<string, array<mixed>> */
	private array $cache = [];

	/**
	 * @param array<string, true> $ruleClasses
	 */
	public function __construct(private Registry $registry, private array $ruleClasses)
	{
	}

	#[Override]
	public function getRules(string $nodeType): array
	{
		if (!isset($this->cache[$nodeType])) {
			$rules = [];
			foreach ($this->registry->getRules($nodeType) as $rule) {
				if (!isset($this->ruleClasses[get_class($rule)])) {
					continue;
				}

				$rules[] = $rule;
			}

			$this->cache[$nodeType] = $rules;
		}

		return $this->cache[$nodeType];
	}

}
