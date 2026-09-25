/*
 * PHPStanTurbo\VariableAccessFlow — native implementation of
 * PHPStan\Analyser\VariableAccessFlow.
 *
 * A final subclass of the native VariableFlow holding one variable access.
 * State lives in the twin's promoted readonly slots: the inherited $kind
 * first, then its own in declaration order. The VariableFlow factories
 * construct it with pt_variable_access_flow_new() and the liveness resolver
 * and the flow builder read the slots in place (ptdecl::VariableAccessFlow).
 */

#include "support.h"
#include "generated/VariableFlow.h"
#include "generated/VariableAccessFlow.h"

namespace slots = ptdecl::VariableAccessFlow::slot;
namespace sigs = ptdecl::VariableAccessFlow::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_variable_access_flow = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableAccessFlow. */
class VariableAccessFlow
{
public:
	explicit VariableAccessFlow(zend_object *self) : self(self) {}

	/* __construct(string $kind, public readonly string $name, public readonly
	 * ?VariableWrite $write = null, public readonly ?Type $type = null,
	 * public readonly ?int $targetId = null, public readonly bool $container
	 * = false, public readonly mixed $offset = null): the promoted
	 * assignments in declaration order, then parent::__construct($kind).
	 * NULL for null; false = pending exception (a repeated construction
	 * modifies a readonly property) */
	[[nodiscard]] bool construct(zend_string *kind, zend_string *name, zval *write, zval *type, zval *targetId, bool container, zval *offset) const
	{
		zval value;
		ZVAL_STR(&value, name);
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::name, &value, self->ce, "name"))) return false;
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::write, orNull(write), self->ce, "write"))) return false;
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::type, orNull(type), self->ce, "type"))) return false;
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::targetId, orNull(targetId), self->ce, "targetId"))) return false;
		ZVAL_BOOL(&value, container);
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::container, &value, self->ce, "container"))) return false;
		if (UNEXPECTED(!pt_variable_flow_init_readonly(self, slots::offset, orNull(offset), self->ce, "offset"))) return false;
		ZVAL_STR(&value, kind);
		return pt_variable_flow_init_readonly(self, ptdecl::VariableFlow::slot::kind, &value, pt_ce_variable_flow, "kind");
	}

	/* new self(...) with every slot written in place; UNDEF = pending
	 * exception */
	static zv::Val create(zend_string *kind, zval *name, zval *write, zval *type, zval *targetId, bool container, zval *offset)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_variable_access_flow) != SUCCESS)) return zv::Val();
		zend_object *obj = Z_OBJ(object);
		zval value;
		ZVAL_STR(&value, kind);
		pt_write_slot(obj, ptdecl::VariableFlow::slot::kind, &value);
		pt_write_slot(obj, slots::name, name);
		pt_write_slot(obj, slots::write, orNull(write));
		pt_write_slot(obj, slots::type, orNull(type));
		pt_write_slot(obj, slots::targetId, orNull(targetId));
		ZVAL_BOOL(&value, container);
		pt_write_slot(obj, slots::container, &value);
		pt_write_slot(obj, slots::offset, orNull(offset));
		return zv::Val::adopt(object);
	}

private:
	zend_object *self;

	static zval *orNull(zval *value)
	{
		return value != NULL ? value : &EG(uninitialized_zval);
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableAccessFlow;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_variable_access_flow_new(zend_string *kind, zval *name, zval *write, zval *type, zval *targetId, bool container, zval *offset)
{
	return VariableAccessFlow::create(kind, name, write, type, targetId, container, offset);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_variable_access_flow)
{
	reg::Class cls("PHPStan\\Analyser\\VariableAccessFlow");
	ptdecl::VariableAccessFlow::declareClass(cls);
	ptdecl::VariableAccessFlow::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *kind, *name;
		zval *write = NULL, *type = NULL, *offset = NULL;
		zend_long targetId = 0;
		bool targetIdIsNull = true;
		bool container = false;
		ZEND_PARSE_PARAMETERS_START(2, 7)
			Z_PARAM_STR(kind)
			Z_PARAM_STR(name)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(write)
			Z_PARAM_OBJECT_OR_NULL(type)
			Z_PARAM_LONG_OR_NULL(targetId, targetIdIsNull)
			Z_PARAM_BOOL(container)
			Z_PARAM_ZVAL(offset)
		ZEND_PARSE_PARAMETERS_END();
		zval targetIdValue;
		ZVAL_LONG(&targetIdValue, targetId);
		if (UNEXPECTED(!VariableAccessFlow(Z_OBJ_P(ZEND_THIS)).construct(kind, name, write, type, targetIdIsNull ? NULL : &targetIdValue, container, offset))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_variable_access_flow);
}

/* }}} */
