<?php

namespace Bug7424;

class Test
{
	public function run(): void
	{
		$data = $this->getInitData();

		array_push($data, ...$this->getExtra());

		if (empty($data)) {
			return;
		}

		echo 'Proceeding to process data';
	}

	/**
	 * @return string[]
	 */
	protected function getInitData(): array
	{
		return [];
	}

	/**
	 * @return string[]
	 */
	protected function getExtra(): array
	{
		return [];
	}
}
