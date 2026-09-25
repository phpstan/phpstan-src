/*
 * PHPStanTurbo\StatementListWalkState — native implementation of
 * PHPStan\Analyser\StatementListWalkState.
 *
 * The state StatementsHandler threads through a statement list: seven
 * public properties in the twin's declaration slots (generated
 * declarations), cloned with the standard object handlers like the twin.
 * The native StatementsHandler creates it and builds its result through
 * pt_statement_list_walk_state_new() / _to_result() (support.h) and reads
 * and writes the generated slots directly; toResult() builds the native
 * InternalStatementResult through its entry.
 */

#include "support.h"
#include "generated/StatementListWalkState.h"

namespace slots = ptdecl::StatementListWalkState::slot;
namespace sigs = ptdecl::StatementListWalkState::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_statement_list_walk_state = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StatementListWalkState; UNDEF = pending
 * exception. */
class StatementListWalkState
{
public:
	explicit StatementListWalkState(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *scope)
	{
		zv::ObjRef(self).propAtWrite(slots::scope, zv::Val::copyOf(zv::Ref(scope)));
	}

	/* Mirrors toResult(). */
	zv::Val toResult() const
	{
		zval *scope = slot(slots::scope);
		if (UNEXPECTED(Z_TYPE_P(scope) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\StatementListWalkState::$scope must not be accessed before initialization");
			return zv::Val();
		}
		zval *variableFlows = slot(slots::variableFlows);
		zv::Val variableFlow = Z_TYPE_P(variableFlows) == IS_ARRAY ? pt_variable_flow_sequence_list(Z_ARRVAL_P(variableFlows)) : pt_variable_flow_sequence(0, NULL);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		return pt_internal_statement_result_new(scope, Z_TYPE_P(slot(slots::hasYield)) == IS_TRUE, Z_TYPE_P(slot(slots::alreadyTerminated)) == IS_TRUE, slot(slots::exitPoints), slot(slots::throwPoints), slot(slots::impurePoints), NULL, variableFlow.raw());
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		ZVAL_DEREF(value);
		return value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::StatementListWalkState;

/* {{{ direct entries for the native StatementsHandler (support.h) */

zv::Val pt_statement_list_walk_state_new(zval *scope)
{
	zval state;
	if (UNEXPECTED(object_init_ex(&state, pt_ce_statement_list_walk_state) != SUCCESS)) return zv::Val();
	StatementListWalkState(Z_OBJ(state)).construct(scope);
	return zv::Val::adopt(state);
}

zv::Val pt_statement_list_walk_state_to_result(zval *state)
{
	if (EXPECTED(Z_OBJCE_P(state) == pt_ce_statement_list_walk_state)) return StatementListWalkState(Z_OBJ_P(state)).toResult();
	return pt_type_call(Z_OBJ_P(state), PT_LC("toresult"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_statement_list_walk_state)
{
	reg::Class cls("PHPStan\\Analyser\\StatementListWalkState");
	ptdecl::StatementListWalkState::declareClass(cls);
	ptdecl::StatementListWalkState::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		StatementListWalkState(Z_OBJ_P(ZEND_THIS)).construct(scope);
	});

	cls.method(sigs::toResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StatementListWalkState(Z_OBJ_P(ZEND_THIS)).toResult());
	});

	cls.shadow(&pt_ce_statement_list_walk_state);
}

/* }}} */
