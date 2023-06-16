<?php

namespace ReturnTypes;

class OverriddenTypeInIfCondition
{

	public function getAnotherAnotherStock(): Stock
	{
		$stock = new Stock();
		if ($stock->findStock() === null) {

		}

		return $stock->findStock();
	}

}
