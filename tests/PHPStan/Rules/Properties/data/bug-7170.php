<?php declare(strict_types = 1);

namespace Bug7170;

/**
 * @template Tdata of array{extension?: array<mixed>}
 */
class Data
{
	/**
	 * @var Tdata
	 */
	private $data;

	/**
	 * @param Tdata $data
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	public function setExtensionProperty(): void
	{
		if (!isset($this->data['extension'])) {
			$this->data['extension'] = [];
		}
	}
}

class NonGeneric
{
	/**
	 * @var array{extension?: array<mixed>}
	 */
	private $data;

	public function setData(): void
	{
		if (!isset($this->data['extension'])) {
			$this->data['extension'] = [];
		}
	}
}
