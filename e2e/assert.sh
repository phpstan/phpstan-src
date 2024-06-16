#!/bin/bash

# Wrapper script around bashunit, to make assertion functions available for cli executable testing.
#
# Usage examples:
#   ./assert.sh contains 'ab' "$VARIABLE"
#   ./assert.sh equals 'ab' "$VARIABLE"
#
# Find all supported bashunit assertions on https://bashunit.typeddevs.com/assertions

__dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"


# import all bashunit functions into the current process
shopt -s expand_aliases  # enable alias expansion (off by default in noninteractive shells)
alias exit=return        # ...and alias 'exit' to 'return'
source ${__dir}/bashunit --version > /dev/null 2>&1;
unalias exit             # disable the alias...


# return non-zero exit code if unknown assertion is called
set -e

# the next line calls the function passed as the first parameter to the script.
# the remaining script arguments can be passed to this function.
"assert_$1" "$2" "$3" "$4" "$5" "$6" "$7" "$8" "$9"

# return non-zero exit code when assertion fails
if [[ "$(state::get_tests_failed)" -gt 0 ]] || [[ "$(state::get_assertions_failed)" -gt 0 ]]; then
    exit 1
fi

exit 0
