<?php

/**
 * @template-covariant T as object
 */
final class WeakReference
{

	/**
	 * @template TIn as object
	 * @param TIn $referent
	 * @return WeakReference<TIn>
	 */
	public static function create(object $referent): WeakReference {}

	/** @return ?T */
	public function get() {}
}
