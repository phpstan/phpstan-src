<?php declare(strict_types = 1);

namespace Bug12827Classes {
	class Post {

		public const FOO = 'foo';

	}

	enum Suit {
		case Hearts;
	}
}

namespace Bug12827ClassesConsumer {
	use Bug12827Classes\Post;
	use Bug12827Classes\Suit;

	class Consumer
	{

		public function doFoo(): object
		{
			return new POST();
		}

		public function doBar(): object
		{
			return new Post();
		}

		public function doBaz(): string
		{
			return POST::class;
		}

		public function doLorem(): string
		{
			return Post::class;
		}

		public function doIpsum(): string
		{
			return POST::FOO;
		}

		public function doDolor(): string
		{
			return Post::FOO;
		}

		public function doSit(): Suit
		{
			return SUIT::Hearts;
		}

		public function doAmet(): Suit
		{
			return Suit::Hearts;
		}

	}
}
