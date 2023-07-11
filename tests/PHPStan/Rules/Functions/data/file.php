<?php

namespace fileFlags;

class Foo {
	public function ok():void {
		$lines = file('http://www.example.com/');
		$lines1 = file('http://www.example.com/', 0);

		$f1 = file(__FILE__, FILE_USE_INCLUDE_PATH);
		$f2 = file(__FILE__, FILE_IGNORE_NEW_LINES);
		$f3 = file(__FILE__, FILE_SKIP_EMPTY_LINES);
	}

	public function error():void {
		$f = file(__FILE__, FILE_APPEND);
	}

	public function noContext():void {
		$f = file(__FILE__, FILE_NO_DEFAULT_CONTEXT);
	}

	public function doFoo():void {
		$fileLines = file(__FILE__, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}
}
