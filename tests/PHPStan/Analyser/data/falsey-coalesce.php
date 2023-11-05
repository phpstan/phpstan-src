<?php

namespace FalseyCoalesce;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

interface TipPluginInterface
{

	/**
	 * Used for returning values by key.
	 *
	 * @return string
	 *   Value of the key.
	 * @var string
	 *   Key of the value.
	 *
	 */
	public function get($key);

}

abstract class TipPluginBase implements TipPluginInterface
{

	public function getLocation(): void
	{
		$location = $this->get('position');
		assertVariableCertainty(TrinaryLogic::createYes(), $location);

			$location ?? '';
		assertVariableCertainty(TrinaryLogic::createYes(), $location);
	}

}

