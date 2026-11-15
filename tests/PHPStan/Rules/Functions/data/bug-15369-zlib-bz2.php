<?php

// php-src tests of https://github.com/php/php-src/commit/f8c854e82faec850981a4a65e6c64a822ec580af
// and https://github.com/php/php-src/commit/3fda4e9c187929cb95d0eec90c7fcd82e4161600

namespace Bug15369Zlib\Gh17745 {

	// ext/zlib/tests/gh17745.phpt
	$obj = new \stdClass;
	$obj->level = 3;
	var_dump(deflate_init(ZLIB_ENCODING_RAW, $obj));

	class Options {
		public int $level = 3;
	}
	var_dump(deflate_init(ZLIB_ENCODING_RAW, new Options));

}

namespace Bug15369Zlib\Gh17745Inflate {

	// ext/zlib/tests/gh17745_inflate.phpt
	$obj = new \stdClass;
	$obj->level = 3;
	var_dump(inflate_init(ZLIB_ENCODING_RAW, $obj));

	class Options {
		public int $level = 3;
	}
	var_dump(inflate_init(ZLIB_ENCODING_RAW, new Options));

}

namespace Bug15369Zlib\Gh22142 {

	// ext/zlib/tests/gh22142.phpt
	class Options {
		public int $level;
	}

	try {
		deflate_init(ZLIB_ENCODING_DEFLATE, new Options());
	} catch (\TypeError $e) {
		echo $e::class, ': ', $e->getMessage(), PHP_EOL;
	}

}

namespace Bug15369Zlib\Gh22142Inflate {

	// ext/zlib/tests/gh22142_inflate.phpt
	class Options {
		public int $window;
	}

	try {
		inflate_init(ZLIB_ENCODING_DEFLATE, new Options());
	} catch (\TypeError $e) {
		echo $e::class, ': ', $e->getMessage(), PHP_EOL;
	}

}

namespace Bug15369Zlib\FilterBrokenObjectOptions {

	// ext/zlib/tests/filter_broken_object_options.phpt
	// $params of stream_filter_append() stays mixed, a user stream filter may expect an object
	class Params {
		public int $memory = 6;
		public int $window = 15;
		public int $level = 6;
	}

	$fp = fopen('php://stdout', 'w');
	stream_filter_append($fp, 'zlib.deflate', STREAM_FILTER_WRITE, new Params);
	stream_filter_append($fp, 'zlib.inflate', STREAM_FILTER_WRITE, new Params);
	fwrite($fp, "Hello world, hopefully not broken\n");

}

namespace Bug15369Bz2\FilterBrokenObjectOptions {

	// ext/bz2/tests/filter_broken_object_options.phpt
	class ParamsCompress {
		public int $blocks = 5;
		public int $work = 10;
	}

	class ParamsDecompress {
		public bool $concatenated = true;
		public bool $small = true;
	}

	$fp = fopen('php://stdout', 'w');
	stream_filter_append($fp, 'bzip2.compress', STREAM_FILTER_WRITE, new ParamsCompress);
	stream_filter_append($fp, 'bzip2.decompress', STREAM_FILTER_WRITE, new ParamsDecompress);
	fwrite($fp, "Hello world, hopefully not broken\n");

}
