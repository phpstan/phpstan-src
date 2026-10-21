<?php declare(strict_types = 1);

if (extension_loaded("pcov")) {
	fwrite(STDERR, "pcov is still loaded after the restart\n");
	exit(1);
}
