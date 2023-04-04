<?php

namespace ClosedResource;

function doFoo() {
	$fp = tmpfile();
	if ($fp === false) {
		die('could not open temp file');
	}
	fread($fp, 1);
	feof($fp);
	fwrite($fp, 'should work');
	fclose($fp);

	// working on a already closed resource should error
	fread($fp, 1);
	feof($fp);
	fwrite($fp, 'err - already closed');
	fclose($fp);
}
