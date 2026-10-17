#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

COLD_CACHE=$(mktemp -d)
WARM_CACHE=$(mktemp -d)
for CACHE in "$COLD_CACHE" "$WARM_CACHE"; do
	printf 'includes:\n\t- %s/phpstan.neon\nparameters:\n\ttmpDir: %s\n' "$PWD" "$CACHE" > "$CACHE/phpstan.neon"
done

../../bin/phpstan analyse --debug --no-progress --error-format=raw -c "$COLD_CACHE/phpstan.neon"
../../bin/phpstan analyse --debug --no-progress --error-format=raw -c "$WARM_CACHE/phpstan.neon" src/usage.php
../../bin/phpstan clear-result-cache -q -c "$WARM_CACHE/phpstan.neon"
../../bin/phpstan analyse --debug --no-progress --error-format=raw -c "$WARM_CACHE/phpstan.neon"
