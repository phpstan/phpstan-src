/*
 * PHPStanTurbo\StringNeverAcceptingObjectWithToStringType — native
 * implementation of PHPStan\Type\StringNeverAcceptingObjectWithToStringType.
 *
 * Declared as PHPStan\Type\StringNeverAcceptingObjectWithToStringType
 * itself at activation: not final, extending the native StringType,
 * without state (the inherited empty constructor is all `new` does). The
 * twin holds parent::isSuperTypeOf() down to maybe for anything but its own
 * class and forces strict types on parent::accepts(); the `parent::` calls
 * go to the native bodies (JustNullableTypeTrait::isSuperTypeOf() bound to
 * StringType, StringType::accepts()) run on the object.
 */

#include "TypeTraits.h"
#include "generated/StringNeverAcceptingObjectWithToStringType.h"

namespace sigs = ptdecl::StringNeverAcceptingObjectWithToStringType::sig;

zend_class_entry *pt_ce_string_never_accepting_object_with_to_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StringNeverAcceptingObjectWithToStringType. */
class StringNeverAcceptingObjectWithToStringType
{
public:
	explicit StringNeverAcceptingObjectWithToStringType(zend_object *self) : self(self) {}

	/* new StringNeverAcceptingObjectWithToStringType(); UNDEF = pending
	 * exception */
	static zv::Val create() { return pt_new_instance(pt_ce_string_never_accepting_object_with_to_string_type); }

	/* the CompoundType callback; parent::isSuperTypeOf($type), and()ed
	 * with maybe unless $type is a StringNeverAcceptingObjectWithToStringType
	 * itself; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval thisValue;
			ZVAL_OBJ(&thisValue, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &thisValue);
		}

		zv::Val result = pt_type_just_nullable_is_super_type_of(self, pt_ce_string_type, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		/* !$type instanceof self */
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_string_never_accepting_object_with_to_string_type)) {
			zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			if (UNEXPECTED(maybe.isUndef())) return zv::Val();
			result = pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, maybe.raw());
		}

		return result;
	}

	/* parent::accepts($type, true) — strict types regardless of the caller's;
	 * UNDEF = pending exception */
	zv::Val accepts(zval *type) const { return pt_string_type_accepts(self, type, true); }

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::StringNeverAcceptingObjectWithToStringType;

bool pt_string_never_accepting_object_with_to_string_type_new(zval *out)
{
	return pt_val_into(StringNeverAcceptingObjectWithToStringType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS StringNeverAcceptingObjectWithToStringType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_string_never_accepting_object_with_to_string_type)
{
	reg::Class cls("PHPStan\\Type\\StringNeverAcceptingObjectWithToStringType");
	ptdecl::StringNeverAcceptingObjectWithToStringType::declareClass(cls);
	ptdecl::StringNeverAcceptingObjectWithToStringType::declareProperties(cls);

	cls.method<&StringNeverAcceptingObjectWithToStringType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.accepts(type));
	});

	cls.shadow(&pt_ce_string_never_accepting_object_with_to_string_type);
}

/* }}} */
