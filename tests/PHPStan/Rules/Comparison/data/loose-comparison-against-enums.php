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

	public function nullableEnum(?FooUnitEnum $u, string $s): void
	{
		// null == ""
		if (in_array($u, [$s])) {
		}

		if (in_array($s, [$u])) {
		}
	}
}
