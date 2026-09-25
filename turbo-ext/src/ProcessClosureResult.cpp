/*
 * PHPStanTurbo\ProcessClosureResult — native implementation of
 * PHPStan\Analyser\ProcessClosureResult.
 *
 * What ClosureProcessor::processClosureNode() hands the closure handler and
 * the argument walk: a final value class over the twin's ten promoted slots,
 * in its order. Native callers create it with pt_process_closure_result_new(),
 * read the slots through the inline readers of AnalyserValues.h and apply the
 * by-ref use scope through pt_process_closure_result_apply_by_ref_use_scope().
 */

#include "support.h"
#include "generated/ProcessClosureResult.h"

namespace slots = ptdecl::ProcessClosureResult::slot;
namespace sigs = ptdecl::ProcessClosureResult::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_process_closure_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ProcessClosureResult. */
class ProcessClosureResult
{
public:
	explicit ProcessClosureResult(zend_object *self) : self(self) {}

	/* __construct(...) — the slots in the twin's order ($byRefClosureResultScope
	 * NULL for null, $byRefUses NULL for []) */
	void construct(zval *scope, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *gatheredReturnStatements, zval *gatheredYieldStatements, zval *executionEnds, zval *closureTypeImpurePoints, zval *byRefClosureResultScope, zval *byRefUses) const
	{
		pt_write_slot(self, slots::scope, scope);
		pt_write_slot(self, slots::throwPoints, throwPoints);
		pt_write_slot(self, slots::impurePoints, impurePoints);
		pt_write_slot(self, slots::invalidateExpressions, invalidateExpressions);
		pt_write_slot(self, slots::gatheredReturnStatements, gatheredReturnStatements);
		pt_write_slot(self, slots::gatheredYieldStatements, gatheredYieldStatements);
		pt_write_slot(self, slots::executionEnds, executionEnds);
		pt_write_slot(self, slots::closureTypeImpurePoints, closureTypeImpurePoints);
		zval value = {};
		if (byRefClosureResultScope != NULL) {
			pt_write_slot(self, slots::byRefClosureResultScope, byRefClosureResultScope);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::byRefClosureResultScope, &value);
		}
		if (byRefUses != NULL) {
			pt_write_slot(self, slots::byRefUses, byRefUses);
		} else {
			ZVAL_EMPTY_ARRAY(&value);
			pt_write_slot(self, slots::byRefUses, &value);
		}
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *scope, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *gatheredReturnStatements, zval *gatheredYieldStatements, zval *executionEnds, zval *closureTypeImpurePoints, zval *byRefClosureResultScope, zval *byRefUses)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_process_closure_result) != SUCCESS)) return zv::Val();
		ProcessClosureResult(Z_OBJ(object)).construct(scope, throwPoints, impurePoints, invalidateExpressions, gatheredReturnStatements, gatheredYieldStatements, executionEnds, closureTypeImpurePoints, byRefClosureResultScope, byRefUses);
		return zv::Val::adopt(object);
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }

	/* Mirrors applyByRefUseScope() */
	zv::Val applyByRefUseScope(zval *scope) const
	{
		zval *byRefClosureResultScope = pt_typed_slot(self, slots::byRefClosureResultScope, pt_ce_process_closure_result, "byRefClosureResultScope");
		if (UNEXPECTED(byRefClosureResultScope == NULL)) return zv::Val();
		if (Z_TYPE_P(byRefClosureResultScope) == IS_NULL) return zv::Val::copyOf(zv::Ref(scope));
		zval *byRefUses = pt_typed_slot(self, slots::byRefUses, pt_ce_process_closure_result, "byRefUses");
		if (UNEXPECTED(byRefUses == NULL)) return zv::Val();
		zval null;
		ZVAL_NULL(&null);
		return pt_mutating_scope_process_closure_scope(Z_OBJ_P(scope), byRefClosureResultScope, &null, byRefUses);
	}

	zv::Val getThrowPoints() const { return read(slots::throwPoints, "throwPoints"); }
	zv::Val getImpurePoints() const { return read(slots::impurePoints, "impurePoints"); }
	zv::Val getInvalidateExpressions() const { return read(slots::invalidateExpressions, "invalidateExpressions"); }
	zv::Val getGatheredReturnStatements() const { return read(slots::gatheredReturnStatements, "gatheredReturnStatements"); }
	zv::Val getGatheredYieldStatements() const { return read(slots::gatheredYieldStatements, "gatheredYieldStatements"); }
	zv::Val getExecutionEnds() const { return read(slots::executionEnds, "executionEnds"); }
	zv::Val getClosureTypeImpurePoints() const { return read(slots::closureTypeImpurePoints, "closureTypeImpurePoints"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, pt_ce_process_closure_result, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ProcessClosureResult;

/* {{{ direct entries (support.h) */

zv::Val pt_process_closure_result_new(zval *scope, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *gatheredReturnStatements, zval *gatheredYieldStatements, zval *executionEnds, zval *closureTypeImpurePoints, zval *byRefClosureResultScope, zval *byRefUses)
{
	return ProcessClosureResult::create(scope, throwPoints, impurePoints, invalidateExpressions, gatheredReturnStatements, gatheredYieldStatements, executionEnds, closureTypeImpurePoints, byRefClosureResultScope != NULL && Z_TYPE_P(byRefClosureResultScope) == IS_NULL ? NULL : byRefClosureResultScope, byRefUses);
}

zv::Val pt_process_closure_result_apply_by_ref_use_scope(zval *result, zval *scope)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_process_closure_result && Z_TYPE_P(scope) == IS_OBJECT)) return ProcessClosureResult(Z_OBJ_P(result)).applyByRefUseScope(scope);
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function applyByRefUseScope() on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(result), PT_LC("applybyrefusescope"), 1, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_process_closure_result)
{
	reg::Class cls("PHPStan\\Analyser\\ProcessClosureResult");
	ptdecl::ProcessClosureResult::declareClass(cls);
	ptdecl::ProcessClosureResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *throwPoints, *impurePoints, *invalidateExpressions, *gatheredReturnStatements, *gatheredYieldStatements, *executionEnds, *closureTypeImpurePoints, *byRefClosureResultScope = NULL, *byRefUses = NULL;
		ZEND_PARSE_PARAMETERS_START(8, 10)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_ARRAY(invalidateExpressions)
			Z_PARAM_ARRAY(gatheredReturnStatements)
			Z_PARAM_ARRAY(gatheredYieldStatements)
			Z_PARAM_ARRAY(executionEnds)
			Z_PARAM_ARRAY(closureTypeImpurePoints)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(byRefClosureResultScope)
			Z_PARAM_ARRAY(byRefUses)
		ZEND_PARSE_PARAMETERS_END();
		ProcessClosureResult(Z_OBJ_P(ZEND_THIS)).construct(scope, throwPoints, impurePoints, invalidateExpressions, gatheredReturnStatements, gatheredYieldStatements, executionEnds, closureTypeImpurePoints, byRefClosureResultScope, byRefUses);
	});

	cls.method<&ProcessClosureResult::getScope>(sigs::getScope);

	cls.method(sigs::applyByRefUseScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ProcessClosureResult(Z_OBJ_P(ZEND_THIS)).applyByRefUseScope(scope));
	});

	cls.method<&ProcessClosureResult::getThrowPoints>(sigs::getThrowPoints);
	cls.method<&ProcessClosureResult::getImpurePoints>(sigs::getImpurePoints);
	cls.method<&ProcessClosureResult::getInvalidateExpressions>(sigs::getInvalidateExpressions);
	cls.method<&ProcessClosureResult::getGatheredReturnStatements>(sigs::getGatheredReturnStatements);
	cls.method<&ProcessClosureResult::getGatheredYieldStatements>(sigs::getGatheredYieldStatements);
	cls.method<&ProcessClosureResult::getExecutionEnds>(sigs::getExecutionEnds);
	cls.method<&ProcessClosureResult::getClosureTypeImpurePoints>(sigs::getClosureTypeImpurePoints);

	cls.shadow(&pt_ce_process_closure_result);
}

/* }}} */
