/*
 * PHPStanTurbo\VariableSequenceFlow — native implementation of
 * PHPStan\Analyser\VariableSequenceFlow.
 *
 * A final subclass of the native VariableFlow: a sequence or a choice of
 * child flows. State lives in the twin's promoted readonly slots (the
 * inherited $kind first, then $children); the VariableFlow factories
 * construct it with pt_variable_sequence_flow_new() and the liveness
 * resolver and the flow builder read the slots in place
 * (ptdecl::VariableSequenceFlow).
 */

#include "support.h"
#include "generated/VariableFlow.h"
#include "generated/VariableSequenceFlow.h"

namespace slots = ptdecl::VariableSequenceFlow::slot;
namespace sigs = ptdecl::VariableSequenceFlow::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_variable_sequence_flow = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableSequenceFlow. */
class VariableSequenceFlow
{
public:
	explicit VariableSequenceFlow(zend_object *self) : self(self) {}

	/* __construct(string $kind, public readonly array $children): the
	 * promoted assignment, then parent::__construct($kind); false = pending
	 * exception (a repeated construction modifies a readonly property) */
	[[nodiscard]] bool construct(zend_string *kind, zval *children) const
	{
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::children, children, self->ce, "children"))) return false;
		zval value;
		ZVAL_STR(&value, kind);
		return pt_variable_flow_init_readonly(self, ptdecl::VariableFlow::slot::kind, &value, pt_ce_variable_flow, "kind");
	}

	/* new self($kind, $children); UNDEF = pending exception */
	static zv::Val create(zend_string *kind, zval *children)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_variable_sequence_flow) != SUCCESS)) return zv::Val();
		zval value;
		ZVAL_STR(&value, kind);
		pt_write_slot(Z_OBJ(object), ptdecl::VariableFlow::slot::kind, &value);
		pt_write_slot(Z_OBJ(object), slots::children, children);
		return zv::Val::adopt(object);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::VariableSequenceFlow;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_variable_sequence_flow_new(zend_string *kind, zval *children)
{
	return VariableSequenceFlow::create(kind, children);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_variable_sequence_flow()
{
	reg::Class cls("PHPStan\\Analyser\\VariableSequenceFlow");
	ptdecl::VariableSequenceFlow::declareClass(cls);
	ptdecl::VariableSequenceFlow::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		zval *children;
		if (!zp::parse<zp::Str, zp::Arr>(execute_data, kind, children)) RETURN_THROWS();
		if (UNEXPECTED(!VariableSequenceFlow(Z_OBJ_P(ZEND_THIS)).construct(kind, children))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_variable_sequence_flow);
}

/* }}} */
