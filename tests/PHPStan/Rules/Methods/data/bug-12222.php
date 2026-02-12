<?php // lint >= 8.1

namespace Bug12222;

enum ContractStatus: string
{
	case ACTIVE = 'A';
	case BEING_TERMINATED = 'B';
	case TERMINATED = 'C';

	public function isActive(): bool
	{
		return $this === self::ACTIVE;
	}

	public function isBeingTerminated(): bool
	{
		return $this === self::BEING_TERMINATED;
	}

	public function isTerminated(): bool
	{
		return $this === self::TERMINATED;
	}
}

/**
 * @phpstan-type Contract array{
 *     reference: string,
 *     status: null|ContractStatus,
 *     startDate: string,
 *     isActive: bool,
 *     isBeingTerminated: bool,
 *     isTerminated: bool
 * }
 */
class DataProcessor
{
	/**
	 * @param mixed[] $data
	 * @return Contract
	 */
	public function process(array $data): array
	{
		/** @var Contract $contract */
		$contract = [
			'reference' => $data['reference'],
			'status' => '' !== $data['status'] ? ContractStatus::from($data['status']) : null,
			'startDate' => $data['startDate'],
		];

		$contract['isActive'] = $contract['status']?->isActive();
		$contract['isBeingTerminated'] = $contract['status']?->isBeingTerminated();
		$contract['isTerminated'] = $contract['status']?->isTerminated();

		return $contract;
	}
}
