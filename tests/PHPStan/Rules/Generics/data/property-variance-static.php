<?php

namespace PropertyVariance\Static;

/**
 * @template-contravariant X
 */
class A {
	/** @var X */
	static $a;

	/** @var X */
	private static $b;
}
