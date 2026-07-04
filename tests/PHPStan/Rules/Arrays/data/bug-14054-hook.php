<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug14054Hook;

final class WithHook
{

	/** @var array<int, string> */
	public array $data = [] {
		set (array|string $value) {
			$this->data = (array) $value;
		}
	}

}

function testHook(WithHook $w): void
{
	$w->data[] = 'x';
	$w->data['key'] = 'y';
}
