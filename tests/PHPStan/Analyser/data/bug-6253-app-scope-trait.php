<?php

namespace Bug6253;

trait AppScopeTrait
{
	/**
	 * @return int
	 */
	public function getApp()
	{
		return 1;
	}

	/**
	 * @return self
	 */
	public function setApp()
	{
		return new self();
	}
}
