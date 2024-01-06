<?php

namespace Bug10377;

final class WebRequest
{

	use Headers, RequestParameters {
		Headers::addAdditionalProperties as addAdditionalPropertiesHeaders;
		RequestParameters::addAdditionalProperties insteadof Headers;
	}

}

trait Headers
{
	/**
	 * @param array<string, mixed> $additionalProperties
	 */
	public function addAdditionalProperties(array $additionalProperties): void
	{
		\PHPStan\dumpType($additionalProperties);
	}
}

trait RequestParameters
{

	/**
	 * @param array<string, mixed> $additionalProperties
	 */
	public function addAdditionalProperties(array $additionalProperties): void
	{
		\PHPStan\dumpType($additionalProperties);
	}

}
