<?php declare(strict_types = 1);

namespace Bug12827Classes {
	class Post {}
}

namespace Bug12827ClassesConsumer {
	use Bug12827Classes\Post;

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

	}
}
