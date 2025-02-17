<?php

declare(strict_types=1);

namespace App;

final class HomepageController
{
	public function homeAction($someUnrelatedError = false): array
	{
		return [
			'title' => 'Homepage',
			'something' => $this->getSomething(),
		];
	}

	public function contactAction($someUnrelatedError): array
	{
		return [
			'title' => 'Contact',
			'something' => $this->getSomething(),
		];
	}

	private function getSomething(): array
	{
		return [];
	}
}
