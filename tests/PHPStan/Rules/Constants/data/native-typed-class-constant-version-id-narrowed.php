<?php // lint >= 8.3

namespace NativeTypedClassConstantPhpVersionNarrowed;

if (PHP_VERSION_ID >= 80300) {
	class Foo {
		public const string BAR = 'bar';
	}
}
