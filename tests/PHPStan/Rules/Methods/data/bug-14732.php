<?php

namespace Bug14732;

class MondayMorning
{
	/** @var array<mixed> */
	public $defaultModal = [MondayMorning::class, 'class' => ['mini']];

	/** @param self $seed */
	public function add($seed): self
	{
		return $seed;
	}

	public function startDay(string $title): self
	{
		$seed = array_merge($this->defaultModal, ['title' => $title]);

		return $this->add($seed);
	}
}
