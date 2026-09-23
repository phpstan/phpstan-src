/*
 * PHPStanTurbo\InternalThrowPoint — native implementation of
 * PHPStan\Analyser\InternalThrowPoint.
 *
 * The engine-side throw point every handler creates (~110K per
 * self-analysis) and VariableFlowBuilder reads back. The twin's constructor
 * is private; instances come from createExplicit() / createImplicit() /
 * createFromPublic() / subtractCatchType(). State lives in the twin's five
 * promoted property slots, in its order. toPublic() builds the native
 * ThrowPoint directly (ThrowPoint.cpp); a ThrowPoint handed to
 * createFromPublic() is read through its slots when it is the native class,
 * through its getters otherwise.
 */

#include "support.h"
#include "generated/InternalThrowPoint.h"
#include "generated/ThrowPoint.h"

namespace slots = ptdecl::InternalThrowPoint::slot;
namespace publicSlots = ptdecl::ThrowPoint::slot;
namespace sigs = ptdecl::InternalThrowPoint::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_internal_throw_point = nullptr;

/* ThrowPoint.cpp */
zv::Val pt_throw_point_throwable_type();

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\InternalThrowPoint. */
class InternalThrowPoint
{
public:
	explicit InternalThrowPoint(zend_object *self) : self(self) {}

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
		if (UNEXPECTED(object_init_ex(&object, pt_ce_internal_throw_point) != SUCCESS)) return zv::Val();
		InternalThrowPoint(Z_OBJ(object)).construct(scope, type, node, explicit_, canContainAnyThrowable);
		return zv::Val::adopt(object);
	}

	/* Mirrors toPublic(). */
	zv::Val toPublic() const
	{
		zval *explicit_ = pt_typed_slot(self, slots::explicit_, self->ce, "explicit");
		if (UNEXPECTED(explicit_ == NULL)) return zv::Val();
		zval *scope = pt_typed_slot(self, slots::scope, self->ce, "scope");
		if (Z_TYPE_P(explicit_) == IS_TRUE) {
			zval *type = scope != NULL ? pt_typed_slot(self, slots::type, self->ce, "type") : NULL;
			zval *node = type != NULL ? pt_typed_slot(self, slots::node, self->ce, "node") : NULL;
			zval *canContainAnyThrowable = node != NULL ? pt_typed_slot(self, slots::canContainAnyThrowable, self->ce, "canContainAnyThrowable") : NULL;
			if (UNEXPECTED(canContainAnyThrowable == NULL)) return zv::Val();
			return pt_throw_point_create_explicit(scope, type, node, Z_TYPE_P(canContainAnyThrowable) == IS_TRUE);
		}

		zval *node = scope != NULL ? pt_typed_slot(self, slots::node, self->ce, "node") : NULL;
		zval *type = node != NULL ? pt_typed_slot(self, slots::type, self->ce, "type") : NULL;
		if (UNEXPECTED(type == NULL)) return zv::Val();
		return pt_throw_point_create_implicit(scope, node, type);
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

	/* Mirrors createFromPublic(): the getters in the twin's order */
	static zv::Val createFromPublic(zval *throwPoint, zval *scope)
	{
		zend_object *point = Z_OBJ_P(throwPoint);
		if (EXPECTED(point->ce == pt_ce_throw_point)) {
			zval *type = pt_typed_slot(point, publicSlots::type, point->ce, "type");
			zval *node = type != NULL ? pt_typed_slot(point, publicSlots::node, point->ce, "node") : NULL;
			zval *explicit_ = node != NULL ? pt_typed_slot(point, publicSlots::explicit_, point->ce, "explicit") : NULL;
			zval *canContainAnyThrowable = explicit_ != NULL ? pt_typed_slot(point, publicSlots::canContainAnyThrowable, point->ce, "canContainAnyThrowable") : NULL;
			if (UNEXPECTED(canContainAnyThrowable == NULL)) return zv::Val();
			return newSelf(scope, type, node, Z_TYPE_P(explicit_) == IS_TRUE, Z_TYPE_P(canContainAnyThrowable) == IS_TRUE);
		}
		zv::Val type = pt_type_call(point, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val node = pt_type_call(point, PT_LC("getnode"), 0, NULL);
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		zv::Val explicit_ = pt_type_call(point, PT_LC("isexplicit"), 0, NULL);
		if (UNEXPECTED(explicit_.isUndef())) return zv::Val();
		zv::Val canContainAnyThrowable = pt_type_call(point, PT_LC("cancontainanythrowable"), 0, NULL);
		if (UNEXPECTED(canContainAnyThrowable.isUndef())) return zv::Val();
		return newSelf(scope, type.raw(), node.raw(), zend_is_true(explicit_.raw()), zend_is_true(canContainAnyThrowable.raw()));
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val getType() const { return read(slots::type, "type"); }
	zv::Val getNode() const { return read(slots::node, "node"); }
	zv::Val isExplicit() const { return read(slots::explicit_, "explicit"); }
	zv::Val canContainAnyThrowable() const { return read(slots::canContainAnyThrowable, "canContainAnyThrowable"); }

	/* Mirrors subtractCatchType(). */
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

using phpstanturbo::InternalThrowPoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_internal_throw_point_create_explicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
{
	return InternalThrowPoint::createExplicit(scope, type, node, canContainAnyThrowable);
}

zv::Val pt_internal_throw_point_create_implicit(zval *scope, zval *node, zval *type)
{
	return InternalThrowPoint::createImplicit(scope, node, type != NULL && Z_TYPE_P(type) == IS_NULL ? NULL : type);
}

/* the twin is final: the native class entry answers natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method; the slot getters are inline in
 * AnalyserValues.h */
zv::Val pt_internal_throw_point_to_public(zval *throwPoint)
{
	if (EXPECTED(Z_OBJCE_P(throwPoint) == pt_ce_internal_throw_point)) return InternalThrowPoint(Z_OBJ_P(throwPoint)).toPublic();
	return pt_type_call(Z_OBJ_P(throwPoint), PT_LC("topublic"), 0, NULL);
}

zv::Val pt_internal_throw_point_subtract_catch_type(zval *throwPoint, zval *catchType)
{
	if (EXPECTED(Z_OBJCE_P(throwPoint) == pt_ce_internal_throw_point)) return InternalThrowPoint(Z_OBJ_P(throwPoint)).subtractCatchType(catchType);
	return pt_type_call(Z_OBJ_P(throwPoint), PT_LC("subtractcatchtype"), 1, catchType);
}

zv::Val pt_internal_throw_point_create_from_public(zval *throwPoint, zval *scope)
{
	return InternalThrowPoint::createFromPublic(throwPoint, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_internal_throw_point()
{
	reg::Class cls("PHPStan\\Analyser\\InternalThrowPoint");
	ptdecl::InternalThrowPoint::declareClass(cls);
	ptdecl::InternalThrowPoint::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *type, *node;
		bool explicit_, canContainAnyThrowable;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Bool, zp::Bool>(execute_data, scope, type, node, explicit_, canContainAnyThrowable)) RETURN_THROWS();
		InternalThrowPoint(Z_OBJ_P(ZEND_THIS)).construct(scope, type, node, explicit_, canContainAnyThrowable);
	});

	cls.method<&InternalThrowPoint::toPublic>(sigs::toPublic);

	cls.method<&InternalThrowPoint::createExplicit, zp::Obj, zp::Obj, zp::Obj, zp::Bool>(sigs::createExplicit);

	cls.method(sigs::createImplicit, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *type = NULL;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, scope, node, type)) RETURN_THROWS();
		PT_RETURN_VAL(InternalThrowPoint::createImplicit(scope, node, type));
	});

	cls.method<&InternalThrowPoint::createFromPublic, zp::Obj, zp::Obj>(sigs::createFromPublic);

	cls.method<&InternalThrowPoint::getScope>(sigs::getScope);

	cls.method<&InternalThrowPoint::getType>(sigs::getType);

	cls.method<&InternalThrowPoint::getNode>(sigs::getNode);

	cls.method<&InternalThrowPoint::isExplicit>(sigs::isExplicit);

	cls.method<&InternalThrowPoint::canContainAnyThrowable>(sigs::canContainAnyThrowable);

	cls.method<&InternalThrowPoint::subtractCatchType, zp::Obj>(sigs::subtractCatchType);

	cls.shadow(&pt_ce_internal_throw_point);
}

/* }}} */
