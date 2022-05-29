<?php

namespace Bug7341;

use function PHPStan\Testing\assertType;

final class CsvWriterTerminate extends \php_user_filter
{
	/**
	 * @param resource $in
	 * @param resource $out
	 * @param int      $consumed
	 * @param bool     $closing
	 */
	public function filter($in, $out, &$consumed, $closing): int
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			assertType('stdClass', $bucket);

			if (isset($this->params['terminate'])) {
				$bucket->data = preg_replace('/([^\r])\n/', '$1'.$this->params['terminate'], $bucket->data);
			}
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}

		return \PSFS_PASS_ON;
	}
}
