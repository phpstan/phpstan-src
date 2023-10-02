<?php declare(strict_types = 1);

namespace Bug9951;

class AbstractScope {}
class Expressionable {}

class Cl
{
    /**
     * @param AbstractScope|array<int, AbstractScope|Expressionable>|string|Expressionable $field
     * @param ($field is string|Expressionable ? ($value is null ? mixed : string) : never) $operator
     * @param ($operator is string ? mixed : never) $value
     */
    public function addCondition($field, $operator = null, $value = null): void
    {
	}

	public function testStr(string $field, bool $value): void
	{
		$this->addCondition($field, $value);
	}

	public function testMixed(mixed $field, bool $value): void
	{
		$this->addCondition($field, $value); // no phpstan expected, no phpstan error was present before rel. 1.10.36
	}

	public function testMixedAsUnion(string|object|null $field, bool $value): void
	{
		$this->addCondition($field, $value);
	}
}
