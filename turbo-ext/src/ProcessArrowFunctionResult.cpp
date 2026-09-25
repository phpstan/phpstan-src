/*
 * PHPStanTurbo\ProcessArrowFunctionResult — native implementation of
 * PHPStan\Analyser\ProcessArrowFunctionResult.
 *
 * What ClosureProcessor::processArrowFunctionNode() hands the arrow function
 * handler and the argument walk: a final value class over the twin's five
 * promoted slots, in its order. Native callers create it with
 * pt_process_arrow_function_result_new() and read the slots through the
 * inline readers of AnalyserValues.h.
 */

#include "support.h"
#include "generated/ProcessArrowFunctionResult.h"

namespace slots = ptdecl::ProcessArrowFunctionResult::slot;
namespace sigs = ptdecl::ProcessArrowFunctionResult::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_process_arrow_function_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ProcessArrowFunctionResult. */
class ProcessArrowFunctionResult
{
public:
	explicit ProcessArrowFunctionResult(zend_object *self) : self(self) {}

	/* __construct(...) — the slots in the twin's order */
	void construct(zval *expressionResult, zval *arrowFunctionScope, zval *closureTypeThrowPoints, zval *closureTypeImpurePoints, zval *invalidateExpressions) const
	{
		pt_write_slot(self, slots::expressionResult, expressionResult);
		pt_write_slot(self, slots::arrowFunctionScope, arrowFunctionScope);
		pt_write_slot(self, slots::closureTypeThrowPoints, closureTypeThrowPoints);
		pt_write_slot(self, slots::closureTypeImpurePoints, closureTypeImpurePoints);
		pt_write_slot(self, slots::invalidateExpressions, invalidateExpressions);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *expressionResult, zval *arrowFunctionScope, zval *closureTypeThrowPoints, zval *closureTypeImpurePoints, zval *invalidateExpressions)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_process_arrow_function_result) != SUCCESS)) return zv::Val();
		ProcessArrowFunctionResult(Z_OBJ(object)).construct(expressionResult, arrowFunctionScope, closureTypeThrowPoints, closureTypeImpurePoints, invalidateExpressions);
		return zv::Val::adopt(object);
	}

	zv::Val getExpressionResult() const { return read(slots::expressionResult, "expressionResult"); }
	zv::Val getArrowFunctionScope() const { return read(slots::arrowFunctionScope, "arrowFunctionScope"); }
	zv::Val getClosureTypeThrowPoints() const { return read(slots::closureTypeThrowPoints, "closureTypeThrowPoints"); }
	zv::Val getClosureTypeImpurePoints() const { return read(slots::closureTypeImpurePoints, "closureTypeImpurePoints"); }
	zv::Val getInvalidateExpressions() const { return read(slots::invalidateExpressions, "invalidateExpressions"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, pt_ce_process_arrow_function_result, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ProcessArrowFunctionResult;

zv::Val pt_process_arrow_function_result_new(zval *expressionResult, zval *arrowFunctionScope, zval *closureTypeThrowPoints, zval *closureTypeImpurePoints, zval *invalidateExpressions)
{
	return ProcessArrowFunctionResult::create(expressionResult, arrowFunctionScope, closureTypeThrowPoints, closureTypeImpurePoints, invalidateExpressions);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_process_arrow_function_result)
{
	reg::Class cls("PHPStan\\Analyser\\ProcessArrowFunctionResult");
	ptdecl::ProcessArrowFunctionResult::declareClass(cls);
	ptdecl::ProcessArrowFunctionResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResult, *arrowFunctionScope, *closureTypeThrowPoints, *closureTypeImpurePoints, *invalidateExpressions;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(expressionResult)
			Z_PARAM_OBJECT(arrowFunctionScope)
			Z_PARAM_ARRAY(closureTypeThrowPoints)
			Z_PARAM_ARRAY(closureTypeImpurePoints)
			Z_PARAM_ARRAY(invalidateExpressions)
		ZEND_PARSE_PARAMETERS_END();
		ProcessArrowFunctionResult(Z_OBJ_P(ZEND_THIS)).construct(expressionResult, arrowFunctionScope, closureTypeThrowPoints, closureTypeImpurePoints, invalidateExpressions);
	});

	cls.method<&ProcessArrowFunctionResult::getExpressionResult>(sigs::getExpressionResult);
	cls.method<&ProcessArrowFunctionResult::getArrowFunctionScope>(sigs::getArrowFunctionScope);
	cls.method<&ProcessArrowFunctionResult::getClosureTypeThrowPoints>(sigs::getClosureTypeThrowPoints);
	cls.method<&ProcessArrowFunctionResult::getClosureTypeImpurePoints>(sigs::getClosureTypeImpurePoints);
	cls.method<&ProcessArrowFunctionResult::getInvalidateExpressions>(sigs::getInvalidateExpressions);

	cls.shadow(&pt_ce_process_arrow_function_result);
}

/* }}} */
