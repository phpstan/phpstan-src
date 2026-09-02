<?php declare(strict_types = 1);

namespace Bug12827StaticCall {
	class Post {

		public static function create(): self
		{
			return new self();
		}

	}
}

namespace Bug12827StaticCallConsumer {
	use Bug12827StaticCall\Post;

	class Consumer
	{

		public function doFoo(): Post
		{
			return POST::create();
		}

		public function doBar(): Post
		{
			return Post::create();
		}

	}
}
