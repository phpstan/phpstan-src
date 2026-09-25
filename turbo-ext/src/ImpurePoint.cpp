/*
 * PHPStanTurbo\ImpurePoint — native implementation of
 * PHPStan\Analyser\ImpurePoint.
 *
 * A final @api value class: the handlers create one per impure operation
 * and the purity rules read it back. State lives in the twin's five
 * promoted property slots, in its order; native creators use
 * pt_impure_point_new() instead of the constructor call.
 */

#include "support.h"
#include "generated/ImpurePoint.h"

namespace slots = ptdecl::ImpurePoint::slot;
namespace sigs = ptdecl::ImpurePoint::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_impure_point = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ImpurePoint. */
class ImpurePoint
{
public:
	explicit ImpurePoint(zend_object *self) : self(self) {}

	/* __construct(private Scope $scope, private Node $node, private string
	 * $identifier, private string $description, private bool $certain) */
	void construct(zval *scope, zval *node, zend_string *identifier, zend_string *description, bool certain) const
	{
		pt_write_slot(self, slots::scope, scope);
		pt_write_slot(self, slots::node, node);
		zval value;
		ZVAL_STR(&value, identifier);
		pt_write_slot(self, slots::identifier, &value);
		ZVAL_STR(&value, description);
		pt_write_slot(self, slots::description, &value);
		ZVAL_BOOL(&value, certain);
		pt_write_slot(self, slots::certain, &value);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *scope, zval *node, zend_string *identifier, zend_string *description, bool certain)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_impure_point) != SUCCESS)) return zv::Val();
		ImpurePoint(Z_OBJ(object)).construct(scope, node, identifier, description, certain);
		return zv::Val::adopt(object);
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val getNode() const { return read(slots::node, "node"); }
	zv::Val getIdentifier() const { return read(slots::identifier, "identifier"); }
	zv::Val getDescription() const { return read(slots::description, "description"); }
	zv::Val isCertain() const { return read(slots::certain, "certain"); }

private:
	zend_object *self;

	/* a copy of the typed slot; UNDEF with the uninitialized-read Error pending */
	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ImpurePoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_impure_point_new(zval *scope, zval *node, zend_string *identifier, zend_string *description, bool certain)
{
	return ImpurePoint::create(scope, node, identifier, description, certain);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_impure_point)
{
	reg::Class cls("PHPStan\\Analyser\\ImpurePoint");
	ptdecl::ImpurePoint::declareClass(cls);
	ptdecl::ImpurePoint::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node;
		zend_string *identifier, *description;
		bool certain;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Str, zp::Str, zp::Bool>(execute_data, scope, node, identifier, description, certain)) RETURN_THROWS();
		ImpurePoint(Z_OBJ_P(ZEND_THIS)).construct(scope, node, identifier, description, certain);
	});

	cls.method<&ImpurePoint::getScope>(sigs::getScope);

	cls.method<&ImpurePoint::getNode>(sigs::getNode);

	cls.method<&ImpurePoint::getIdentifier>(sigs::getIdentifier);

	cls.method<&ImpurePoint::getDescription>(sigs::getDescription);

	cls.method<&ImpurePoint::isCertain>(sigs::isCertain);

	cls.shadow(&pt_ce_impure_point);
}

/* }}} */
