<?php declare(strict_types = 1);

namespace Bug10245;

/**
 * @throws \Exception
 */
function produceInt(): int
{
	return 1;
}

function testTryCatchInWhileTrue(): void
{
	while (true) {
		try {
			$a = produceInt();
			break;
		} catch (\Throwable $e) {}
	}

	echo $a;
}

function testIfBreakInWhileTrue(int $max): void
{
	$i = 0;
	while (true) {
		if ($i > $max) {
			$result = 'done';
			break;
		}
		++$i;
	}
	print $result;
}
