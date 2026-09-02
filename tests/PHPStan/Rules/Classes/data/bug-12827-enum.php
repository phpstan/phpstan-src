<?php // lint >= 8.1

namespace Bug12827Enum {
	enum Suit {
		case Hearts;
	}
}

namespace Bug12827EnumConsumer {
	use Bug12827Enum\Suit;

	class Consumer
	{

		public function doFoo(): Suit
		{
			return SUIT::Hearts;
		}

		public function doBar(): Suit
		{
			return Suit::Hearts;
		}

	}
}
