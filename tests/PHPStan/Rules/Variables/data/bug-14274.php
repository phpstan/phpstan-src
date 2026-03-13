<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14274;

class PreApplyEvent {}

final class ComposerPatchesValidator {
	/**
	 * Validates the status of the patcher plugin.
	 */
	public function validate(mixed $event): void {
		$messages = [];

		[$plugin_installed_in_active, $is_active_root_requirement, $active_configuration_ok] = $this->computePatcherStatus();
		if ($event instanceof PreApplyEvent) {
			[$plugin_installed_in_stage, $is_stage_root_requirement, $stage_configuration_ok] = $this->computePatcherStatus();
			$has_staged_update = TRUE;
		}
		else {
			// No staged update exists.
			$has_staged_update = FALSE;
		}

		if ($has_staged_update && $plugin_installed_in_active !== $plugin_installed_in_stage) {
			$messages[] = 'package-manager-faq-composer-patches-installed-or-removed';
		}

		// If the patcher is not listed in the runtime or dev dependencies, that's
		// an error as well.
		if (($plugin_installed_in_active && !$is_active_root_requirement) || ($has_staged_update && $plugin_installed_in_stage && !$is_stage_root_requirement)) {
			$messages[] = 'It must be a root dependency.';
		}

		// If the plugin is misconfigured in either the active or stage directories,
		// flag an error.
		if (($plugin_installed_in_active && !$active_configuration_ok) || ($has_staged_update && $plugin_installed_in_stage && !$stage_configuration_ok)) {
			$messages[] = 'The composer-exit-on-patch-failure key is not set to true.';
		}
	}

	/**
	 * @return bool[]
	 */
	private function computePatcherStatus(): array {
		return [];
	}
}
