<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Broker\Broker;

/**
 * @api
 * @deprecated Inject PHPStan\Reflection\ReflectionProvider into the constructor instead
 */
interface BrokerAwareExtension
{

	public function setBroker(Broker $broker): void;

}
