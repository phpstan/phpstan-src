<?php declare(strict_types = 1);

// the restart disarms pcov rather than unloading it, which keeps pcov_*() reflectable
if (extension_loaded("pcov") && !in_array((string) ini_get("pcov.enabled"), ["", "0"], true)) {
	fwrite(STDERR, "pcov is still enabled after the restart\n");
	exit(1);
}
