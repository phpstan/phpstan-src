/*
 * PHPStanTurbo\HasMethodType — native implementation of
 * PHPStan\Type\Accessory\HasMethodType.
 *
 * State is the twin's promoted `private string $methodName`, a declared
 * typed property slot (IS_PROP_UNINIT until the constructor writes it), so
 * the std object handlers do GC/clone.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * isCallable(), getUnresolvedMethodPrototype(), toString()) go through the
 * object's class entry — a subclass may have overridden them — with a
 * direct C++ call when the object is exactly a HasMethodType. The twin's
 * private getCanonicalMethodName() is inlined (strtolower of the slot), and
 * the private slot of another HasMethodType (`$type->methodName`) is read
 * directly, as the twin does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/HasMethodType.h"

namespace slots = ptdecl::HasMethodType::slot;
namespace sigs = ptdecl::HasMethodType::sig;

zend_class_entry *pt_ce_has_method_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\HasMethodType. State lives in the PHP
 * object's $methodName. */
class HasMethodType
{
public:
	explicit HasMethodType(zend_object *self) : self(self) {}

	/* __construct(private string $methodName) */
	void construct(zend_string *methodName)
	{
		zv::ObjRef(self).propAtWrite(slots::methodName, zv::Val::string(methodName));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::methodName)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* $this->methodName (borrowed); NULL with an Error pending when the
	 * constructor never ran — the twin's typed-property read raises the
	 * same */
	[[nodiscard]] zend_string *methodName() const { return methodNameOf(self); }

	static zend_string *methodNameOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::methodName);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_STRING)) {
			zend_throw_error(NULL, "Typed property %s::$methodName must not be accessed before initialization", ZSTR_VAL(pt_ce_has_method_type->name));
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

	/* the CompoundType callback; else $type->hasMethod($this->methodName)
	 * as a fresh result; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}
		zend_long has = otherHasMethod(type);
		if (UNEXPECTED(has < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(has);
	}

	/* the union/intersection callback; yes for a callable other type when
	 * $this is callable and the other is no Closure; else the other type's
	 * hasMethod() verdict, and'ed with maybe unless it is a HasMethodType;
	 * UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(otherType), PT_LC("issupertypeof"), 1, &selfZv);
		}
		/* $this->isCallable()->yes() && $otherType->isCallable()->yes()
		 * && !(new ObjectType(Closure::class))->isSuperTypeOf($otherType)->yes()
		 * — short-circuiting like the twin */
		zend_long callable = isExact() ? isCallable() : pt_type_call_trinary(self, PT_LC("iscallable"), 0, NULL);
		if (UNEXPECTED(callable < 0)) return zv::Val();
		if (callable == PT_TRI_YES) {
			zend_long otherCallable = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("iscallable"), 0, NULL);
			if (UNEXPECTED(otherCallable < 0)) return zv::Val();
			if (otherCallable == PT_TRI_YES) {
				zv::Val className = zv::Val::string(PT_LC("Closure"));
				zv::Val closure = pt_type_new_object_type(className.raw());
				if (UNEXPECTED(closure.isUndef())) return zv::Val();
				zv::Val isClosure = pt_type_call(Z_OBJ_P(closure.raw()), PT_LC("issupertypeof"), 1, otherType);
				if (UNEXPECTED(isClosure.isUndef())) return zv::Val();
				zend_long isClosureValue = pt_type_result_trinary(isClosure.raw());
				if (UNEXPECTED(isClosureValue < 0)) return zv::Val();
				if (isClosureValue != PT_TRI_YES) return pt_type_is_super_type_of_result(PT_TRI_YES);
			}
		}
		zend_long limit = instanceof_function(Z_OBJCE_P(otherType), pt_ce_has_method_type) ? PT_TRI_YES : PT_TRI_MAYBE;
		zend_long has = otherHasMethod(otherType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(pt_trinary_and(limit, has));
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_call(self, PT_LC("issubtypeof"), 1, acceptingType));
	}

	/* $type instanceof self && the canonical (lowercased) names are equal;
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_has_method_type)) {
			out = false;
			return true;
		}
		zv::Str canonical = canonicalMethodName();
		if (UNEXPECTED(canonical.isNull())) return false;
		zend_string *otherName = methodNameOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherName == NULL)) return false;
		zv::Str otherCanonical = zv::Str::adopt(zend_string_tolower(otherName));
		out = zend_string_equals(canonical.get(), otherCanonical.get());
		return true;
	}

	/* sprintf('hasMethod(%s)', $this->methodName); UNDEF = pending exception */
	zv::Val describe() const
	{
		zend_string *name = methodName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "hasMethod(%s)", ZSTR_VAL(name)));
	}

	/* yes for the canonical name, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long hasMethod(zend_string *methodNameArg) const
	{
		zv::Str canonical = canonicalMethodName();
		if (UNEXPECTED(canonical.isNull())) return -1;
		zv::Str argCanonical = zv::Str::adopt(zend_string_tolower(methodNameArg));
		return zend_string_equals(canonical.get(), argCanonical.get()) ? PT_TRI_YES : PT_TRI_MAYBE;
	}

	/* $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod()
	 * — through the object's class */
	zv::Val getMethod(zval *methodNameArg, zval *scope) const
	{
		return pt_type_transformed_member(self, PT_LC("getunresolvedmethodprototype"), true, methodNameArg, scope);
	}

	/* new CallbackUnresolvedMethodPrototypeReflection(new DummyMethodReflection($this->methodName), ...)
	 * — over the accessory's own method name, whatever was asked for */
	zv::Val getUnresolvedMethodPrototype() const
	{
		zend_string *name = methodName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval nameZv;
		ZVAL_STR(&nameZv, name);
		return pt_type_dummy_unresolved_prototype(true, &nameZv);
	}

	/* yes for __invoke, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		zv::Str canonical = canonicalMethodName();
		if (UNEXPECTED(canonical.isNull())) return -1;
		return zend_string_equals_literal(canonical.get(), "__invoke") ? PT_TRI_YES : PT_TRI_MAYBE;
	}

	/* string for __toString, an ErrorType otherwise; UNDEF = pending
	 * exception */
	zv::Val toString() const
	{
		zv::Str canonical = canonicalMethodName();
		if (UNEXPECTED(canonical.isNull())) return zv::Val();
		if (zend_string_equals_literal(canonical.get(), "__tostring")) return pt_type_new_string_type();
		return pt_type_new_error_type();
	}

	/* $this under strict types, TypeCombinator::union($this, $this->toString())
	 * otherwise; UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (strictTypes) return thisValue();
		zv::Val string = isExact() ? toString() : pt_type_call(self, PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Args args{self, string.raw()};
		return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
	}

	/* new IdentifierTypeNode('') — no PHPDoc representation */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node("", 0); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_has_method_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* the twin's private getCanonicalMethodName(): strtolower($this->methodName);
	 * an owned string, NULL = pending exception */
	zv::Str canonicalMethodName() const
	{
		zend_string *name = methodName();
		if (UNEXPECTED(name == NULL)) return zv::Str();
		return zv::Str::adopt(zend_string_tolower(name));
	}

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) return equals(type, out);
		return pt_type_call_bool(self, PT_LC("equals"), 1, type, out);
	}

	/* $type->hasMethod($this->methodName); -1 = pending exception */
	[[nodiscard]] zend_long otherHasMethod(zval *type) const
	{
		zend_string *name = methodName();
		if (UNEXPECTED(name == NULL)) return -1;
		zval nameZv;
		ZVAL_STR(&nameZv, name);
		return pt_type_call_trinary(Z_OBJ_P(type), PT_LC("hasmethod"), 1, &nameZv);
	}

	/* TrinaryLogic::and(): the minimum */

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out) { return pt_type_is_union_or_intersection(type, out); }
};

} // namespace phpstanturbo

using phpstanturbo::HasMethodType;

bool pt_has_method_type_new(zval *out, zend_string *methodName)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_has_method_type) != SUCCESS)) return false;
	HasMethodType(Z_OBJ_P(out)).construct(methodName);
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS HasMethodType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL hmtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL hmtNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL hmtThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hmtThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL hmtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hmtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL hmtMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

/* (Type $type) → a Type */
static void pt_hmt_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (HasMethodType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

/* () → a Type */
static void pt_hmt_no_args(INTERNAL_FUNCTION_PARAMETERS, zv::Val (HasMethodType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

void pt_register_has_method_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\HasMethodType");
	ptdecl::HasMethodType::declareClass(cls);
	/* "methodName" must stay the first declared property (slots::methodName) */
	ptdecl::HasMethodType::declareProperties(cls);

	cls.method<&HasMethodType::construct, zp::Str>(sigs::__construct);

	cls.method(sigs::getReferencedClasses, hmtEmptyArray0);
	cls.method(sigs::getObjectClassNames, hmtEmptyArray0);
	cls.method(sigs::getObjectClassReflections, hmtEmptyArray0);

	cls.method(sigs::getClassStringType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hmt_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasMethodType::getClassStringType);
	});

	cls.method<&HasMethodType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hmt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasMethodType::isSuperTypeOf);
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hmt_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasMethodType::isSubTypeOf);
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&HasMethodType::equals, zp::Obj>(sigs::equals);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.describe());
	});

	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *methodName;
		if (!zp::parse<zp::Str>(execute_data, methodName)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasMethod(methodName));
	});

	cls.method<&HasMethodType::getMethod, zp::Zval, zp::Obj>(sigs::getMethod);

	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.getUnresolvedMethodPrototype());
	});

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isCallable());
	});

	cls.method(sigs::isNull, hmtNo0);
	cls.method(sigs::isConstantValue, hmtNo0);
	cls.method(sigs::isConstantScalarValue, hmtNo0);
	cls.method(sigs::getConstantScalarTypes, hmtEmptyArray0);
	cls.method(sigs::getConstantScalarValues, hmtEmptyArray0);
	cls.method(sigs::isTrue, hmtNo0);
	cls.method(sigs::isFalse, hmtNo0);
	cls.method(sigs::isBoolean, hmtNo0);
	cls.method(sigs::isFloat, hmtNo0);
	cls.method(sigs::isInteger, hmtNo0);
	cls.method(sigs::getClassStringObjectType, hmtThis0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, hmtThis0);
	cls.method(sigs::isVoid, hmtNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toNumber, hmtError0);
	cls.method(sigs::toBitwiseNotType, hmtError0);
	cls.method(sigs::toAbsoluteNumber, hmtError0);

	cls.method(sigs::toString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_hmt_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &HasMethodType::toString);
	});

	cls.method(sigs::toInteger, hmtError0);
	cls.method(sigs::toFloat, hmtError0);
	cls.method(sigs::toArray, hmtMixed0);
	cls.method(sigs::toArrayKey, hmtError0);

	cls.method<&HasMethodType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::getEnumCases, hmtEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, hmtThis2);
	cls.method(sigs::exponentiate, hmtError1);
	cls.method(sigs::getFiniteTypes, hmtEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_object_without_class_type());
	});

	cls.method<&HasMethodType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::HasMethodType::registerTraits(cls);

	cls.shadow(&pt_ce_has_method_type);
}

/* }}} */
