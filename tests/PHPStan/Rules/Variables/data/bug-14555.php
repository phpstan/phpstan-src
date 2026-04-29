<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14555;

class ValueObject {
	function __construct(
		public readonly string $value,
	) {}
}

class SomeDTO {
	function __construct(
        public readonly ValueObject $value,
    ) {}
}

class StaticHolder {
	public static ValueObject $value;
}

/** @param array<string, list<SomeDTO>> $array */
function exampleNullCoalesce(array $array): void
{
	$someValue = $array['someKey'][0]->value->value ?? null;

	$dto = $array['someKey'][0] ?? null;
	$someValue2 = $dto->value->value ?? null;
}

/** @param array<string, list<SomeDTO>> $array */
function exampleIsset(array $array): void
{
	if (isset($array['someKey'][0]->value->value)) {
		echo 'yes';
	}
}

/** @param array<string, list<SomeDTO>> $array */
function exampleNullCoalesceAssign(array $array): void
{
	$someValue = $array['someKey'][0]->value->value ??= 'default';
}

/** @param array<string, list<StaticHolder>> $array */
function exampleStaticProperty(array $array): void
{
	$someValue = $array['someKey'][0]::$value->value ?? null;
}
