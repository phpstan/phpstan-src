<?php

namespace MethodSignatureVariance\Static;

/**
 * @template-contravariant X
 */
class C {
	/**
	 * @template Y of X
	 * @return Y
	 */
	static function a() {}

	/**
	 * @return X
	 */
	static function b() {}

	/**
	 * @return X
	 */
	private static function c() {}
}
