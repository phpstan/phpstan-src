<?php // lint >= 8.1

namespace LooseComparisonAgainstEnums;

enum FooUnitEnum
{
	case A;
	case B;
}

enum FooBackedEnum: string
{
	case A = 'A';
	case B = 'B';
}

class InArrayTest
{
	public function enumVsString(FooUnitEnum $u, FooBackedEnum $b): void
	{
		if (in_array($u, ['A'])) {
		}

		if (in_array($u, ['A'], false)) {
		}

		if (in_array($b, ['A'])) {
		}

		if (in_array($b, ['A'], false)) {
		}

		if (in_array(rand() ? $u : $b, ['A'], false)) {
		}
	}

	public function stringVsEnum(FooUnitEnum $u, FooBackedEnum $b): void
	{
		if (in_array('A', [$u])) {
		}

		if (in_array('A', [$u], false)) {
		}

		if (in_array('A', [$b])) {
		}

		if (in_array('A', [$b], false)) {
		}

		if (in_array('A', [rand() ? $u : $b], false)) {
		}
	}

	public function enumVsBool(FooUnitEnum $u, FooBackedEnum $b, bool $bl): void
	{
		if (in_array($u, [$bl])) {
		}

		if (in_array($u, [$bl], false)) {
		}

		if (in_array($b, [$bl])) {
		}

		if (in_array($b, [$bl], false)) {
		}

		if (in_array(rand() ? $u : $b, [$bl], false)) {
		}
	}

	public function boolVsEnum(FooUnitEnum $u, FooBackedEnum $b, bool $bl): void
	{
		if (in_array($bl, [$u])) {
		}

		if (in_array($bl, [$u], false)) {
		}

		if (in_array($bl, [$b])) {
		}

		if (in_array($bl, [$b], false)) {
		}

		if (in_array($bl, [rand() ? $u : $b], false)) {
		}
	}

	public function null(FooUnitEnum $u, FooBackedEnum $b): void
	{
		if (in_array($u, [null])) {
		}

		if (in_array(null, [$b])) {
		}
	}

	public function nullableEnum(?FooUnitEnum $u, string $s): void
	{
		// null == ""
		if (in_array($u, [$s])) {
		}

		if (in_array($s, [$u])) {
		}
	}

	/**
	 * @param array<string> $strings
	 * @param array<FooUnitEnum> $unitEnums
	 */
	public function dynamicValues(FooUnitEnum $u, string $s, array $strings, array $unitEnums): void
	{
		if (in_array($u, $unitEnums)) {
		}

		if (in_array($u, $unitEnums, false)) {
		}

		if (in_array($u, $unitEnums, true)) {
		}

		if (in_array($u, $strings)) {
		}

		if (in_array($u, $strings, false)) {
		}

		if (in_array($u, $strings, true)) {
		}

		if (in_array($s, $strings)) {
		}

		if (in_array($s, $strings, false)) {
		}

		if (in_array($s, $strings, true)) {
		}

		if (in_array($s, $unitEnums)) {
		}

		if (in_array($s, $unitEnums, false)) {
		}

		if (in_array($s, $unitEnums, true)) {
		}
	}

    /**
     * @param non-empty-array<FooUnitEnum::A> $nonEmptyA
     * @return void
     */
    public function nonEmptyArray(array $nonEmptyA): void
    {
        if (in_array(FooUnitEnum::B, $nonEmptyA)) {
        }

        if (in_array(FooUnitEnum::A, $nonEmptyA)) {
        }

        if (in_array(FooUnitEnum::A, $nonEmptyA, false)) {
        }

        if (in_array(FooUnitEnum::A, $nonEmptyA, true)) {
        }

        if (in_array(FooUnitEnum::B, $nonEmptyA, false)) {
        }

        if (in_array(FooUnitEnum::B, $nonEmptyA, true)) {
        }
    }
}
