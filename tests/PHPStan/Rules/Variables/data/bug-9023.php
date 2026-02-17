<?php declare(strict_types = 1);

namespace Bug9023;

function unreliableFunc(): string
{
	if (random_int(1, 5) === 1) {
		return 'something';
	} else {
		throw new \Exception('Just demonstrating');
	}
}

function testRetryUntilSuccess(): void
{
	while (true) {
		try {
			$Defined = unreliableFunc();
			break;
		} catch (\Exception $e) {
			sleep(10);
			continue;
		}
	}

	echo $Defined;
}
