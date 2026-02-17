<?php declare(strict_types = 1);

namespace Bug7503;

class HelloWorld
{
	public function processWithMightNotBeDefined(): void
	{
		switch ($this->getStatus()) {
			case 'if break else return; fail':
				if ($this->hasFeatureFlag()) {
					$doSomething = true;
					break;
				}

				return;
			default:
				throw new \Exception('unknown status');
		}

		echo $doSomething;
	}

	public function processWithoutMightNotBeDefined(): void
	{
		switch ($this->getStatus()) {
			case 'if return else break; success':
				if ($this->hasFeatureFlag()) {
					return;
				}

				$doSomething = true;
				break;
			default:
				throw new \Exception('unknown status');
		}
		echo $doSomething;
	}

	public function getStatus(): string
	{
		return '';
	}

	public function hasFeatureFlag(): bool
	{
		return true;
	}
}
