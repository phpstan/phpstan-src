<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * A state-dependent augmentation of a SpecifiedTypes, deferred to the
 * application point: MutatingScope::applySpecifiedTypes() evaluates it against
 * the applying scope and unions the produced entries into the applied batch.
 * The composition captures only position-fixed facts (operand-walk reads);
 * everything that must reflect the current state runs in evaluate().
 */
interface DeferredSpecifiedTypesAugment
{

	public function evaluate(MutatingScope $scope): ?SpecifiedTypes;

}
