/*
 * PHPStanTurbo\StatementResult — native implementation of
 * PHPStan\Analyser\StatementResult.
 *
 * The public (@api) statement result InternalStatementResult::toPublic()
 * hands to the node callbacks and rules. State lives in the twin's seven
 * promoted property slots, in its order; the exit-point walks are the ones
 * InternalStatementResult shares (StatementResults.h), over
 * StatementExitPoint.
 */

#include "support.h"
#include "generated/StatementResult.h"

namespace slots = ptdecl::StatementResult::slot;
namespace sigs = ptdecl::StatementResult::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AnalyserValues.h"
#include "StatementResults.h"

zend_class_entry *pt_ce_statement_result = nullptr;

namespace {

const ptsr::ExitPointFlavour statementExitPoints = {
	pt_statement_exit_point_statement,
	pt_statement_exit_point_scope,
	pt_statement_exit_point_new,
};

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StatementResult. */
class StatementResult
{
public:
	explicit StatementResult(zend_object *self) : self(self) {}

	/* __construct(private Scope $scope, private bool $hasYield, private bool
	 * $isAlwaysTerminating, private array $exitPoints, private array
	 * $throwPoints, private array $impurePoints, private array $endStatements
	 * = []); $endStatements NULL for [] */
	void construct(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements) const
	{
		pt_write_slot(self, slots::scope, scope);
		zval value = {};
		ZVAL_BOOL(&value, hasYield);
		pt_write_slot(self, slots::hasYield, &value);
		ZVAL_BOOL(&value, isAlwaysTerminating);
		pt_write_slot(self, slots::isAlwaysTerminating, &value);
		pt_write_slot(self, slots::exitPoints, exitPoints);
		pt_write_slot(self, slots::throwPoints, throwPoints);
		pt_write_slot(self, slots::impurePoints, impurePoints);
		if (endStatements != NULL) {
			pt_write_slot(self, slots::endStatements, endStatements);
		} else {
			ZVAL_EMPTY_ARRAY(&value);
			pt_write_slot(self, slots::endStatements, &value);
		}
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_statement_result) != SUCCESS)) return zv::Val();
		StatementResult(Z_OBJ(object)).construct(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements);
		return zv::Val::adopt(object);
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val hasYield() const { return read(slots::hasYield, "hasYield"); }
	zv::Val isAlwaysTerminating() const { return read(slots::isAlwaysTerminating, "isAlwaysTerminating"); }

	/* Mirrors filterOutLoopExitPoints(). */
	zv::Val filterOutLoopExitPoints() const
	{
		zval *isAlwaysTerminating = pt_typed_slot(self, slots::isAlwaysTerminating, self->ce, "isAlwaysTerminating");
		if (UNEXPECTED(isAlwaysTerminating == NULL)) return zv::Val();
		if (Z_TYPE_P(isAlwaysTerminating) != IS_TRUE) return pt_this_value(self);

		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		int leaves = ptsr::leavesThisLoop(statementExitPoints, Z_ARRVAL_P(exitPoints));
		if (UNEXPECTED(leaves < 0)) return zv::Val();
		if (leaves == 0) return pt_this_value(self);

		/* new self($this->scope, $this->hasYield, false, $this->exitPoints,
		 * $this->throwPoints, $this->impurePoints) */
		zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
		zval *hasYield = scope != NULL ? pt_typed_slot(self, slots::hasYield, self->ce, "hasYield") : NULL;
		zval *throwPoints = hasYield != NULL ? pt_typed_slot(self, slots::throwPoints, self->ce, "throwPoints") : NULL;
		zval *impurePoints = throwPoints != NULL ? pt_typed_slot(self, slots::impurePoints, self->ce, "impurePoints") : NULL;
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		return create(scope, Z_TYPE_P(hasYield) == IS_TRUE, false, exitPoints, throwPoints, impurePoints, NULL);
	}

	zv::Val getExitPoints() const { return read(slots::exitPoints, "exitPoints"); }

	/* Mirrors getExitPointsByType(); $stmtClass a class name, never loaded
	 * here (an undeclared class matches nothing, as `instanceof` does) */
	zv::Val getExitPointsByType(zend_string *stmtClass) const
	{
		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		zend_class_entry *stmtCe = zend_lookup_class_ex(stmtClass, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
		return ptsr::exitPointsByType(statementExitPoints, Z_ARRVAL_P(exitPoints), stmtCe);
	}

	/* Mirrors getExitPointsForOuterLoop(). */
	zv::Val getExitPointsForOuterLoop() const
	{
		zval *exitPoints = pt_typed_slot(self, slots::exitPoints, self->ce, "exitPoints");
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		return ptsr::exitPointsForOuterLoop(statementExitPoints, Z_ARRVAL_P(exitPoints));
	}

	zv::Val getThrowPoints() const { return read(slots::throwPoints, "throwPoints"); }
	zv::Val getImpurePoints() const { return read(slots::impurePoints, "impurePoints"); }
	zv::Val getEndStatements() const { return read(slots::endStatements, "endStatements"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::StatementResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_statement_result_new(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements)
{
	return StatementResult::create(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_statement_result)
{
	reg::Class cls("PHPStan\\Analyser\\StatementResult");
	ptdecl::StatementResult::declareClass(cls);
	ptdecl::StatementResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *exitPoints, *throwPoints, *impurePoints, *endStatements = NULL;
		bool hasYield, isAlwaysTerminating;
		ZEND_PARSE_PARAMETERS_START(6, 7)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_BOOL(hasYield)
			Z_PARAM_BOOL(isAlwaysTerminating)
			Z_PARAM_ARRAY(exitPoints)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(endStatements)
		ZEND_PARSE_PARAMETERS_END();
		StatementResult(Z_OBJ_P(ZEND_THIS)).construct(scope, hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements);
	});

	cls.method<&StatementResult::getScope>(sigs::getScope);

	cls.method<&StatementResult::hasYield>(sigs::hasYield);

	cls.method<&StatementResult::isAlwaysTerminating>(sigs::isAlwaysTerminating);

	cls.method<&StatementResult::filterOutLoopExitPoints>(sigs::filterOutLoopExitPoints);

	cls.method<&StatementResult::getExitPoints>(sigs::getExitPoints);

	cls.method<&StatementResult::getExitPointsByType, zp::Str>(sigs::getExitPointsByType);

	cls.method<&StatementResult::getExitPointsForOuterLoop>(sigs::getExitPointsForOuterLoop);

	cls.method<&StatementResult::getThrowPoints>(sigs::getThrowPoints);

	cls.method<&StatementResult::getImpurePoints>(sigs::getImpurePoints);

	cls.method<&StatementResult::getEndStatements>(sigs::getEndStatements);

	cls.shadow(&pt_ce_statement_result);
}

/* }}} */
