/*
 * PHPStanTurbo\AssignTargetWalkMode — native implementation of
 * PHPStan\Analyser\AssignTargetWalkMode.
 *
 * How AssignHandler::prepareTarget() walks an assignment target. The twin's
 * constructor is private; each factory creates a fresh instance, as the
 * twin's `new self(...)` does. State lives in the three promoted property
 * slots, in the twin's order.
 */

#include "support.h"
#include "generated/AssignTargetWalkMode.h"

namespace slots = ptdecl::AssignTargetWalkMode::slot;
namespace sigs = ptdecl::AssignTargetWalkMode::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_assign_target_walk_mode = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\AssignTargetWalkMode. */
class AssignTargetWalkMode
{
public:
	explicit AssignTargetWalkMode(zend_object *self) : self(self) {}

	/* the private constructor's body */
	void construct(bool enterExpressionAssign, bool producesTargetReadResult, bool issetSemanticsForRead) const
	{
		zval value = {};
		ZVAL_BOOL(&value, enterExpressionAssign);
		pt_write_slot(self, slots::enterExpressionAssign, &value);
		ZVAL_BOOL(&value, producesTargetReadResult);
		pt_write_slot(self, slots::producesTargetReadResult, &value);
		ZVAL_BOOL(&value, issetSemanticsForRead);
		pt_write_slot(self, slots::issetSemanticsForRead, &value);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(bool enterExpressionAssign, bool producesTargetReadResult, bool issetSemanticsForRead)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_assign_target_walk_mode) != SUCCESS)) return zv::Val();
		AssignTargetWalkMode(Z_OBJ(object)).construct(enterExpressionAssign, producesTargetReadResult, issetSemanticsForRead);
		return zv::Val::adopt(object);
	}

	static zv::Val assign() { return create(true, false, false); }
	static zv::Val virtualAssign() { return create(false, false, false); }
	static zv::Val readModifyWrite() { return create(false, true, false); }
	static zv::Val coalesceReadModifyWrite() { return create(true, true, true); }

	zv::Val enterExpressionAssign() const { return read(slots::enterExpressionAssign, "enterExpressionAssign"); }
	zv::Val producesTargetReadResult() const { return read(slots::producesTargetReadResult, "producesTargetReadResult"); }
	zv::Val issetSemanticsForRead() const { return read(slots::issetSemanticsForRead, "issetSemanticsForRead"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::AssignTargetWalkMode;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_assign_target_walk_mode_new(bool enterExpressionAssign, bool producesTargetReadResult, bool issetSemanticsForRead)
{
	return AssignTargetWalkMode::create(enterExpressionAssign, producesTargetReadResult, issetSemanticsForRead);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_assign_target_walk_mode()
{
	reg::Class cls("PHPStan\\Analyser\\AssignTargetWalkMode");
	ptdecl::AssignTargetWalkMode::declareClass(cls);
	ptdecl::AssignTargetWalkMode::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool enterExpressionAssign, producesTargetReadResult, issetSemanticsForRead;
		if (!zp::parse<zp::Bool, zp::Bool, zp::Bool>(execute_data, enterExpressionAssign, producesTargetReadResult, issetSemanticsForRead)) RETURN_THROWS();
		AssignTargetWalkMode(Z_OBJ_P(ZEND_THIS)).construct(enterExpressionAssign, producesTargetReadResult, issetSemanticsForRead);
	});

	cls.method<&AssignTargetWalkMode::assign>(sigs::assign);

	cls.method<&AssignTargetWalkMode::virtualAssign>(sigs::virtualAssign);

	cls.method<&AssignTargetWalkMode::readModifyWrite>(sigs::readModifyWrite);

	cls.method<&AssignTargetWalkMode::coalesceReadModifyWrite>(sigs::coalesceReadModifyWrite);

	cls.method<&AssignTargetWalkMode::enterExpressionAssign>(sigs::enterExpressionAssign);

	cls.method<&AssignTargetWalkMode::producesTargetReadResult>(sigs::producesTargetReadResult);

	cls.method<&AssignTargetWalkMode::issetSemanticsForRead>(sigs::issetSemanticsForRead);

	cls.shadow(&pt_ce_assign_target_walk_mode);
}

/* }}} */
