<?php // lint >= 8.1

namespace Bug7763;

enum MyEnum: int {
	case Case1 = 1;
	case Case2 = 2;

	public function test(self $enum): string
	{
		$mapping = array_filter([
			self::Case1->value => $this->maybeNull(),
			self::Case2->value => $this->maybeNull(),
		]);

		if (array_key_exists($enum->value, $mapping)) {
			return $mapping[$enum->value]; // Offset 1|2 does not exist on array{1?: non-falsy-string, 2?: non-falsy-string}
		}

		return '';
	}

	private function maybeNull(): ?string
	{
		return (bool) rand(0, 1) ? '' : null;
	}
}
