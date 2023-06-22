<?php

namespace flockFlags;

class flock {
	/** @param resource $fp */
	public function ok($fp):void {

		if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
			ftruncate($fp, 0);      // truncate file
			fwrite($fp, "Write something here\n");
			fflush($fp);            // flush output before releasing the lock
			flock($fp, LOCK_UN);    // release the lock
		} else {
			echo "Couldn't get the lock!";
		}

		fclose($fp);
	}

	/** @param resource $fp */
	public function ok1($fp):void {

		/* Activate the LOCK_NB option on an LOCK_EX operation */
		if(!flock($fp, LOCK_EX | LOCK_NB)) {
			echo 'Unable to obtain lock';
			exit(-1);
		}
		if(!flock($fp, LOCK_SH | LOCK_NB)) {
			echo 'Unable to obtain lock';
			exit(-1);
		}
		if(!flock($fp, LOCK_UN | LOCK_NB)) {
			echo 'Unable to obtain lock';
			exit(-1);
		}

		/* ... */

		fclose($fp);
	}

	/** @param resource $fp */
	public function error($fp):void {
		$f = flock($fp, FILE_APPEND);
	}
}
