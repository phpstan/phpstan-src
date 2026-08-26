<?php declare(strict_types = 1);

namespace Bug12827 {
	#[\Attribute(\Attribute::TARGET_METHOD)]
	class Post {}
}

namespace Bug12827Consumer {
	use Bug12827\Post;

	class Controller {
		#[POST]
		public function action(): void {}

		#[Post]
		public function correctAction(): void {}
	}
}

namespace Bug12827Alias {
	use Bug12827\Post as MyPost;

	class Controller {
		#[MyPost]
		public function action(): void {}
	}
}
