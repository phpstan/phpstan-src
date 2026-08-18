<?php declare(strict_types = 1);

if (!class_exists('ValueError')) {
	class ValueError extends Error
	{
	}
}

if (!interface_exists('Stringable')) {
	interface Stringable
	{
		public function __toString(): string;
	}
}

if (!class_exists('SkipPolyfillNotNativeClass')) {
	class SkipPolyfillNotNativeClass
	{
	}
}

class SkipPolyfillUnconditionalClass
{
}

if (!defined('JSON_THROW_ON_ERROR')) {
	define('JSON_THROW_ON_ERROR', 4194304);
}

if (!defined('SKIP_POLYFILL_NOT_NATIVE_CONSTANT')) {
	define('SKIP_POLYFILL_NOT_NATIVE_CONSTANT', 1);
}
