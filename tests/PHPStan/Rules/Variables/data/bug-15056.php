<?php declare(strict_types = 1);

namespace Bug15056;

class Service
{
    private string $priceInfo;

    public function __construct(?string $priceInfo = null)
    {
        $this->initDependencies($priceInfo);
    }

    public function getPriceInfo(): string
    {
        $this->initDependencies($this->priceInfo ?? null);

        return $this->priceInfo;
    }

    protected function initDependencies(?string $priceInfo): void
    {
        $this->priceInfo = $priceInfo ?? 'foobar'; // in reality: fetch from contiainer
    }

	public function __sleep(): array
    {
		return []; // priceInfo will be lost on serialization
	}
}
