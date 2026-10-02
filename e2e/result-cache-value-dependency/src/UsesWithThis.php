<?php

namespace ResultCacheE2EValueDependency;

function usesWithThis(): void
{
	withThis(function (): void {
		$this->send();
	});
}
