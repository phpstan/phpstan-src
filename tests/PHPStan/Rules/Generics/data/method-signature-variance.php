<?php

namespace MethodSignatureVariance;

class C {
	/**
	 * @template U
	 * @return void
	 */
	function a() {}

	/**
	 * @template-covariant U
	 * @return void
	 */
	function b() {}

	/**
	 * @template-contravariant U
	 * @return void
	 */
	function c() {}
}
