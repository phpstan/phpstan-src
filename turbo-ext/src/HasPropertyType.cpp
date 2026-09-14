/*
 * PHPStanTurbo\HasPropertyType — native implementation of
 * PHPStan\Type\Accessory\HasPropertyType.
 *
 * State is the twin's promoted `private string $propertyName`, a declared
 * typed property slot (IS_PROP_UNINIT until the constructor writes it), so
 * the std object handlers do GC/clone.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * toString()) go through the object's class entry — a subclass may have
 * overridden them — with a direct C++ call when the object is exactly a
 * HasPropertyType. The private slot of another HasPropertyType
 * (`$type->propertyName`) is read directly, as the twin does from inside
 * the class.
 */

#include "TypeTraits.h"
#include "generated/HasPropertyType.h"

namespace slots = ptdecl::HasPropertyType::slot;
namespace sigs = ptdecl::HasPropertyType::sig;

zend_class_entry *pt_ce_has_property_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\HasPropertyType. State lives in the PHP
 * object's $propertyName. */
class HasPropertyType
{
public:
	explicit HasPropertyType(zend_object *self) : self(self) {}

	/* __construct(private string $propertyName) */
	void construct(zend_string *propertyName)
	{
		zv::ObjRef(self).propAtWrite(slots::propertyName, zv::Val::string(propertyName));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::propertyName)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* $this->propertyName (borrowed); NULL with an Error pending when the
	 * constructor never ran — the twin's typed-property read raises the
	 * same */
	[[nodiscard]] zend_string *propertyName() const { return propertyNameOf(self); }

	static zend_string *propertyNameOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::propertyName);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_STRING)) {
			zend_throw_error(NULL, "Typed property %s::$propertyName must not be accessed before initialization", ZSTR_VAL(pt_ce_has_property_type->name));
			return NULL;
		}
		return Z_STR_P(slot);
	}

	/* new GenericClassStringType($this) */
	zv::Val getClassStringType() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_type_new_ce(pt_ce_generic_class_string_type, 1, &selfZv);
	}

	zv::Val getPropertyName() const
	{
		zend_string *name = propertyName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return zv::Val::string(name);
	}

	/* the CompoundType callback; else AcceptsResult::createFromBoolean($this->equals($type));
	 * UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		return pt_type_accepts_result(equal ? PT_TRI_YES : PT_TRI_NO);
	}

	/* the CompoundType callback; else $type->hasInstanceProperty($this->propertyName)
	 * or'ed with $type->hasStaticProperty($this->propertyName) as a fresh
	 * result; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		zend_long has = otherHasProperty(type);
		if (UNEXPECTED(has < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(has);
	}

	/* the union/intersection callback; else the other type's
	 * instance-or-static property verdict, and'ed with maybe unless it is a
	 * HasPropertyType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long limit = instanceof_function(Z_OBJCE_P(otherType), pt_ce_has_property_type) ? PT_TRI_YES : PT_TRI_MAYBE;
		zend_long has = otherHasProperty(otherType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(pt_trinary_and(limit, has));
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType));
	}

	/* $type instanceof self && $this->propertyName === $type->propertyName;
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_has_property_type)) {
			out = false;
			return true;
		}
		zend_string *name = propertyName();
		if (UNEXPECTED(name == NULL)) return false;
		zend_string *otherName = propertyNameOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherName == NULL)) return false;
		out = zend_string_equals(name, otherName);
		return true;
	}

	/* sprintf('hasProperty(%s)', $this->propertyName); UNDEF = pending
	 * exception */
	zv::Val describe() const
	{
		zend_string *name = propertyName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "hasProperty(%s)", ZSTR_VAL(name)));
	}

	/* yes for the accessory's own name, maybe otherwise — hasProperty(),
	 * hasInstanceProperty() and hasStaticProperty() alike; -1 = pending
	 * exception */
	[[nodiscard]] zend_long hasProperty(zend_string *propertyNameArg) const
	{
		zend_string *name = propertyName();
		if (UNEXPECTED(name == NULL)) return -1;
		return zend_string_equals(name, propertyNameArg) ? PT_TRI_YES : PT_TRI_MAYBE;
	}

	zend_long hasInstanceProperty(zend_string *propertyNameArg) const { return hasProperty(propertyNameArg); }

	zend_long hasStaticProperty(zend_string *propertyNameArg) const { return hasProperty(propertyNameArg); }

	/* $this under strict types, TypeCombinator::union($this, $this->toString())
	 * otherwise; UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (strictTypes) return thisValue();
		/* $this->toString() — through the object's class (an ErrorType
		 * unless overridden) */
		zv::Val string = isExact() ? pt_type_new_error_type() : pt_type_call(self, PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Args args{self, string.raw()};
		return pt_type_combinator_call(PT_LC("union"), 2, args);
	}

	/* new IdentifierTypeNode('') — no PHPDoc representation */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node("", 0); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_has_property_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) return equals(type, out);
		return pt_type_op_bool(self, PT_OP_EQUALS, 1, type, out);
	}

	/* $type->hasInstanceProperty($this->propertyName)->or($type->hasStaticProperty($this->propertyName));
	 * -1 = pending exception */
	[[nodiscard]] zend_long otherHasProperty(zval *type) const
	{
		zend_string *name = propertyName();
		if (UNEXPECTED(name == NULL)) return -1;
		zval nameZv;
		ZVAL_STR(&nameZv, name);
		zend_long instance = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasinstanceproperty"), 1, &nameZv);
		if (UNEXPECTED(instance < 0)) return -1;
		zend_long isStatic = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasstaticproperty"), 1, &nameZv);
		if (UNEXPECTED(isStatic < 0)) return -1;
		return pt_trinary_or(instance, isStatic);
	}

	/* TrinaryLogic::and(): the minimum; TrinaryLogic::or(): the maximum */

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out) { return pt_type_is_union_or_intersection(type, out); }
};

} // namespace phpstanturbo

using phpstanturbo::HasPropertyType;

bool pt_has_property_type_new(zval *out, zend_string *propertyName)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_has_property_type) != SUCCESS)) return false;
	HasPropertyType(Z_OBJ_P(out)).construct(propertyName);
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS HasPropertyType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL hptEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL hptNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL hptThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hptThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hptError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hptError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hptMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

/* (Type $type) → a Type */
static void pt_hpt_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (HasPropertyType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

/* (string $propertyName) → a TrinaryLogic */
static void pt_hpt_one_name(INTERNAL_FUNCTION_PARAMETERS, zend_long (HasPropertyType::*method)(zend_string *) const)
{
	zend_string *propertyName;
	if (!zp::parse<zp::Str>(execute_data, propertyName)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(propertyName));
}

void pt_register_has_property_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\HasPropertyType");
	ptdecl::HasPropertyType::declareClass(cls);
	/* "propertyName" must stay the first declared property (slots::propertyName) */
	ptdecl::HasPropertyType::declareProperties(cls);

	cls.method<&HasPropertyType::construct, zp::Str>(sigs::__construct);

	cls.method(sigs::getReferencedClasses, hptEmptyArray0);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, hptEmptyArray0);
	cls.method(sigs::getObjectClassReflections, hptEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });

	cls.method<&HasPropertyType::getClassStringType>(sigs::getClassStringType);

	cls.method<&HasPropertyType::getPropertyName>(sigs::getPropertyName);

	cls.method<&HasPropertyType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hpt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasPropertyType::isSuperTypeOf);
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hpt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasPropertyType::isSubTypeOf);
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&HasPropertyType::equals, zp::Obj>(sigs::equals);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.describe());
	});

	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hpt_one_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasPropertyType::hasProperty);
	});

	cls.method(sigs::hasInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hpt_one_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasPropertyType::hasInstanceProperty);
	});
	cls.op<PT_OP_HAS_INSTANCE_PROPERTY, &HasPropertyType::hasInstanceProperty>();

	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hpt_one_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasPropertyType::hasStaticProperty);
	});

	cls.method(sigs::isNull, hptNo0);
	cls.method(sigs::isConstantValue, hptNo0);
	cls.method(sigs::isConstantScalarValue, hptNo0);
	cls.method(sigs::getConstantScalarTypes, hptEmptyArray0);
	cls.method(sigs::getConstantScalarValues, hptEmptyArray0);
	cls.method(sigs::isTrue, hptNo0);
	cls.method(sigs::isFalse, hptNo0);
	cls.method(sigs::isBoolean, hptNo0);
	cls.method(sigs::isFloat, hptNo0);
	cls.method(sigs::isInteger, hptNo0);
	cls.method(sigs::getClassStringObjectType, hptThis0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, hptThis0);
	cls.method(sigs::isVoid, hptNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toNumber, hptError0);
	cls.method(sigs::toBitwiseNotType, hptError0);
	cls.method(sigs::toAbsoluteNumber, hptError0);
	cls.method(sigs::toString, hptError0);
	cls.method(sigs::toInteger, hptError0);
	cls.method(sigs::toFloat, hptError0);
	cls.method(sigs::toArray, hptMixed0);
	cls.method(sigs::toArrayKey, hptError0);

	cls.method<&HasPropertyType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::getEnumCases, hptEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.op(PT_OP_GET_ENUM_CASE_OBJECT, PT_OP_LAMBDA { return zv::Val::null(); });
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, hptThis2);
	cls.method(sigs::exponentiate, hptError1);
	cls.method(sigs::getFiniteTypes, hptEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_object_without_class_type());
	});

	cls.method<&HasPropertyType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::HasPropertyType::registerTraits(cls);

	cls.shadow(&pt_ce_has_property_type);
}

/* }}} */
