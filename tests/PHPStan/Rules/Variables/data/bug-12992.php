<?php declare(strict_types = 1);

namespace Bug12992;

class HelloWorld
{
	public function test(\Closure $fx): void
	{
		$capture = tmpfile();
		if ($capture !== false) {
			$capturePath = stream_get_meta_data($capture)['uri'] ?? '';
			if (@is_writable($capturePath)) {
				$errorLogPrevious = ini_set('error_log', $capturePath);
			} else {
				$capture = false;
			}
		}

		if ($capture !== false) {
			fclose($capture);

			ini_set('error_log', $errorLogPrevious);
		}
	}
}

function test(bool $v): void
{
	if ($v) {
	    if (rand() === 3) {
	        $newvar = 1;
	    } else {
	        $v = false;
	    }
	}

    if ($v === true) {
        echo $newvar;
    }
}
