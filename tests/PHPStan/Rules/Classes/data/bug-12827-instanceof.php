<?php declare(strict_types = 1);

namespace Bug12827InstanceOf {
	class Post {}
}

namespace Bug12827InstanceOfConsumer {
	use Bug12827InstanceOf\Post;

	class Consumer
	{

		public function doFoo(object $o): bool
		{
			return $o instanceof POST;
		}

		public function doBar(object $o): bool
		{
			return $o instanceof Post;
		}

	}
}
