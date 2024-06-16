#!/bin/bash

shopt -s expand_aliases  # enable alias expansion (off by default in noninteractive shells)
alias exit=return        # ...and alias 'exit' to 'return'
source bashunit --version > /dev/null 2>&1;
unalias exit             # disable the alias...

# the next line calls the function passed as the first parameter to the script.
# the remaining script arguments can be passed to this function.

"assert_$1" $2 $3 $4 $5 $6 $7 $8 $9

if [[ "$(state::get_tests_failed)" -gt 0 ]] || [[ "$(state::get_assertions_failed)" -gt 0 ]]; then
    exit 1
fi

exit 0
