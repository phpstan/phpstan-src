/*
 * PHPStanTurbo\VariableInputFlow — native implementation of
 * PHPStan\Analyser\VariableInputFlow.
 *
 * A final subclass of the native VariableFlow: the enclosing expression
 * consumes a write's inputs. State lives in the twin's promoted readonly
 * slots (the inherited $kind, always VariableFlow::SEQUENCE, first); the
 * VariableFlow::inputs() factory constructs it with
 * pt_variable_input_flow_new() and the liveness resolver reads the slots in
 * place (ptdecl::VariableInputFlow).
 */

#include "support.h"
#include "generated/VariableFlow.h"
#include "generated/VariableInputFlow.h"

namespace slots = ptdecl::VariableInputFlow::slot;
namespace sigs = ptdecl::VariableInputFlow::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_variable_input_flow = nullptr;

namespace {

/* VariableFlow::SEQUENCE, the permanent interned string (module startup) */
zend_string *pt_vif_sequence;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableInputFlow. */
class VariableInputFlow
{
public:
	explicit VariableInputFlow(zend_object *self) : self(self) {}

	/* __construct(public readonly int $writeId, public readonly ?int
	 * $targetId): the promoted assignments, then
	 * parent::__construct(self::SEQUENCE); $targetId NULL for null; false =
	 * pending exception (a repeated construction modifies a readonly
	 * property) */
	[[nodiscard]] bool construct(zend_long writeId, zval *targetId) const
	{
		zval value;
		ZVAL_LONG(&value, writeId);
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::writeId, &value, self->ce, "writeId"))) return false;
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::targetId, targetId != NULL ? targetId : &EG(uninitialized_zval), self->ce, "targetId"))) return false;
		ZVAL_INTERNED_STR(&value, pt_vif_sequence);
		return pt_variable_flow_init_readonly(self, ptdecl::VariableFlow::slot::kind, &value, pt_ce_variable_flow, "kind");
	}

	/* new self($writeId, $targetId); UNDEF = pending exception */
	static zv::Val create(zend_long writeId, zval *targetId)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_variable_input_flow) != SUCCESS)) return zv::Val();
		zend_object *obj = Z_OBJ(object);
		zval value;
		ZVAL_INTERNED_STR(&value, pt_vif_sequence);
		pt_write_slot(obj, ptdecl::VariableFlow::slot::kind, &value);
		ZVAL_LONG(&value, writeId);
		pt_write_slot(obj, slots::writeId, &value);
		pt_write_slot(obj, slots::targetId, targetId != NULL ? targetId : &EG(uninitialized_zval));
		return zv::Val::adopt(object);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::VariableInputFlow;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_variable_input_flow_new(zend_long writeId, zval *targetId)
{
	return VariableInputFlow::create(writeId, targetId);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_variable_input_flow)
{
	pt_vif_sequence = zend_string_init_interned("sequence", sizeof("sequence") - 1, 1);

	reg::Class cls("PHPStan\\Analyser\\VariableInputFlow");
	ptdecl::VariableInputFlow::declareClass(cls);
	ptdecl::VariableInputFlow::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long writeId, targetId = 0;
		bool targetIdIsNull = true;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_LONG(writeId)
			Z_PARAM_LONG_OR_NULL(targetId, targetIdIsNull)
		ZEND_PARSE_PARAMETERS_END();
		zval targetIdValue;
		ZVAL_LONG(&targetIdValue, targetId);
		if (UNEXPECTED(!VariableInputFlow(Z_OBJ_P(ZEND_THIS)).construct(writeId, targetIdIsNull ? NULL : &targetIdValue))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_variable_input_flow);
}

/* }}} */
