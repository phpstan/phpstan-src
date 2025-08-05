<?php declare(strict_types = 1);

namespace Bug4204;

interface Block
{
	public function getSettings(): mixed;
}

class HelloWorld
{
	/**
	 * @param array<int, object> $blocks
	 */
	public function sayHello(array $blocks): void
	{
		foreach ($blocks as $block) {
			$settings = $block->getSettings();

			if (isset($settings['name'])) {
				// switch name with code key
				$settings['code'] = $settings['name'];
				unset($settings['name']);
			}
		}
	}
}
