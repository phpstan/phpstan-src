.PHONY: tests

tests:
	vendor/bin/paratest --no-coverage

tests-integration:
	vendor/bin/paratest --no-coverage --group exec

tests-static-reflection:
	vendor/bin/paratest --no-coverage --bootstrap tests/bootstrap-static-reflection.php

tests-coverage:
	vendor/bin/paratest

tests-integration-coverage:
	vendor/bin/paratest --group exec

tests-static-reflection-coverage:
	vendor/bin/paratest --bootstrap tests/bootstrap-static-reflection.php
