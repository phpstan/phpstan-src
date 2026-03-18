<?php declare(strict_types = 1);

namespace Bug7806;

class HelloWorld
{

	public function test(): void
	{
		try {
			preg_match('/pattern/', 'subject', $reasons);
		} catch (\Throwable $e) {
			if (!empty($reasons)) {
				echo implode(', ', $reasons);
			}
		}
	}

}
