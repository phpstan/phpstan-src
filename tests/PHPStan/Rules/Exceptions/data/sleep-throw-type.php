<?php

namespace SleepThrowType;

class Foo
{

	/**
	 * @param positive-int $phpDocSeconds
	 */
	public function doSleep(int $i, int $phpDocSeconds): void
	{
		try {
			sleep(0);
		} catch (\ValueError $e) {

		}

		try {
			sleep(4294967);
		} catch (\ValueError $e) {

		}

		try {
			sleep(-1);
		} catch (\ValueError $e) {

		}

		try {
			sleep($i);
		} catch (\ValueError $e) {

		}

		try {
			sleep($phpDocSeconds);
		} catch (\ValueError $e) {

		}

		try {
			sleep(4294968);
		} catch (\ValueError $e) {

		}

		try {
			usleep(0);
		} catch (\ValueError $e) {

		}

		try {
			usleep(4294967295);
		} catch (\ValueError $e) {

		}

		try {
			usleep(-1);
		} catch (\ValueError $e) {

		}

		try {
			usleep($i);
		} catch (\ValueError $e) {

		}

		try {
			usleep(4294967296);
		} catch (\ValueError $e) {

		}
	}

	public function doRanges(int $i): void
	{
		try {
			if ($i >= 0 && $i <= 1000) {
				sleep($i);
				usleep($i);
			}
		} catch (\ValueError $e) {

		}

		try {
			if ($i >= 0) {
				sleep($i);
			}
		} catch (\ValueError $e) {

		}
	}

}
