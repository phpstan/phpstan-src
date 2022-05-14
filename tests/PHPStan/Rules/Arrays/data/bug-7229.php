<?php declare(strict_types = 1);

namespace Bug7229;

class Config
{
	// HINTS: a config comes from a config file which always will be an array.
	// E.g: return require 'config.php'; // => array<int|string, mixed>
	// where mixed is rather the short write for scalar|array<mixed>|null which
	// can be nested in N-depth (merging several configs)

	/**
	 * Returns the value from the given array.
	 *
	 * @param array<int|string, mixed>|mixed $config The array to search in
	 * @param array<int|string, string> $parts Parts to look for inside the array
	 *
	 * @return array<int|string, mixed>|mixed Found value or null if not available
	 */
	protected function _getValueFromArray( $config, $parts )
	{
		// $config type is mixed or array !?

		if ( ( $key = array_shift( $parts ) ) !== null && isset( $config[$key] ) ) {

			// $config type NOT mixed

			if ( count( $parts ) > 0 ) {
				return $this->_getValueFromArray( $config[$key], $parts );
			}

			return $config[$key];
		}

		return null;
	}
}
