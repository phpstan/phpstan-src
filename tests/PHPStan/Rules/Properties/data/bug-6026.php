<?php

namespace Bug6026;

class Foo {
	/**
	 * @param resource $theResource
	 * @return bool
	 */
	public function Process($theResource) : bool
	{
		$bucket = \stream_bucket_make_writeable($theResource);
		if ( $bucket === null )
		{
			return false;
		}
		$bucketLen = $bucket->datalen ?? 0;
		$bucketLen = isset($bucket->datalen) ? $bucket->datalen : 0;

		return true;
	}
}

