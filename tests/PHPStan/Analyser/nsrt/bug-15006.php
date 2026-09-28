<?php declare(strict_types = 1);

namespace Bug15006;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param ?non-negative-int $field
	 * @return ($field is null
	 *   ? ($nullable is true ? ($numeric is true ? list<?numeric-string> : list<?string>) : ($numeric is true ? list<numeric-string> : list<string>))
	 *   : ($nullable is true ? ($numeric is true ? array<?numeric-string> : array<?string>) : ($numeric is true ? array<numeric-string> : array<string>))
	 * )
	 */
	public function bar(?int $field = null, bool $nullable = true, bool $numeric = false): array
	{
		return [];
	}

	/**
	 * @return ($a is true ? ($b is true ? non-empty-list<int> : non-empty-list<string>) : ($b is true ? non-empty-list<float> : non-empty-list<bool>))
	 */
	public function nonEmptyList(bool $a, bool $b): array
	{
		return [1];
	}

	/**
	 * @template T
	 * @param T $v
	 * @return (T is int ? ($a is true ? list<int> : list<string>) : ($a is true ? list<float> : list<bool>))
	 */
	public function templateConditional($v, bool $a): array
	{
		return [];
	}

	public function test(?int $field, bool $nullable, bool $numeric, bool $a, bool $b, $v): void
	{
		assertType('array<string|null>', $this->bar($field, $nullable, $numeric));
		assertType('list<string|null>', $this->bar(null, $nullable, $numeric));
		assertType('non-empty-list<bool|float|int|string>', $this->nonEmptyList($a, $b));
		assertType('list<bool|float|int|string>', $this->templateConditional($v, $a));
	}

}
