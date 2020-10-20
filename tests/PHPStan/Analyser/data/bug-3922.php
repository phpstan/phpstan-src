<?php

namespace Bug3922;

use function PHPStan\Analyser\assertType;

interface ViewData
{
}

/**
 * @template TViewData
 */
class ViewModel
{
}

/**
 * @template TViewData of ViewData
 * @template TModel of ViewModel<TViewData>
 */
abstract class BaseRepository
{
	/**
	 * @param TViewData $a
	 * @param TModel $b
	 */
	public function method($a, $b)
	{
		assertType('TViewData of Bug3922\ViewData (class Bug3922\BaseRepository, argument)', $a);
		assertType('TModel of Bug3922\ViewModel<TViewData> (class Bug3922\BaseRepository, argument)', $b);
	}
}

class StudentViewData implements ViewData
{
}

class TeacherViewData implements ViewData
{
}

/**
 * @extends ViewModel<StudentViewData>
 */
class StudentModel extends ViewModel
{
}

/**
 * @extends ViewModel<TeacherViewData>
 */
class TeacherModel extends ViewModel
{
}

/**
 * @extends BaseRepository<StudentViewData, StudentModel>
 */
class StudentRepository extends BaseRepository
{
}

/**
 * @extends BaseRepository<StudentViewData, TeacherModel>
 */
class TeacherRepository extends BaseRepository
{
}
