/*
 * PHPStanTurbo\VariableControlFlow — native implementation of
 * PHPStan\Analyser\VariableControlFlow.
 *
 * A final subclass of the native VariableFlow: loops, try/catch, switch,
 * exits, throws, dead code, arrow functions and loop statements. State lives
 * in the twin's promoted readonly slots (the inherited $kind first, then its
 * own in declaration order); the VariableFlow factories construct it with
 * pt_variable_control_flow_new() and the liveness resolver reads the slots
 * in place (ptdecl::VariableControlFlow).
 */

#include "support.h"
#include "generated/VariableFlow.h"
#include "generated/VariableControlFlow.h"

namespace slots = ptdecl::VariableControlFlow::slot;
namespace sigs = ptdecl::VariableControlFlow::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_variable_control_flow = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableControlFlow. */
class VariableControlFlow
{
public:
	explicit VariableControlFlow(zend_object *self) : self(self) {}

	/* __construct(string $kind, public readonly array $children = [], ...,
	 * public readonly array $ownWrites = []): the promoted assignments in
	 * declaration order, then parent::__construct($kind); false = pending
	 * exception (a repeated construction modifies a readonly property) */
	[[nodiscard]] bool construct(zend_string *kind, const pt_variable_control_flow_args &a) const
	{
		zval value;
		if (UNEXPECTED(!init(slots::children, arrayOrEmpty(a.children, value), "children"))) return false;
		if (a.name != NULL) {
			ZVAL_STR(&value, a.name);
		} else {
			ZVAL_NULL(&value);
		}
		if (UNEXPECTED(!init(slots::name, &value, "name"))) return false;
		if (UNEXPECTED(!init(slots::type, orNull(a.type), "type"))) return false;
		ZVAL_LONG(&value, a.level);
		if (UNEXPECTED(!init(slots::level, &value, "level"))) return false;
		ZVAL_BOOL(&value, a.atLeastOnce);
		if (UNEXPECTED(!init(slots::atLeastOnce, &value, "atLeastOnce"))) return false;
		ZVAL_BOOL(&value, a.canExit);
		if (UNEXPECTED(!init(slots::canExit, &value, "canExit"))) return false;
		if (UNEXPECTED(!init(slots::catches, arrayOrEmpty(a.catches, value), "catches"))) return false;
		if (UNEXPECTED(!init(slots::arrow, orNull(a.arrow), "arrow"))) return false;
		if (UNEXPECTED(!init(slots::cases, arrayOrEmpty(a.cases, value), "cases"))) return false;
		ZVAL_BOOL(&value, a.canRepeat);
		if (UNEXPECTED(!init(slots::canRepeat, &value, "canRepeat"))) return false;
		ZVAL_BOOL(&value, a.canContainAnyThrowable);
		if (UNEXPECTED(!init(slots::canContainAnyThrowable, &value, "canContainAnyThrowable"))) return false;
		if (UNEXPECTED(!init(slots::stmt, orNull(a.stmt), "stmt"))) return false;
		if (UNEXPECTED(!init(slots::bindings, arrayOrEmpty(a.bindings, value), "bindings"))) return false;
		if (UNEXPECTED(!init(slots::ownWrites, arrayOrEmpty(a.ownWrites, value), "ownWrites"))) return false;
		ZVAL_STR(&value, kind);
		return pt_variable_flow_init_readonly(self, ptdecl::VariableFlow::slot::kind, &value, pt_ce_variable_flow, "kind");
	}

	/* new self($kind, ...) with every slot written in place; UNDEF = pending
	 * exception */
	static zv::Val create(zend_string *kind, const pt_variable_control_flow_args &a)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_variable_control_flow) != SUCCESS)) return zv::Val();
		zend_object *obj = Z_OBJ(object);
		zval value;
		ZVAL_STR(&value, kind);
		pt_write_slot(obj, ptdecl::VariableFlow::slot::kind, &value);
		pt_write_slot(obj, slots::children, arrayOrEmpty(a.children, value));
		if (a.name != NULL) {
			ZVAL_STR(&value, a.name);
		} else {
			ZVAL_NULL(&value);
		}
		pt_write_slot(obj, slots::name, &value);
		pt_write_slot(obj, slots::type, orNull(a.type));
		ZVAL_LONG(&value, a.level);
		pt_write_slot(obj, slots::level, &value);
		ZVAL_BOOL(&value, a.atLeastOnce);
		pt_write_slot(obj, slots::atLeastOnce, &value);
		ZVAL_BOOL(&value, a.canExit);
		pt_write_slot(obj, slots::canExit, &value);
		pt_write_slot(obj, slots::catches, arrayOrEmpty(a.catches, value));
		pt_write_slot(obj, slots::arrow, orNull(a.arrow));
		pt_write_slot(obj, slots::cases, arrayOrEmpty(a.cases, value));
		ZVAL_BOOL(&value, a.canRepeat);
		pt_write_slot(obj, slots::canRepeat, &value);
		ZVAL_BOOL(&value, a.canContainAnyThrowable);
		pt_write_slot(obj, slots::canContainAnyThrowable, &value);
		pt_write_slot(obj, slots::stmt, orNull(a.stmt));
		pt_write_slot(obj, slots::bindings, arrayOrEmpty(a.bindings, value));
		pt_write_slot(obj, slots::ownWrites, arrayOrEmpty(a.ownWrites, value));
		return zv::Val::adopt(object);
	}

private:
	zend_object *self;

	[[nodiscard]] bool init(uint32_t index, zval *value, const char *name) const
	{
		return pt_variable_flow_init_readonly(self, index, value, self->ce, name);
	}

	static zval *orNull(zval *value)
	{
		return value != NULL ? value : &EG(uninitialized_zval);
	}

	/* the array argument, [] (in scratch) for NULL */
	static zval *arrayOrEmpty(zval *value, zval &scratch)
	{
		if (value != NULL) return value;
		ZVAL_EMPTY_ARRAY(&scratch);
		return &scratch;
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableControlFlow;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_variable_control_flow_new(zend_string *kind, const pt_variable_control_flow_args &args)
{
	return VariableControlFlow::create(kind, args);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_variable_control_flow()
{
	reg::Class cls("PHPStan\\Analyser\\VariableControlFlow");
	ptdecl::VariableControlFlow::declareClass(cls);
	ptdecl::VariableControlFlow::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind;
		pt_variable_control_flow_args a;
		ZEND_PARSE_PARAMETERS_START(1, 15)
			Z_PARAM_STR(kind)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(a.children)
			Z_PARAM_STR_OR_NULL(a.name)
			Z_PARAM_OBJECT_OR_NULL(a.type)
			Z_PARAM_LONG(a.level)
			Z_PARAM_BOOL(a.atLeastOnce)
			Z_PARAM_BOOL(a.canExit)
			Z_PARAM_ARRAY(a.catches)
			Z_PARAM_OBJECT_OR_NULL(a.arrow)
			Z_PARAM_ARRAY(a.cases)
			Z_PARAM_BOOL(a.canRepeat)
			Z_PARAM_BOOL(a.canContainAnyThrowable)
			Z_PARAM_OBJECT_OR_NULL(a.stmt)
			Z_PARAM_ARRAY(a.bindings)
			Z_PARAM_ARRAY(a.ownWrites)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!VariableControlFlow(Z_OBJ_P(ZEND_THIS)).construct(kind, a))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_variable_control_flow);
}

/* }}} */
