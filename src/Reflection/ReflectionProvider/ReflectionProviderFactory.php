<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Broker\Broker;
use PHPStan\Reflection\ReflectionProvider;

class ReflectionProviderFactory
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $phpParserReflectionProvider;

	/** @var bool */
	private $enableStaticReflectionForPhpParser;

	/** @var string[] */
	private $universalObjectCratesClasses;

	/**
	 * @param \PHPStan\Broker\Broker $broker
	 * @param \PHPStan\Reflection\ReflectionProvider $phpParserReflectionProvider
	 * @param bool $enableStaticReflectionForPhpParser
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		Broker $broker,
		ReflectionProvider $phpParserReflectionProvider,
		bool $enableStaticReflectionForPhpParser,
		array $universalObjectCratesClasses
	)
	{
		$this->broker = $broker;
		$this->phpParserReflectionProvider = $phpParserReflectionProvider;
		$this->enableStaticReflectionForPhpParser = $enableStaticReflectionForPhpParser;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public function create(): ReflectionProvider
	{
		$providers = [];

		if ($this->enableStaticReflectionForPhpParser) {
			$providers[] = $this->phpParserReflectionProvider;
		}

		$providers[] = $this->broker;

		if (count($providers) === 1) {
			return $providers[0];
		}

		return new ChainReflectionProvider($providers, $this->universalObjectCratesClasses);
	}

}
