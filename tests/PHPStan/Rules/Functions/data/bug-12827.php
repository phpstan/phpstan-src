<?php declare(strict_types = 1);

namespace Bug12827Functions {
	class Post {}
}

namespace Bug12827FunctionsConsumer {
	use Bug12827Functions\Post;

	function doFoo(POST $post): POST
	{
		return $post;
	}

	function doBar(Post $post): Post
	{
		return $post;
	}
}
