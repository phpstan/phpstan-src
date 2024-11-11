<?php declare(strict_types = 1);

namespace Bug11992;

// valid
function exampleA(): void {
	$bc = 0;
	for ($i = 0; $i < 3; ++$i) {
		$bc++;
	}
	printf("bc: %d\n", $bc);
}

// not valid? Why? this is deterministic even from a static standpoint
function exampleB(): void {
	$bc = 0;
	for ($c = (0x80 | 0x40); $c & 0x80; $c = $c << 1) {
		$bc++;
	}
	printf("bc: %d\n", $bc);
}

// invalid because valid() is theoretically infinite?
function exampleC(): void {
	for (
		$it = new \DirectoryIterator('/tmp');
		$it->valid();
		$it->next()
	) {
		printf("name: %s\n", $it->getFilename());
	}
	printf("done\n");
}

exampleA();
exampleB();
exampleC();
