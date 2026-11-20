<?php

namespace ContinueTargetingSwitch;

function withoutLoop(int $i): void
{
	switch ($i) {
		case 1:
			// nothing else to do
			continue;
		case 2:
			switch ($i) {
				default:
					continue 2;
			}
	}
}

/**
 * @param list<int> $items
 */
function insideLoop(array $items): void
{
	foreach ($items as $item) {
		switch ($item) {
			case 1:
				continue;
			case 2:
				continue 2;
			case 3:
				foreach ($items as $inner) {
					continue 2;
				}
				break;
			case 4:
				while (true) {
					continue;
				}
		}

		for ($i = 0; $i < $item; $i++) {
			switch ($i) {
				case 0:
					switch ($item) {
						case 0:
							continue;
					}
			}
		}

		echo $item;
	}
}

/**
 * @param list<int> $items
 */
function closureIsBoundary(array $items): void
{
	foreach ($items as $item) {
		$f = static function () use ($item): void {
			switch ($item) {
				case 1:
					continue;
			}
		};
		$f();
	}
}
