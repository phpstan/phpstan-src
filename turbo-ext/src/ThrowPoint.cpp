/*
 * PHPStanTurbo\ThrowPoint — native implementation of
 * PHPStan\Analyser\ThrowPoint.
 *
 * The public (@api) face of a throw point: InternalThrowPoint::toPublic()
 * creates one per throw point a StatementResult hands to the rules. The
 * twin's constructor is private; instances come from createExplicit() /
 * createImplicit() / subtractCatchType(), natively through
 * pt_throw_point_create_explicit() / pt_throw_point_create_implicit().
 * State lives in the twin's five promoted property slots, in its order.
 */

#include "support.h"
#include "generated/ThrowPoint.h"

namespace slots = ptdecl::ThrowPoint::slot;
namespace sigs = ptdecl::ThrowPoint::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_throw_point = nullptr;

namespace {

/* Throwable::class, a permanent interned string */
zend_string *pt_tp_throwable = nullptr;

} // namespace

/* new ObjectType(Throwable::class) — shared with InternalThrowPoint.cpp;
 * UNDEF = pending exception */
zv::Val pt_throw_point_throwable_type()
{
	zval type;
	if (UNEXPECTED(!pt_object_type_new(&type, pt_tp_throwable))) return zv::Val();
	return zv::Val::adopt(type);
}

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ThrowPoint. */
class ThrowPoint
{
public:
	explicit ThrowPoint(zend_object *self) : self(self) {}

	/* the private constructor's body */
	void construct(zval *scope, zval *type, zval *node, bool explicit_, bool canContainAnyThrowable) const
	{
		pt_write_slot(self, slots::scope, scope);
		pt_write_slot(self, slots::type, type);
		pt_write_slot(self, slots::node, node);
		zval value = {};
		ZVAL_BOOL(&value, explicit_);
		pt_write_slot(self, slots::explicit_, &value);
		ZVAL_BOOL(&value, canContainAnyThrowable);
		pt_write_slot(self, slots::canContainAnyThrowable, &value);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val newSelf(zval *scope, zval *type, zval *node, bool explicit_, bool canContainAnyThrowable)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_throw_point) != SUCCESS)) return zv::Val();
		ThrowPoint(Z_OBJ(object)).construct(scope, type, node, explicit_, canContainAnyThrowable);
		return zv::Val::adopt(object);
	}

	static zv::Val createExplicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
	{
		return newSelf(scope, type, node, true, canContainAnyThrowable);
	}

	/* $type NULL for null */
	static zv::Val createImplicit(zval *scope, zval *node, zval *type)
	{
		if (type != NULL) return newSelf(scope, type, node, false, true);
		zv::Val throwable = pt_throw_point_throwable_type();
		if (UNEXPECTED(throwable.isUndef())) return zv::Val();
		return newSelf(scope, throwable.raw(), node, false, true);
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val getType() const { return read(slots::type, "type"); }
	zv::Val getNode() const { return read(slots::node, "node"); }
	zv::Val isExplicit() const { return read(slots::explicit_, "explicit"); }
	zv::Val canContainAnyThrowable() const { return read(slots::canContainAnyThrowable, "canContainAnyThrowable"); }

	zv::Val subtractCatchType(zval *catchType) const
	{
		zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
		zval *type = scope != NULL ? pt_typed_slot(self, slots::type, self->ce, "type") : NULL;
		if (UNEXPECTED(type == NULL)) return zv::Val();
		zv::Val removed = pt_type_combinator_remove(type, catchType);
		if (UNEXPECTED(removed.isUndef())) return zv::Val();
		zval *node = pt_typed_slot(self, slots::node, self->ce, "node");
		zval *explicit_ = node != NULL ? pt_typed_slot(self, slots::explicit_, self->ce, "explicit") : NULL;
		zval *canContainAnyThrowable = explicit_ != NULL ? pt_typed_slot(self, slots::canContainAnyThrowable, self->ce, "canContainAnyThrowable") : NULL;
		if (UNEXPECTED(canContainAnyThrowable == NULL)) return zv::Val();
		return newSelf(scope, removed.raw(), node, Z_TYPE_P(explicit_) == IS_TRUE, Z_TYPE_P(canContainAnyThrowable) == IS_TRUE);
	}

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ThrowPoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_throw_point_create_explicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
{
	return ThrowPoint::createExplicit(scope, type, node, canContainAnyThrowable);
}

zv::Val pt_throw_point_create_implicit(zval *scope, zval *node, zval *type)
{
	return ThrowPoint::createImplicit(scope, node, type != NULL && Z_TYPE_P(type) == IS_NULL ? NULL : type);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_throw_point()
{
	pt_tp_throwable = zend_string_init_interned(PT_LC("Throwable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ThrowPoint");
	ptdecl::ThrowPoint::declareClass(cls);
	ptdecl::ThrowPoint::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *type, *node;
		bool explicit_, canContainAnyThrowable;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Bool, zp::Bool>(execute_data, scope, type, node, explicit_, canContainAnyThrowable)) RETURN_THROWS();
		ThrowPoint(Z_OBJ_P(ZEND_THIS)).construct(scope, type, node, explicit_, canContainAnyThrowable);
	});

	cls.method<&ThrowPoint::createExplicit, zp::Obj, zp::Obj, zp::Obj, zp::Bool>(sigs::createExplicit);

	cls.method(sigs::createImplicit, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *type = NULL;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, scope, node, type)) RETURN_THROWS();
		PT_RETURN_VAL(ThrowPoint::createImplicit(scope, node, type));
	});

	cls.method<&ThrowPoint::getScope>(sigs::getScope);

	cls.method<&ThrowPoint::getType>(sigs::getType);

	cls.method<&ThrowPoint::getNode>(sigs::getNode);

	cls.method<&ThrowPoint::isExplicit>(sigs::isExplicit);

	cls.method<&ThrowPoint::canContainAnyThrowable>(sigs::canContainAnyThrowable);

	cls.method<&ThrowPoint::subtractCatchType, zp::Obj>(sigs::subtractCatchType);

	cls.shadow(&pt_ce_throw_point);
}

/* }}} */
