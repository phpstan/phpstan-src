<?php // lint >= 8.1

namespace Bug9142;

enum MyEnum: string
{

	case One = 'one';
	case Two = 'two';
	case Three = 'three';

	public function thisTypeWithSubtractedEnumCase(): int
	{
		if ($this === self::Three) {
			return -1;
		}

		if ($this === self::Three) {
			return 0;
		}

		return 1;
	}

	public function enumTypeWithSubtractedEnumCase(self $self): int
	{
		if ($self === self::Three) {
			return -1;
		}

		if ($self === self::Three) {
			return 0;
		}

		return 1;
	}

}
