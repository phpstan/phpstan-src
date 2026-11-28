<?php declare(strict_types = 1);

namespace Bug15860;

use function PHPStan\Testing\assertType;

/** @param list<array{k: string, p: string, cat: string, c: string}> $rows */
function f(array $rows): void
{
	$dates = [];
	foreach ($rows as $r) {
		$k = $r['k'];
		$p = $r['p'];
		$dates[$k] ??= [];
		if (!isset($dates[$k][$p])) {
			$dates[$k][$p] = [];
			$dates[$k][$p]['l1'] = [];
			$dates[$k][$p]['l2'] = [];
			$dates[$k][$p]['l3'] = [];
			$dates[$k][$p]['l4'] = [];
			$dates[$k][$p]['l5'] = [];
			$dates[$k][$p]['l6'] = [];
			$dates[$k][$p]['other'] = [];
		}
		if ($r['cat'] === 'l1') {
			$dates[$k][$p]['l1'][] = $r['c'];
		} elseif ($r['cat'] === 'l2') {
			$dates[$k][$p]['l2'][] = $r['c'];
		} elseif ($r['cat'] === 'l3') {
			$dates[$k][$p]['l3'][] = $r['c'];
		} elseif ($r['cat'] === 'l4') {
			$dates[$k][$p]['l4'][] = $r['c'];
		} elseif ($r['cat'] === 'l5') {
			$dates[$k][$p]['l5'][] = $r['c'];
		} elseif ($r['cat'] === 'l6') {
			$dates[$k][$p]['l6'][] = $r['c'];
		} else {
			$dates[$k][$p]['other'][$r['cat']] ??= [];
			$dates[$k][$p]['other'][$r['cat']][] = $r['c'];
		}
	}
	assertType('array<string, non-empty-array<string, array{l1: list<string>, l2: list<string>, l3: list<string>, l4: list<string>, l5: list<string>, l6: list<string>, other: non-empty-array<string, list<string>>}|array{l1: list<string>, l2: list<string>, l3: list<string>, l4: list<string>, l5: list<string>, l6: non-empty-list<string>, other: array<string, list<string>>}|array{l1: list<string>, l2: list<string>, l3: list<string>, l4: list<string>, l5: non-empty-list<string>, l6: list<string>, other: array<string, list<string>>}|array{l1: list<string>, l2: list<string>, l3: list<string>, l4: non-empty-list<string>, l5: list<string>, l6: list<string>, other: array<string, list<string>>}|array{l1: list<string>, l2: list<string>, l3: non-empty-list<string>, l4: list<string>, l5: list<string>, l6: list<string>, other: array<string, list<string>>}|array{l1: list<string>, l2: non-empty-list<string>, l3: list<string>, l4: list<string>, l5: list<string>, l6: list<string>, other: array<string, list<string>>}|array{l1: non-empty-list<string>, l2: list<string>, l3: list<string>, l4: list<string>, l5: list<string>, l6: list<string>, other: array<string, list<string>>}>>', $dates);
}

/** @param list<array{k: string, p: string, cat: string, c: string}> $rows */
function withoutElse(array $rows): void
{
	$dates = [];
	foreach ($rows as $r) {
		$k = $r['k'];
		$p = $r['p'];
		$dates[$k] ??= [];
		if (!isset($dates[$k][$p])) {
			$dates[$k][$p] = ['l1' => [], 'l2' => [], 'l3' => [], 'l4' => []];
		}
		if ($r['cat'] === 'l1') {
			$dates[$k][$p]['l1'][] = $r['c'];
		} elseif ($r['cat'] === 'l2') {
			$dates[$k][$p]['l2'][] = $r['c'];
		} elseif ($r['cat'] === 'l3') {
			$dates[$k][$p]['l3'][] = $r['c'];
		} elseif ($r['cat'] === 'l4') {
			$dates[$k][$p]['l4'][] = $r['c'];
		}
	}
	assertType('array<string, array<string, array{l1: list<string>, l2: list<string>, l3: list<string>, l4: list<string>}>>', $dates);
}

/** @param list<array{p: string, cat: string, c: string}> $rows */
function oneLevel(array $rows): void
{
	$dates = [];
	foreach ($rows as $r) {
		$p = $r['p'];
		if (!isset($dates[$p])) {
			$dates[$p] = ['l1' => [], 'l2' => [], 'l3' => [], 'l4' => [], 'l5' => [], 'l6' => [], 'l7' => []];
		}
		if ($r['cat'] === 'l1') {
			$dates[$p]['l1'][] = $r['c'];
		} elseif ($r['cat'] === 'l2') {
			$dates[$p]['l2'][] = $r['c'];
		} elseif ($r['cat'] === 'l3') {
			$dates[$p]['l3'][] = $r['c'];
		} elseif ($r['cat'] === 'l4') {
			$dates[$p]['l4'][] = $r['c'];
		} elseif ($r['cat'] === 'l5') {
			$dates[$p]['l5'][] = $r['c'];
		} elseif ($r['cat'] === 'l6') {
			$dates[$p]['l6'][] = $r['c'];
		} elseif ($r['cat'] === 'l7') {
			$dates[$p]['l7'][] = $r['c'];
		}
	}
	assertType('array<string, array{l1: list<string>, l2: list<string>, l3: list<string>, l4: list<string>, l5: list<string>, l6: list<string>, l7: list<string>}>', $dates);
}


final class QueryUtils
{

	/**
	 * @param list<mixed> $binds
	 * @return resource
	 */
	public static function sqlStatementThrowException(string $sql, array $binds = [])
	{
		throw new \Exception();
	}

	/**
	 * @param resource $resultSet
	 * @return array<string, mixed>|false
	 */
	public static function fetchArrayFromResultSet($resultSet)
	{
		throw new \Exception();
	}

}

function escape_table_name(string $table): string
{
	return $table;
}

/** @param array<string, ?string> $raw */
function formatVitals(array $raw): string
{
	return '';
}


/**
 * @return array<string, array<string, array{name: string, date: string, pubpid: string, dob: string, reason: string, vitals: string, exam: list<string>, prescriptions: list<string>, other: array<string, list<string>>, billing: list<string>, calories: list<array{subcategory: string, item: string, content: string, date: string}>}>>
 */
function getFormData(string $start_date, string $end_date, string $lname, string $fname): array
{
 //dates in sql format

        // All 4 parameters have previously been trimmed

    $binds = [];
    $where_clauses = [];
    $getPid = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
    $getEncounter = filter_input(INPUT_GET, 'encounter', FILTER_VALIDATE_INT);
    if ($getPid !== null && $getPid !== false && $getEncounter !== null && $getEncounter !== false) {
        $where_clauses[] = "t2.pid = ?";
        $binds[] = $getPid;
        $where_clauses[] = "t2.encounter = ?";
        $binds[] = $getEncounter;
    } else {
        $where_clauses[] = "DATE(t2.date) >= ?";
        $binds[] = $start_date;
        $where_clauses[] = "DATE(t2.date) <= ?";
        $binds[] = $end_date;
        if ($lname || $fname) {
            $where_clauses[] = "t3.lname LIKE ?";
            $binds[] = '%' . $lname . '%';
            $where_clauses[] = "t3.fname LIKE ?";
            $binds[] = '%' . $fname . '%';
        }
    }

    $dates = [];
    $query1 = QueryUtils::sqlStatementThrowException(
        "SELECT t1.form_id, t1.form_name, t1.pid,
                DATE_FORMAT(t2.date, '%m-%d-%Y') AS date,
                DATE_FORMAT(t2.date, '%Y%m%d') AS datekey,
                t3.lname, t3.fname, t3.pubpid,
                DATE_FORMAT(t3.DOB, '%m-%d-%Y') AS dob,
                t2.encounter AS enc,
                t2.reason
            FROM forms AS t1
            JOIN form_encounter AS t2
                ON (t1.pid = t2.pid AND t1.encounter = t2.encounter)
            JOIN patient_data AS t3
                ON (t1.pid = t3.pid)
            WHERE " . implode(" AND ", $where_clauses) . "
            ORDER BY date, pid",
        $binds
    );
    while ($results1 = QueryUtils::fetchArrayFromResultSet($query1)) {
        /** @var array{form_id: int, form_name: ?string, pid: int, date: string, datekey: string, lname: string, fname: string, pubpid: string, dob: string, enc: int, reason: ?string} $results1 */
        $datekey = $results1['datekey'];
        $pidEnc = $results1['pid'] . '_' . $results1['enc'];
        $dates[$datekey] ??= [];

        if (!isset($dates[$datekey][$pidEnc])) {
            $dates[$datekey][$pidEnc] = [];
            $dates[$datekey][$pidEnc]['name'] = $results1['fname'] . ' ' . $results1['lname'];
            $dates[$datekey][$pidEnc]['date'] = $results1['date'];
            $dates[$datekey][$pidEnc]['pubpid'] = $results1['pubpid'];
            $dates[$datekey][$pidEnc]['dob'] = $results1['dob'];
            $dates[$datekey][$pidEnc]['vitals'] = '';
            $dates[$datekey][$pidEnc]['reason'] = $results1['reason'] ?? '';
            $dates[$datekey][$pidEnc]['exam'] = [];
            $dates[$datekey][$pidEnc]['prescriptions'] = [];
            $dates[$datekey][$pidEnc]['other'] = [];
            $dates[$datekey][$pidEnc]['billing'] = [];
            $dates[$datekey][$pidEnc]['calories'] = [];
        }

        // get ICD10 codes for this encounter
        $query2 = QueryUtils::sqlStatementThrowException(
            "SELECT *
                FROM billing
                WHERE encounter = ?
                    AND pid = ?
                    AND code_type LIKE 'ICD10'
                    AND activity = 1",
            [$results1['enc'], $results1['pid']]
        );
        /** @var array{code: string, code_text: string}|false $results2 */
        while ($results2 = QueryUtils::fetchArrayFromResultSet($query2)) {
            array_push(
                $dates[$datekey][$pidEnc]['billing'],
                $results2['code'] . ' ' . $results2['code_text']
            );
        }

        if (strtolower($results1['form_name'] ?? '') == 'vitals') { // deal with Vitals
            $query2 = QueryUtils::sqlStatementThrowException(
                "SELECT * FROM form_vitals WHERE id = ?",
                [$results1['form_id']]
            );
            /** @var array{height: ?string, weight: ?string, BMI: ?string, temperature: ?string, bps: ?string, bpd: ?string, pulse: ?string, respiration: ?string, oxygen_saturation: ?string}|false $results2 */
            if ($results2 = QueryUtils::fetchArrayFromResultSet($query2)) {
                $dates[$datekey][$pidEnc]['vitals'] = formatVitals($results2);
            }
        }

        if (str_starts_with(strtolower($results1['form_name'] ?? ''), 'camos')) { // deal with camos
            // escape_table_name() on a literal handles case-insensitive table name matching.
            $query2 = QueryUtils::sqlStatementThrowException(
                "SELECT category, subcategory, item, content,
                        DATE_FORMAT(date, '%h:%i %p') AS date
                    FROM " . escape_table_name("form_CAMOS") . "
                    WHERE id = ?",
                [$results1['form_id']]
            );
            /** @var array{category: string, subcategory: string, item: string, content: ?string, date: string}|false $results2 */
            if ($results2 = QueryUtils::fetchArrayFromResultSet($query2)) {
                if ($results2['category'] == 'exam') {
                    array_push($dates[$datekey][$pidEnc]['exam'], ($results2['content'] ?? ''));
                } elseif ($results2['category'] == 'prescriptions') {
                    array_push($dates[$datekey][$pidEnc]['prescriptions'], (string) preg_replace("/\n+/", ' ', ($results2['content'] ?? '')));
                } elseif ($results2['category'] == 'communications') {
                    //do nothing
                } elseif ($results2['category'] == 'calorie intake') {
                    $values = ['subcategory' => $results2['subcategory'],
                        'item' => $results2['item'],
                        'content' => ($results2['content'] ?? ''),
                        'date' => $results2['date']];
                    array_push($dates[$datekey][$pidEnc]['calories'], $values);
                } else {
                    $dates[$datekey][$pidEnc]['other'][$results2['category']] ??= [];

                    array_push(
                        $dates[$datekey][$pidEnc]['other'][$results2['category']],
                        (string) preg_replace(["/\n+/","/patientname/i"], [' ',$results1['fname'] . ' ' . $results1['lname']], ($results2['content'] ?? ''))
                    );
                }
            }
        }
    }

    assertType('array<string, array<lowercase-string&non-falsy-string&uppercase-string, array{name: non-falsy-string, date: string, pubpid: string, dob: string, vitals: string, reason: string, exam: list<string>, prescriptions: list<string>, other: array<string, non-empty-list<string>>, billing: list<non-falsy-string>, calories: list<array{subcategory: string, item: string, content: string, date: string}>}>>', $dates);
    return $dates;
}
