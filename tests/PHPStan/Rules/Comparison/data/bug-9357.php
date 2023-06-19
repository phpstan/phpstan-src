<?php // lint >= 8.1

namespace Bug9357;

enum MyEnum: string {
	case A = 'a';
	case B = 'b';
}

class My {
	/** @phpstan-impure */
	public function getType(): MyEnum {
		echo "called!";
		return rand() > 0.5 ? MyEnum::A : MyEnum::B;
	}
}

function test(My $m): void {
	echo match ($m->getType()) {
		MyEnum::A => 1,
		MyEnum::B => 2,
	};
}
