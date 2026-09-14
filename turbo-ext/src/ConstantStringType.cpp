/*
 * PHPStanTurbo\ConstantStringType — native implementation of
 * PHPStan\Type\Constant\ConstantStringType.
 *
 * Declared as PHPStan\Type\Constant\ConstantStringType itself at
 * activation: not final (the PHP TemplateConstantStringType extends it),
 * extending the native StringType (declared first — Shadow.cpp
 * materialises a parent plan before its child) and implementing
 * PHPStan\Type\ConstantScalarType. State is the twin's five private
 * properties, declared typed slots in the twin's order: the three memo
 * properties with their defaults and the two promoted constructor
 * parameters (IS_PROP_UNINIT until the constructor writes them), so the
 * std object handlers do GC/clone.
 *
 * The class body's methods live here; the two traits
 * (ConstantScalarTypeTrait, ConstantScalarToBooleanTrait) come from the
 * shared registrars in TypeTraits.cpp, run after the class's own methods
 * so the class body's isSuperTypeOf() wins. Everything else is inherited
 * from StringType.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it — with a direct C++ call when
 * the object is exactly a ConstantStringType; `parent::` calls go to
 * StringType's native bodies directly. String conversions the twin leaves
 * to PHP ((int)/(float)/(bool) casts, the unary plus, `~`, is_numeric(),
 * the array-key coercion of key()) go through the engine's own operators so
 * the outcome is the twin's.
 */

#include "TypeTraits.h"
#include "generated/ConstantStringType.h"

namespace slots = ptdecl::ConstantStringType::slot;
namespace sigs = ptdecl::ConstantStringType::sig;

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
extern "C" {
#include "ext/standard/php_string.h" /* php_addcslashes_str */
}
#pragma GCC diagnostic pop

#include <cstring>

zend_class_entry *pt_ce_constant_string_type = nullptr;

/* the twin's private const DESCRIBE_LIMIT */
#define PT_CST_DESCRIBE_LIMIT 20

/* Class::NAME — a literal class constant, borrowed; NULL = pending
 * exception */
static zval *pt_class_constant(zend_class_entry *ce, const char *name, size_t len)
{
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
	return &constant->value;
}

namespace phpstanturbo {

/* the character classes of the 'MyClass::myStaticFunction' pattern
 * (#^([a-zA-Z_\x7f-\xff\\][a-zA-Z0-9_\x7f-\xff\\]*)::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\z#) */
static bool isIdentifierStart(unsigned char c)
{
	return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || c == '_' || c >= 0x7f;
}

static bool isIdentifierChar(unsigned char c)
{
	return isIdentifierStart(c) || (c >= '0' && c <= '9');
}

/* Strings::match($value, '#^(class)::(method)\z#'): neither class allows
 * a colon, so the greedy groups never backtrack — the match is the
 * maximal class run followed by "::" and a method run reaching the end */
static bool matchStaticCallable(const unsigned char *s, size_t len, size_t &classLen, size_t &methodStart)
{
	if (len == 0 || !(isIdentifierStart(s[0]) || s[0] == '\\')) return false;
	size_t i = 1;
	while (i < len && (isIdentifierChar(s[i]) || s[i] == '\\')) {
		i++;
	}
	if (i + 2 > len || s[i] != ':' || s[i + 1] != ':') return false;
	size_t m = i + 2;
	if (m >= len || !isIdentifierStart(s[m])) return false;
	size_t j = m + 1;
	while (j < len && isIdentifierChar(s[j])) {
		j++;
	}
	if (j != len) return false;
	classLen = i;
	methodStart = m;
	return true;
}

/* Mirrors PHPStan\Type\Constant\ConstantStringType. State lives in the PHP
 * object's slots. */
class ConstantStringType
{
public:
	explicit ConstantStringType(zend_object *self) : self(self) {}

	/* __construct(private string $value, private bool $isClassString = false):
	 * initializes the typed slots; parent::__construct() is StringType's
	 * empty constructor */
	void construct(zend_string *value, bool isClassString)
	{
		zval *valueSlot = OBJ_PROP_NUM(self, slots::value);
		zval *flagSlot = OBJ_PROP_NUM(self, slots::isClassString);
		ZVAL_STR_COPY(valueSlot, value);
		ZVAL_BOOL(flagSlot, isClassString);
		Z_PROP_FLAG_P(valueSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(flagSlot) = 0;
	}

	/* new self($value, $isClassString); UNDEF = pending exception */
	static zv::Val create(zend_string *value, bool isClassString = false)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_string_type) != SUCCESS)) return zv::Val();
		ConstantStringType(Z_OBJ(object)).construct(value, isClassString);
		return zv::Val::adopt(object);
	}

	/* $this->value (borrowed); NULL with an Error pending when the
	 * constructor never ran (ReflectionClass::newInstanceWithoutConstructor())
	 * — the twin's typed-property read raises the same */
	[[nodiscard]] zend_string *value() const { return valueOf(self); }

	/* $object->value — the private slot declared here, read directly on a
	 * subclass too (as `$type->value` does) */
	static zend_string *valueOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_STRING)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_string_type->name));
			return NULL;
		}
		return Z_STR_P(slot);
	}

	/* $this->isClassString; false with an Error pending when uninitialized */
	[[nodiscard]] bool isClassStringFlag(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::isClassString);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$isClassString must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_string_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* an owned copy of $this->value; UNDEF = pending exception */
	zv::Val getValue() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		return zv::Val::string(v);
	}

	/* [$this] */
	zv::Val getConstantStrings() const
	{
		zv::Arr types = zv::Arr::create(1);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		types.push(zv::Ref(&selfZv));
		return zv::Val(std::move(types));
	}

	/* yes when constructed as a class-string, else whether the reflection
	 * provider knows a class of that name; -1 = pending exception */
	[[nodiscard]] zend_long isClassString() const
	{
		bool flag = false;
		if (UNEXPECTED(!isClassStringFlag(flag))) return -1;
		if (flag) return PT_TRI_YES;
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return -1;
		zv::Val reflectionProvider = reflectionProviderInstance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return -1;
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		zv::Val hasClass = pt_type_call(Z_OBJ_P(reflectionProvider.raw()), PT_LC("hasclass"), 1, &valueZv);
		if (UNEXPECTED(hasClass.isUndef())) return -1;
		return zend_is_true(hasClass.raw()) ? PT_TRI_YES : PT_TRI_NO;
	}

	/* new ObjectType($this->value) when $this->isClassString()->yes(), new
	 * ErrorType() otherwise; UNDEF = pending exception */
	zv::Val getClassStringObjectType() const
	{
		zend_long isClass = thisIsClassString();
		if (UNEXPECTED(isClass < 0)) return zv::Val();
		if (isClass == PT_TRI_YES) return newObjectTypeOfValue();
		return pt_type_new_error_type();
	}

	/* $this->getClassStringObjectType() */
	zv::Val getObjectTypeOrClassStringObjectType() const
	{
		if (EXPECTED(isExact())) return getClassStringObjectType();
		return pt_type_call(self, PT_LC("getclassstringobjecttype"), 0, NULL);
	}

	/* $level->handle(): 'string' for the type-only level; the exported
	 * value, truncated to DESCRIBE_LIMIT unless a class-string, for the
	 * value level; the exported full value for the precise and cache
	 * levels — each memoized per level in $cachedDescriptions; UNDEF =
	 * pending exception */
	zv::Val describe(zval *level) const
	{
		/* $level->getLevelValue() — the shadowing VerbosityLevel's slot, or
		 * the PHP twin's method (VerbosityLevel.cpp) */
		zend_long levelValue;
		if (UNEXPECTED(!pt_verbosity_level_value_of(level, levelValue))) return zv::Val();

		zv::ArrRef cached(OBJ_PROP_NUM(self, slots::cachedDescriptions));
		if (UNEXPECTED(!cached.isArray())) {
			zend_throw_error(NULL, "Typed property %s::$cachedDescriptions must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_string_type->name));
			return zv::Val();
		}
		zv::Ref hit = cached.findIndex((zend_ulong) levelValue);
		if (hit.raw() != NULL && !hit.isNull()) return zv::Val::copyOf(hit);

		if (levelValue == PT_VERBOSITY_LEVEL_TYPE_ONLY) return zv::Val::string("string", sizeof("string") - 1);

		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Val exported;
		if (levelValue == PT_VERBOSITY_LEVEL_VALUE) {
			bool flag = false;
			if (UNEXPECTED(!isClassStringFlag(flag))) return zv::Val();
			zv::Str truncated;
			if (!flag) {
				truncated = truncate(v);
				if (UNEXPECTED(truncated.isNull())) return zv::Val();
				v = truncated.get();
			}
			exported = zv::Val::adoptString(exportValue(v));
		} else {
			/* the precise callback, which the cache level falls through to */
			exported = zv::Val::adoptString(exportValue(v));
		}
		cached.setIndex((zend_ulong) levelValue, zv::Ref(exported.raw()));
		return exported;
	}

	/* maybe/no for a GenericClassStringType by whether its generic type (a
	 * StaticType's object type, a TemplateType's bound; mixed is maybe) is
	 * a supertype of this value's ObjectType; maybe/no for a
	 * ClassStringType by whether this is a class-string; yes/no against
	 * another ConstantStringType's value; maybe for any other StringType;
	 * the CompoundType callback; no otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		zend_class_entry *typeCe = Z_OBJCE_P(type);
		if (instanceof_function(typeCe, pt_ce_generic_class_string_type)) {
			zv::Val genericType = pt_type_call(Z_OBJ_P(type), PT_LC("getgenerictype"), 0, NULL);
			if (UNEXPECTED(genericType.isUndef())) return zv::Val();
			if (zv::Ref(genericType.raw()).instanceOf(pt_ce_mixed_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			/* $genericType instanceof StaticType — the shadowing class */
			bool isStatic = zv::Ref(genericType.raw()).instanceOf(pt_ce_static_type);
			if (isStatic) {
				genericType = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("getstaticobjecttype"), 0, NULL);
				if (UNEXPECTED(genericType.isUndef())) return zv::Val();
			}

			/* We are transforming constant class-string to ObjectType. But
			 * we need to filter out an uncertainty originating in possible
			 * ObjectType's class subtypes. */
			zval *objectType = getObjectType();
			if (UNEXPECTED(objectType == NULL)) return zv::Val();

			/* Do not use TemplateType's isSuperTypeOf handling directly
			 * because it takes ObjectType uncertainty into account. */
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(genericType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			zv::Val isSuperType;
			if (isTemplate) {
				zv::Val bound = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("getbound"), 0, NULL);
				if (UNEXPECTED(bound.isUndef())) return zv::Val();
				isSuperType = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("issupertypeof"), 1, objectType);
			} else {
				isSuperType = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("issupertypeof"), 1, objectType);
			}
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			zend_long verdict = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(verdict < 0)) return zv::Val();

			/* Explicitly handle the uncertainty for Yes & Maybe. */
			if (verdict == PT_TRI_YES) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			return pt_type_is_super_type_of_result(PT_TRI_NO);
		}
		if (instanceof_function(typeCe, pt_ce_class_string_type)) {
			zend_long isClass = thisIsClassString();
			if (UNEXPECTED(isClass < 0)) return zv::Val();
			return pt_type_is_super_type_of_result(isClass == PT_TRI_YES ? PT_TRI_MAYBE : PT_TRI_NO);
		}

		/* $type instanceof self: $this->value === $type->value */
		if (instanceof_function(typeCe, pt_ce_constant_string_type)) {
			zend_string *v = value();
			if (UNEXPECTED(v == NULL)) return zv::Val();
			zend_string *typeValue = valueOf(Z_OBJ_P(type));
			if (UNEXPECTED(typeValue == NULL)) return zv::Val();
			return pt_type_is_super_type_of_result(zend_string_equals(v, typeValue) ? PT_TRI_YES : PT_TRI_NO);
		}

		/* $type instanceof parent */
		if (instanceof_function(typeCe, pt_ce_string_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* no for the empty string; yes for a known function name; for
	 * 'MyClass::myStaticFunction' maybe when the class is unknown, yes when
	 * it has the method (no for an instance method before PHP 8.2's
	 * callable instance methods), maybe when the class is not final by
	 * keyword, no otherwise; no for anything else; -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return -1;
		if (ZSTR_LEN(v) == 0) return PT_TRI_NO;

		zv::Val reflectionProvider = reflectionProviderInstance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return -1;
		zend_object *provider = Z_OBJ_P(reflectionProvider.raw());

		/* 'my_function' */
		zv::Val name = newName(v);
		if (UNEXPECTED(name.isUndef())) return -1;
		zv::Args args{name.raw(), zv::null};
		zv::Val hasFunction = pt_type_call(provider, PT_LC("hasfunction"), 2, args);
		if (UNEXPECTED(hasFunction.isUndef())) return -1;
		if (zend_is_true(hasFunction.raw())) return PT_TRI_YES;

		/* 'MyClass::myStaticFunction' */
		size_t classLen, methodStart;
		if (matchStaticCallable((const unsigned char *) ZSTR_VAL(v), ZSTR_LEN(v), classLen, methodStart)) {
			zv::Val className = zv::Val::string(ZSTR_VAL(v), classLen);
			zv::Val methodName = zv::Val::string(ZSTR_VAL(v) + methodStart, ZSTR_LEN(v) - methodStart);
			zv::Val hasClass = pt_type_call(provider, PT_LC("hasclass"), 1, className.raw());
			if (UNEXPECTED(hasClass.isUndef())) return -1;
			if (!zend_is_true(hasClass.raw())) return PT_TRI_MAYBE;

			zv::Val classRef = pt_type_call(provider, PT_LC("getclass"), 1, className.raw());
			if (UNEXPECTED(classRef.isUndef())) return -1;
			zv::Val hasMethod = pt_type_call(Z_OBJ_P(classRef.raw()), PT_LC("hasmethod"), 1, methodName.raw());
			if (UNEXPECTED(hasMethod.isUndef())) return -1;
			if (zend_is_true(hasMethod.raw())) {
				zv::Val phpVersion = pt_type_call_static(PT_CLASS_PHP_VERSION_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
				if (UNEXPECTED(phpVersion.isUndef())) return -1;
				zv::Val supportsInstanceMethods = pt_type_call(Z_OBJ_P(phpVersion.raw()), PT_LC("supportscallableinstancemethods"), 0, NULL);
				if (UNEXPECTED(supportsInstanceMethods.isUndef())) return -1;
				if (!zend_is_true(supportsInstanceMethods.raw())) {
					zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
					if (UNEXPECTED(scope.isUndef())) return -1;
					zv::Args methodArgs{methodName.raw(), scope.raw()};
					zv::Val method = pt_type_call(Z_OBJ_P(classRef.raw()), PT_LC("getmethod"), 2, methodArgs);
					if (UNEXPECTED(method.isUndef())) return -1;
					zv::Val isStatic = pt_type_call(Z_OBJ_P(method.raw()), PT_LC("isstatic"), 0, NULL);
					if (UNEXPECTED(isStatic.isUndef())) return -1;
					if (!zend_is_true(isStatic.raw())) return PT_TRI_NO;
				}

				return PT_TRI_YES;
			}

			zv::Val isFinalByKeyword = pt_type_call(Z_OBJ_P(classRef.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
			if (UNEXPECTED(isFinalByKeyword.isUndef())) return -1;
			if (!zend_is_true(isFinalByKeyword.raw())) return PT_TRI_MAYBE;

			return PT_TRI_NO;
		}

		return PT_TRI_NO;
	}

	/* [] for the empty string; a known function's callable variants; for
	 * 'MyClass::myStaticFunction' a trivial acceptor when the class is
	 * unknown, the method's variants (an InaccessibleMethod when the scope
	 * cannot call it) when it has the method, a trivial acceptor when the
	 * class is not final by keyword; ShouldNotHappenException otherwise;
	 * UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors(zval *scope) const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		if (ZSTR_LEN(v) == 0) return zv::Val(zv::Arr::empty());

		zv::Val reflectionProvider = reflectionProviderInstance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		zend_object *provider = Z_OBJ_P(reflectionProvider.raw());

		/* 'my_function' */
		zv::Val functionName = newName(v);
		if (UNEXPECTED(functionName.isUndef())) return zv::Val();
		zv::Args args{functionName.raw(), zv::null};
		zv::Val hasFunction = pt_type_call(provider, PT_LC("hasfunction"), 2, args);
		if (UNEXPECTED(hasFunction.isUndef())) return zv::Val();
		if (zend_is_true(hasFunction.raw())) {
			zv::Val function = pt_type_call(provider, PT_LC("getfunction"), 2, args);
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			return callableVariants(function.raw());
		}

		/* 'MyClass::myStaticFunction' */
		size_t classLen, methodStart;
		if (matchStaticCallable((const unsigned char *) ZSTR_VAL(v), ZSTR_LEN(v), classLen, methodStart)) {
			zv::Val className = zv::Val::string(ZSTR_VAL(v), classLen);
			zv::Val methodName = zv::Val::string(ZSTR_VAL(v) + methodStart, ZSTR_LEN(v) - methodStart);
			zv::Val hasClass = pt_type_call(provider, PT_LC("hasclass"), 1, className.raw());
			if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
			if (!zend_is_true(hasClass.raw())) return trivialAcceptors();

			zv::Val classReflection = pt_type_call(provider, PT_LC("getclass"), 1, className.raw());
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			zv::Val hasMethod = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("hasmethod"), 1, methodName.raw());
			if (UNEXPECTED(hasMethod.isUndef())) return zv::Val();
			if (zend_is_true(hasMethod.raw())) {
				zv::Args methodArgs{methodName.raw(), scope};
				zv::Val method = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getmethod"), 2, methodArgs);
				if (UNEXPECTED(method.isUndef())) return zv::Val();
				zv::Val canCall = pt_type_call(Z_OBJ_P(scope), PT_LC("cancallmethod"), 1, method.raw());
				if (UNEXPECTED(canCall.isUndef())) return zv::Val();
				if (!zend_is_true(canCall.raw())) {
					/* [new InaccessibleMethod($method)] */
					zv::Val inaccessible = pt_type_new(PT_CLASS_INACCESSIBLE_METHOD, 1, method.raw());
					if (UNEXPECTED(inaccessible.isUndef())) return zv::Val();
					zv::Arr acceptors = zv::Arr::create(1);
					acceptors.push(std::move(inaccessible));
					return zv::Val(std::move(acceptors));
				}

				return callableVariants(method.raw());
			}

			zv::Val isFinalByKeyword = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
			if (UNEXPECTED(isFinalByKeyword.isUndef())) return zv::Val();
			if (!zend_is_true(isFinalByKeyword.raw())) return trivialAcceptors();
		}

		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* the value's number (`+$value`: the engine's numeric-string
	 * conversion, a float on overflow) as a ConstantFloatType or
	 * ConstantIntegerType when is_numeric(), new ErrorType() otherwise;
	 * UNDEF = pending exception */
	zv::Val toNumber() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		if (!is_numeric_string(ZSTR_VAL(v), ZSTR_LEN(v), NULL, NULL, false)) return pt_type_new_error_type();
		/* +$value compiles to `$value * 1` */
		zval valueZv, one, number;
		ZVAL_STR(&valueZv, v);
		ZVAL_LONG(&one, 1);
		if (UNEXPECTED(mul_function(&number, &valueZv, &one) != SUCCESS || EG(exception))) return zv::Val();
		if (Z_TYPE(number) == IS_DOUBLE) return pt_type_new_constant_float(Z_DVAL(number));
		return pt_type_new_constant_integer(Z_LVAL(number));
	}

	/* new ConstantStringType(~$this->value) */
	zv::Val toBitwiseNotType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval valueZv, negated;
		ZVAL_STR(&valueZv, v);
		if (UNEXPECTED(bitwise_not_function(&negated, &valueZv) != SUCCESS || EG(exception))) return zv::Val();
		zv::Val result = create(Z_STR(negated));
		zval_ptr_dtor(&negated);
		return result;
	}

	/* new ClassNameToObjectTypeResult(new ObjectType($this->value), false) */
	zv::Val toObjectTypeForInstanceofCheck() const
	{
		zv::Val objectType = newObjectTypeOfValue();
		if (UNEXPECTED(objectType.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(objectType.raw(), false);
	}

	/* the ObjectType of the value — or, when strings are allowed, its
	 * union with the GenericClassStringType of it — with the uncertainty
	 * the twin derives from the compared type's class names (and never
	 * when the compared type is exactly this one final class); UNDEF =
	 * pending exception */
	zv::Val toObjectTypeForIsACheck(zval *objectOrClassType, bool allowString, bool allowSameClass) const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zv::Val classNamesRaw = pt_type_call(Z_OBJ_P(objectOrClassType), PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(classNamesRaw.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(classNamesRaw.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return array", ZSTR_VAL(Z_OBJCE_P(objectOrClassType)->name));
			return zv::Val();
		}
		zv::Arr classNames = zv::Arr::adoptVal(std::move(classNamesRaw));
		if (allowString) {
			zv::Val constantStrings = pt_type_call(Z_OBJ_P(objectOrClassType), PT_LC("getconstantstrings"), 0, NULL);
			if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(constantStrings.raw()).isArray())) {
				zend_type_error("phpstan_turbo: %s::getConstantStrings() must return array", ZSTR_VAL(Z_OBJCE_P(objectOrClassType)->name));
				return zv::Val();
			}
			for (zv::ArrayEntry entry : zv::ArrRef(constantStrings.raw())) {
				if (UNEXPECTED(!entry.value().isObject())) {
					zend_type_error("phpstan_turbo: %s::getConstantStrings() must return ConstantStringType instances", ZSTR_VAL(Z_OBJCE_P(objectOrClassType)->name));
					return zv::Val();
				}
				zv::Val constantValue = pt_constant_string_get_value(entry.value().asObject());
				if (UNEXPECTED(constantValue.isUndef())) return zv::Val();
				classNames.push(std::move(constantValue));
			}
			/* array_values(array_unique($objectOrClassTypeClassNames)):
			 * the first occurrence of each string, re-indexed */
			zv::Arr unique = zv::Arr::create(classNames.arrRef().size());
			zv::ScratchTable seen(classNames.arrRef().size());
			for (zv::ArrayEntry entry : classNames.arrRef()) {
				zval *item = entry.value().raw();
				zend_string *tmp;
				zend_string *key = zval_get_tmp_string(item, &tmp);
				if (UNEXPECTED(EG(exception))) {
					zend_tmp_string_release(tmp);
					return zv::Val();
				}
				bool first = zend_hash_add_empty_element(seen.table(), key) != NULL;
				zend_tmp_string_release(tmp);
				if (first) {
					unique.push(entry.value());
				}
			}
			classNames = std::move(unique);
		}

		bool uncertainty = false;
		if (!allowSameClass) {
			/* $objectOrClassTypeClassNames === [$this->value] */
			zv::Arr onlyThis = zv::Arr::create(1);
			onlyThis.push(zv::Val::string(v));
			if (zend_is_identical(classNames.raw(), onlyThis.raw())) {
				bool isSameClass = true;
				zv::Val reflections = pt_type_call(Z_OBJ_P(objectOrClassType), PT_LC("getobjectclassreflections"), 0, NULL);
				if (UNEXPECTED(reflections.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(reflections.raw()).isArray())) {
					zend_type_error("phpstan_turbo: %s::getObjectClassReflections() must return array", ZSTR_VAL(Z_OBJCE_P(objectOrClassType)->name));
					return zv::Val();
				}
				for (zv::ArrayEntry entry : zv::ArrRef(reflections.raw())) {
					if (UNEXPECTED(!entry.value().isObject())) {
						zend_type_error("phpstan_turbo: %s::getObjectClassReflections() must return ClassReflection instances", ZSTR_VAL(Z_OBJCE_P(objectOrClassType)->name));
						return zv::Val();
					}
					zv::Val isFinal = pt_type_call(entry.value().asObject(), PT_LC("isfinal"), 0, NULL);
					if (UNEXPECTED(isFinal.isUndef())) return zv::Val();
					if (!zend_is_true(isFinal.raw())) {
						isSameClass = false;
						break;
					}
				}

				if (isSameClass) {
					zv::Val never = pt_type_new_never_type();
					if (UNEXPECTED(never.isUndef())) return zv::Val();
					return classNameToObjectTypeResult(never.raw(), false);
				}
			}

			/* in_array($this->value, $objectOrClassTypeClassNames, true) —
			 * for object, as soon as the exact same type is provided in
			 * the list we cannot be sure of the result */
			bool contained = false;
			for (zv::ArrayEntry entry : classNames.arrRef()) {
				if (entry.value().isString() && zend_string_equals(entry.value().asString(), v)) {
					contained = true;
					break;
				}
			}
			if (contained) {
				uncertainty = true;
			} else if (allowString && classNames.arrRef().size() == 0) {
				/* this also occurs for generic class string:
				 * $objectOrClassType->isSuperTypeOf($this)->yes() */
				zval selfZv;
				ZVAL_OBJ(&selfZv, self);
				zv::Val isSuperType = pt_type_call(Z_OBJ_P(objectOrClassType), PT_LC("issupertypeof"), 1, &selfZv);
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long verdict = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				if (verdict == PT_TRI_YES) {
					uncertainty = true;
				}
			}
		}

		zv::Val objectType = newObjectTypeOfValue();
		if (UNEXPECTED(objectType.isUndef())) return zv::Val();
		if (allowString) {
			/* new UnionType([new ObjectType($this->value), new GenericClassStringType(new ObjectType($this->value))]) */
			zv::Val innerObjectType = newObjectTypeOfValue();
			if (UNEXPECTED(innerObjectType.isUndef())) return zv::Val();
			zval genericRaw;
			if (UNEXPECTED(object_init_ex(&genericRaw, pt_ce_generic_class_string_type) != SUCCESS)) return zv::Val();
			zv::Val generic = zv::Val::adopt(genericRaw);
			zend_call_known_instance_method(pt_ce_generic_class_string_type->constructor, Z_OBJ_P(generic.raw()), NULL, 1, innerObjectType.raw());
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(objectType));
			types.push(std::move(generic));
			zv::Val unionType = pt_type_new_union(std::move(types));
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			return classNameToObjectTypeResult(unionType.raw(), uncertainty);
		}

		return classNameToObjectTypeResult(objectType.raw(), uncertainty);
	}

	/* $this->toNumber()->toAbsoluteNumber() */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(number.raw()), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toInteger() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		return pt_type_new_constant_integer(zval_get_long(&valueZv));
	}

	/* new ConstantFloatType((float) $this->value) */
	zv::Val toFloat() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		return pt_type_new_constant_float(zval_get_double(&valueZv));
	}

	/* $this when the value stays a string key (key([$this->value => null])),
	 * the memoized ConstantIntegerType of its integer key otherwise; UNDEF
	 * = pending exception */
	zv::Val toArrayKey() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::arrayKeyType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));

		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zend_ulong index;
		if (!_zend_handle_numeric_str(ZSTR_VAL(v), ZSTR_LEN(v), &index)) {
			/* $offsetValue === $this->value */
			return thisValue();
		}

		/* is_int($offsetValue) ? new ConstantIntegerType($offsetValue) :
		 * new ConstantStringType($offsetValue) — a numeric key is always
		 * the int */
		zv::Val arrayKeyType = pt_type_new_constant_integer((zend_long) index);
		if (UNEXPECTED(arrayKeyType.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::arrayKeyType, zv::Val::copyOf(zv::Ref(arrayKeyType.raw())));
		return arrayKeyType;
	}

	static zend_long isString() { return PT_TRI_YES; }

	/* is_numeric($this->getValue()); -1 = pending exception */
	[[nodiscard]] zend_long isNumericString() const
	{
		zv::Val v = thisGetValue();
		if (UNEXPECTED(v.isUndef())) return -1;
		zend_string *str = zv::Ref(v.raw()).asString();
		return is_numeric_string(ZSTR_VAL(str), ZSTR_LEN(str), NULL, NULL, false) ? PT_TRI_YES : PT_TRI_NO;
	}

	/* (string) (int) $this->value === $this->value; -1 = pending exception */
	[[nodiscard]] zend_long isDecimalIntegerString() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return -1;
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		zend_string *roundTrip = zend_long_to_str(zval_get_long(&valueZv));
		bool equal = zend_string_equals(roundTrip, v);
		zend_string_release(roundTrip);
		return equal ? PT_TRI_YES : PT_TRI_NO;
	}

	/* $this->getValue() !== ''; -1 = pending exception */
	[[nodiscard]] zend_long isNonEmptyString() const
	{
		zv::Val v = thisGetValue();
		if (UNEXPECTED(v.isUndef())) return -1;
		return ZSTR_LEN(zv::Ref(v.raw()).asString()) != 0 ? PT_TRI_YES : PT_TRI_NO;
	}

	/* !in_array($this->getValue(), ['', '0'], true); -1 = pending exception */
	[[nodiscard]] zend_long isNonFalsyString() const
	{
		zv::Val v = thisGetValue();
		if (UNEXPECTED(v.isUndef())) return -1;
		zend_string *str = zv::Ref(v.raw()).asString();
		bool falsy = ZSTR_LEN(str) == 0 || (ZSTR_LEN(str) == 1 && ZSTR_VAL(str)[0] == '0');
		return falsy ? PT_TRI_NO : PT_TRI_YES;
	}

	static zend_long isLiteralString() { return PT_TRI_YES; }

	/* strtolower($this->value) === $this->value; -1 = pending exception */
	[[nodiscard]] zend_long isLowercaseString() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return -1;
		zend_string *lower = zend_string_tolower(v);
		bool equal = zend_string_equals(lower, v);
		zend_string_release(lower);
		return equal ? PT_TRI_YES : PT_TRI_NO;
	}

	/* strtoupper($this->value) === $this->value; -1 = pending exception */
	[[nodiscard]] zend_long isUppercaseString() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return -1;
		zend_string *upper = zend_string_toupper(v);
		bool equal = zend_string_equals(upper, v);
		zend_string_release(upper);
		return equal ? PT_TRI_YES : PT_TRI_NO;
	}

	/* for an integer offset whether int<-strlen, strlen-1> is a supertype
	 * of it, parent::hasOffsetValueType() (StringType's) otherwise; UNDEF
	 * = pending exception */
	zv::Val hasOffsetValueType(zval *offsetType) const
	{
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES) {
			zv::Val strLenType = offsetRangeType();
			if (UNEXPECTED(strLenType.isUndef())) return zv::Val();
			zv::Val isSuperType = pt_type_call(Z_OBJ_P(strLenType.raw()), PT_LC("issupertypeof"), 1, offsetType);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			/* ->result */
			zend_long verdict = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(verdict < 0)) return zv::Val();
			return pt_type_trinary(verdict);
		}

		return pt_string_type_has_offset_value_type(self, offsetType);
	}

	/* for an integer offset: the character at a constant offset within
	 * int<-strlen, strlen-1> (new ErrorType() outside it), the union of the
	 * characters at the finitely many offsets of the range's intersection
	 * with the offset type (plus '' when the offset may fall outside);
	 * parent::getOffsetValueType() (StringType's) otherwise; UNDEF =
	 * pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long isInteger = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isinteger"), 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES) {
			zend_string *v = value();
			if (UNEXPECTED(v == NULL)) return zv::Val();
			zv::Val strLenType = offsetRangeType();
			if (UNEXPECTED(strLenType.isUndef())) return zv::Val();

			if (instanceof_function(Z_OBJCE_P(offsetType), pt_ce_constant_integer_type)) {
				zend_long verdict = superTypeVerdict(strLenType.raw(), offsetType);
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				if (verdict == PT_TRI_YES) {
					zend_long offset;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetType), offset))) return zv::Val();
					return charAt(v, offset);
				}

				return pt_type_new_error_type();
			}

			zv::Args args{strLenType.raw(), offsetType};
			zv::Val intersected = pt_type_combinator_call(PT_LC("intersect"), 2, args);
			if (UNEXPECTED(intersected.isUndef())) return zv::Val();
			if (zv::Ref(intersected.raw()).instanceOf(pt_ce_integer_range_type)) {
				zv::Val finiteTypes = pt_type_call(Z_OBJ_P(intersected.raw()), PT_LC("getfinitetypes"), 0, NULL);
				if (UNEXPECTED(finiteTypes.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(finiteTypes.raw()).isArray())) {
					zend_type_error("phpstan_turbo: %s::getFiniteTypes() must return array", ZSTR_VAL(Z_OBJCE_P(intersected.raw())->name));
					return zv::Val();
				}
				zv::ArrRef finite(finiteTypes.raw());
				if (finite.size() == 0) return pt_string_type_get_offset_value_type(self, offsetType);

				zv::Arr chars = zv::Arr::create(finite.size() + 1);
				for (zv::ArrayEntry entry : finite) {
					if (UNEXPECTED(!entry.value().isObject())) {
						zend_type_error("phpstan_turbo: %s::getFiniteTypes() must return ConstantIntegerType instances", ZSTR_VAL(Z_OBJCE_P(intersected.raw())->name));
						return zv::Val();
					}
					zend_long offset;
					if (UNEXPECTED(!pt_constant_integer_get_value(entry.value().asObject(), offset))) return zv::Val();
					zv::Val chr = charAt(v, offset);
					if (UNEXPECTED(chr.isUndef())) return zv::Val();
					chars.push(std::move(chr));
				}
				zend_long verdict = superTypeVerdict(strLenType.raw(), offsetType);
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				if (verdict != PT_TRI_YES) {
					zv::Val empty = create(ZSTR_EMPTY_ALLOC());
					if (UNEXPECTED(empty.isUndef())) return zv::Val();
					chars.push(std::move(empty));
				}

				return pt_type_combinator_call_spread(PT_LC("union"), chars.table());
			}
		}

		return pt_string_type_get_offset_value_type(self, offsetType);
	}

	/* new ErrorType() for a value with no string form; for a constant
	 * integer offset and a constant string value, the value written into
	 * the string at that offset (`$value[$offset] = $char`: new ErrorType()
	 * for a negative offset or a value that is not one character, spaces
	 * padding a gap); parent::setOffsetValueType() (StringType's)
	 * otherwise; offsetType NULL = the twin's null; UNDEF = pending
	 * exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Val valueStringType = pt_type_call(Z_OBJ_P(valueType), PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(valueStringType.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof_ce(valueStringType.raw(), pt_ce_error_type, isError))) return zv::Val();
		if (isError) return pt_type_new_error_type();
		if (offsetType != NULL
			&& instanceof_function(Z_OBJCE_P(offsetType), pt_ce_constant_integer_type)
			&& zv::Ref(valueStringType.raw()).instanceOf(pt_ce_constant_string_type)
		) {
			zend_string *v = value();
			if (UNEXPECTED(v == NULL)) return zv::Val();
			zend_long offsetValue;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(offsetType), offsetValue))) return zv::Val();
			if (offsetValue < 0) return pt_type_new_error_type();
			zv::Val stringValue = pt_constant_string_get_value(Z_OBJ_P(valueStringType.raw()));
			if (UNEXPECTED(stringValue.isUndef())) return zv::Val();
			zend_string *chr = zv::Ref(stringValue.raw()).asString();
			if (ZSTR_LEN(chr) != 1) return pt_type_new_error_type();
			/* $value[$offsetValue] = $stringValue — the engine pads a gap
			 * with spaces */
			size_t len = ZSTR_LEN(v);
			size_t newLen = (size_t) offsetValue < len ? len : (size_t) offsetValue + 1;
			zend_string *written = zend_string_alloc(newLen, 0);
			memcpy(ZSTR_VAL(written), ZSTR_VAL(v), len);
			if (newLen > len) {
				memset(ZSTR_VAL(written) + len, ' ', newLen - len);
			}
			ZSTR_VAL(written)[offsetValue] = ZSTR_VAL(chr)[0];
			ZSTR_VAL(written)[newLen] = '\0';
			zv::Val result = create(written);
			zend_string_release(written);
			return result;
		}

		return pt_string_type_set_offset_value_type(self, offsetType, valueType);
	}

	/* parent::setOffsetValueType($offsetType, $valueType) — StringType's */
	zv::Val setExistingOffsetValueType(zval *offsetType, zval *valueType) const
	{
		return pt_string_type_set_offset_value_type(self, offsetType, valueType);
	}

	/* new self($this->getValue() . $otherString->getValue()) */
	zv::Val append(zval *otherString) const
	{
		zv::Val own = thisGetValue();
		if (UNEXPECTED(own.isUndef())) return zv::Val();
		zv::Val other = pt_constant_string_get_value(Z_OBJ_P(otherString));
		if (UNEXPECTED(other.isUndef())) return zv::Val();
		zend_string *joined = zend_string_concat2(
			ZSTR_VAL(zv::Ref(own.raw()).asString()), ZSTR_LEN(zv::Ref(own.raw()).asString()),
			ZSTR_VAL(zv::Ref(other.raw()).asString()), ZSTR_LEN(zv::Ref(other.raw()).asString())
		);
		zv::Val result = create(joined);
		zend_string_release(joined);
		return result;
	}

	/* class-string / string for a class-string by precision; for a
	 * non-empty value at the more specific precision the intersection of
	 * string with the literal, numeric (when numeric), non-falsy (or
	 * non-empty for '0'), lowercase and uppercase accessories that hold;
	 * literal-string at the more specific precision otherwise, plain string
	 * else; UNDEF = pending exception */
	zv::Val generalize(zval *precision) const
	{
		bool flag = false;
		if (UNEXPECTED(!isClassStringFlag(flag))) return zv::Val();
		zv::Val moreSpecificZv = pt_type_call(Z_OBJ_P(precision), PT_LC("ismorespecific"), 0, NULL);
		if (UNEXPECTED(moreSpecificZv.isUndef())) return zv::Val();
		bool moreSpecific = zend_is_true(moreSpecificZv.raw());
		if (flag) {
			zval result;
			if (moreSpecific) {
				if (UNEXPECTED(!pt_class_string_type_new(&result))) return zv::Val();
				return zv::Val::adopt(result);
			}
			if (UNEXPECTED(!pt_string_type_new(&result))) return zv::Val();
			return zv::Val::adopt(result);
		}

		zv::Val valueZv = thisGetValue();
		if (UNEXPECTED(valueZv.isUndef())) return zv::Val();
		zend_string *v = zv::Ref(valueZv.raw()).asString();
		if (ZSTR_LEN(v) != 0 && moreSpecific) {
			zv::Arr accessories = zv::Arr::create(6);
			if (UNEXPECTED(!pushString(accessories) || !pushNew(accessories, pt_accessory_literal_string_type_new))) return zv::Val();

			if (is_numeric_string(ZSTR_VAL(v), ZSTR_LEN(v), NULL, NULL, false)) {
				if (UNEXPECTED(!pushNew(accessories, pt_accessory_numeric_string_type_new))) return zv::Val();
			}

			bool isZero = ZSTR_LEN(v) == 1 && ZSTR_VAL(v)[0] == '0';
			if (UNEXPECTED(!pushNew(accessories, isZero ? pt_accessory_non_empty_string_type_new : pt_accessory_non_falsy_string_type_new))) return zv::Val();

			zend_string *lower = zend_string_tolower(v);
			bool isLower = zend_string_equals(lower, v);
			zend_string_release(lower);
			if (isLower && UNEXPECTED(!pushNew(accessories, pt_accessory_lowercase_string_type_new))) return zv::Val();

			zend_string *upper = zend_string_toupper(v);
			bool isUpper = zend_string_equals(upper, v);
			zend_string_release(upper);
			if (isUpper && UNEXPECTED(!pushNew(accessories, pt_accessory_uppercase_string_type_new))) return zv::Val();

			return pt_intersection_of(std::move(accessories));
		}

		if (moreSpecific) {
			zv::Arr accessories = zv::Arr::create(2);
			if (UNEXPECTED(!pushString(accessories) || !pushNew(accessories, pt_accessory_literal_string_type_new))) return zv::Val();
			return pt_intersection_of(std::move(accessories));
		}

		return pt_val_of<pt_string_type_new>();
	}

	/* mixed minus [true, int>=(float) $value] (+ [null, string] for '',
	 * + [false] when falsy) */
	zv::Val getSmallerType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval asFloat;
		ZVAL_DOUBLE(&asFloat, floatValue(v));
		zv::Arr subtractedTypes = zv::Arr::create(5);
		if (UNEXPECTED(!pushBoolean(subtractedTypes, true) || !pushRange(subtractedTypes, pt_integer_range_create_all_greater_than_or_equal_to(&asFloat)))) {
			return zv::Val();
		}
		if (ZSTR_LEN(v) == 0) {
			if (UNEXPECTED(!pushNull(subtractedTypes) || !pushString(subtractedTypes))) return zv::Val();
		}
		if (!truthy(v) && UNEXPECTED(!pushBoolean(subtractedTypes, false))) return zv::Val();
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed minus [int>(float) $value] (+ [true] when falsy) */
	zv::Val getSmallerOrEqualType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval asFloat;
		ZVAL_DOUBLE(&asFloat, floatValue(v));
		zv::Arr subtractedTypes = zv::Arr::create(2);
		if (UNEXPECTED(!pushRange(subtractedTypes, pt_integer_range_create_all_greater_than(&asFloat)))) return zv::Val();
		if (!truthy(v) && UNEXPECTED(!pushBoolean(subtractedTypes, true))) return zv::Val();
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed minus [false, int<=(float) $value] (+ [true] when truthy) */
	zv::Val getGreaterType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval asFloat;
		ZVAL_DOUBLE(&asFloat, floatValue(v));
		zv::Arr subtractedTypes = zv::Arr::create(3);
		if (UNEXPECTED(!pushBoolean(subtractedTypes, false) || !pushRange(subtractedTypes, pt_integer_range_create_all_smaller_than_or_equal_to(&asFloat)))) {
			return zv::Val();
		}
		if (truthy(v) && UNEXPECTED(!pushBoolean(subtractedTypes, true))) return zv::Val();
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* mixed minus [int<(float) $value] (+ [false] when truthy) */
	zv::Val getGreaterOrEqualType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval asFloat;
		ZVAL_DOUBLE(&asFloat, floatValue(v));
		zv::Arr subtractedTypes = zv::Arr::create(2);
		if (UNEXPECTED(!pushRange(subtractedTypes, pt_integer_range_create_all_smaller_than(&asFloat)))) return zv::Val();
		if (truthy(v) && UNEXPECTED(!pushBoolean(subtractedTypes, false))) return zv::Val();
		return pt_type_mixed_minus(subtractedTypes.table());
	}

	/* $this->isClassString(); -1 = pending exception */
	[[nodiscard]] zend_long canAccessConstants() const { return thisIsClassString(); }

	/* $this->getObjectType()->hasConstant($constantName) */
	zv::Val hasConstant(zval *constantName) const
	{
		zval *objectType = getObjectType();
		if (UNEXPECTED(objectType == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(objectType), PT_LC("hasconstant"), 1, constantName);
	}

	/* $this->getObjectType()->getConstant($constantName) */
	zv::Val getConstant(zval *constantName) const
	{
		zval *objectType = getObjectType();
		if (UNEXPECTED(objectType == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(objectType), PT_LC("getconstant"), 1, constantName);
	}

	/* the PHPDoc node of $this->generalize(GeneralizePrecision::moreSpecific())
	 * for a value containing a newline, new ConstTypeNode(new
	 * ConstExprStringNode($this->value, ConstExprStringNode::SINGLE_QUOTED))
	 * otherwise; UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		if (memchr(ZSTR_VAL(v), '\n', ZSTR_LEN(v)) != NULL) {
			zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
			if (UNEXPECTED(precision.isUndef())) return zv::Val();
			zv::Val generalized = isExact() ? generalize(precision.raw()) : pt_type_call(self, PT_LC("generalize"), 1, precision.raw());
			if (UNEXPECTED(generalized.isUndef())) return zv::Val();
			return pt_type_call(Z_OBJ_P(generalized.raw()), PT_LC("tophpdocnode"), 0, NULL);
		}

		zend_class_entry *constExprStringNodeCe = pt_class(PT_CLASS_CONST_EXPR_STRING_NODE);
		if (UNEXPECTED(constExprStringNodeCe == NULL)) return zv::Val();
		zval *singleQuoted = pt_class_constant(constExprStringNodeCe, PT_LC("SINGLE_QUOTED"));
		if (UNEXPECTED(singleQuoted == NULL)) return zv::Val();
		zv::Args args{v, singleQuoted};
		zv::Val constExpr = pt_type_new(PT_CLASS_CONST_EXPR_STRING_NODE, 2, args);
		if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constExpr.raw());
	}

private:
	zend_object *self;

	/* exactly a ConstantStringType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_constant_string_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->getValue() — through the object's class; an owned string,
	 * UNDEF = pending exception */
	zv::Val thisGetValue() const
	{
		if (EXPECTED(isExact())) return getValue();
		return pt_constant_string_get_value(self);
	}

	/* $this->isClassString() — through the object's class; -1 = pending
	 * exception */
	[[nodiscard]] zend_long thisIsClassString() const
	{
		if (EXPECTED(isExact())) return isClassString();
		return pt_type_call_trinary(self, PT_LC("isclassstring"), 0, NULL);
	}

	/* $this->objectType ??= new ObjectType($this->value) — the private
	 * memo, borrowed; NULL = pending exception */
	[[nodiscard]] zval *getObjectType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::objectType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return memo;
		zv::Val objectType = newObjectTypeOfValue();
		if (UNEXPECTED(objectType.isUndef())) return NULL;
		zv::ObjRef(self).propAtWrite(slots::objectType, std::move(objectType));
		return OBJ_PROP_NUM(self, slots::objectType);
	}

	/* new ObjectType($this->value) */
	zv::Val newObjectTypeOfValue() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		return pt_type_new_object_type(&valueZv);
	}

	/* ReflectionProviderStaticAccessor::getInstance() */
	static zv::Val reflectionProviderInstance()
	{
		return pt_type_call_static(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
	}

	/* new Name($name) */
	static zv::Val newName(zend_string *name)
	{
		zval nameZv;
		ZVAL_STR(&nameZv, name);
		return pt_type_new(PT_CLASS_NAME, 1, &nameZv);
	}

	/* FunctionCallableVariant::createFromVariants($function, $function->getVariants()) */
	static zv::Val callableVariants(zval *function)
	{
		zv::Val variants = pt_type_call(Z_OBJ_P(function), PT_LC("getvariants"), 0, NULL);
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Args args{function, variants.raw()};
		return pt_type_call_static(PT_CLASS_FUNCTION_CALLABLE_VARIANT, PT_LC("createfromvariants"), 2, args);
	}

	/* [new TrivialParametersAcceptor()] */
	static zv::Val trivialAcceptors()
	{
		zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
		if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(acceptor));
		return zv::Val(std::move(acceptors));
	}

	/* new ClassNameToObjectTypeResult($type, $uncertainty) */
	static zv::Val classNameToObjectTypeResult(zval *type, bool uncertainty)
	{
		zv::Args args{type, uncertainty};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* IntegerRangeType::fromInterval(-$strlen, $strlen - 1) */
	zv::Val offsetRangeType() const
	{
		zend_string *v = value();
		if (UNEXPECTED(v == NULL)) return zv::Val();
		zend_long strlen = (zend_long) ZSTR_LEN(v);
		return pt_integer_range_from_interval(NullableLong::of(-strlen), NullableLong::of(strlen - 1), 0);
	}

	/* $range->isSuperTypeOf($offsetType)->result's value; -1 = pending
	 * exception */
	[[nodiscard]] static zend_long superTypeVerdict(zval *range, zval *offsetType)
	{
		zv::Val isSuperType = pt_type_call(Z_OBJ_P(range), PT_LC("issupertypeof"), 1, offsetType);
		if (UNEXPECTED(isSuperType.isUndef())) return -1;
		return pt_type_result_trinary(isSuperType.raw());
	}

	/* new self($this->value[$offset]) for an offset the range check proved
	 * within [-strlen, strlen - 1] (a negative one counts from the end) */
	static zv::Val charAt(zend_string *v, zend_long offset)
	{
		zend_long len = (zend_long) ZSTR_LEN(v);
		if (offset < 0) {
			offset += len;
		}
		if (UNEXPECTED(offset < 0 || offset >= len)) {
			zend_throw_error(NULL, "phpstan_turbo: string offset " ZEND_LONG_FMT " outside the range check", offset);
			return zv::Val();
		}
		zend_string *chr = zend_string_init(ZSTR_VAL(v) + offset, 1, 0);
		zv::Val result = create(chr);
		zend_string_release(chr);
		return result;
	}

	/* Strings::truncate($value, self::DESCRIBE_LIMIT), with the twin's
	 * RegexpException fallback of substr() plus an ellipsis. A value of at
	 * most DESCRIBE_LIMIT bytes has at most that many characters and comes
	 * back unchanged, so only longer ones go to Nette; NULL = pending
	 * exception */
	static zv::Str truncate(zend_string *v)
	{
		if (ZSTR_LEN(v) <= PT_CST_DESCRIBE_LIMIT) return zv::Str::copyOf(v);
		zend_class_entry *regexpException = pt_class(PT_CLASS_NETTE_REGEXP_EXCEPTION);
		if (UNEXPECTED(regexpException == NULL)) return zv::Str();
		zv::Args args{v, zend_long(PT_CST_DESCRIBE_LIMIT)};
		zv::Val truncated = pt_type_call_static(PT_CLASS_NETTE_STRINGS, PT_LC("truncate"), 2, args);
		if (UNEXPECTED(truncated.isUndef())) {
			if (EG(exception) != NULL && instanceof_function(EG(exception)->ce, regexpException)) {
				zend_clear_exception();
				/* substr($value, 0, self::DESCRIBE_LIMIT) . "\u{2026}" */
				return zv::Str::adopt(zend_string_concat2(ZSTR_VAL(v), PT_CST_DESCRIBE_LIMIT, "\xE2\x80\xA6", 3));
			}
			return zv::Str();
		}
		if (UNEXPECTED(!zv::Ref(truncated.raw()).isString())) {
			zend_type_error("phpstan_turbo: Strings::truncate() must return string");
			return zv::Str();
		}
		return zv::Str::copyOf(zv::Ref(truncated.raw()).asString());
	}

	/* self::export(): the value in double quotes with control characters,
	 * backslashes and quotes escaped when it holds a control character, in
	 * single quotes with backslashes and single quotes escaped otherwise */
	static zend_string *exportValue(zend_string *v)
	{
		zend_string *escaped = php_addcslashes_str(ZSTR_VAL(v), ZSTR_LEN(v), "\0..\37", 5);
		bool hasControls = !zend_string_equals(escaped, v);
		zend_string_release(escaped);
		if (hasControls) {
			zend_string *inner = php_addcslashes_str(ZSTR_VAL(v), ZSTR_LEN(v), "\0..\37\\\"", 7);
			zend_string *result = zend_string_concat3("\"", 1, ZSTR_VAL(inner), ZSTR_LEN(inner), "\"", 1);
			zend_string_release(inner);
			return result;
		}

		zend_string *inner = php_addcslashes_str(ZSTR_VAL(v), ZSTR_LEN(v), "\\'", 2);
		zend_string *result = zend_string_concat3("'", 1, ZSTR_VAL(inner), ZSTR_LEN(inner), "'", 1);
		zend_string_release(inner);
		return result;
	}

	/* (float) $this->value */
	static double floatValue(zend_string *v)
	{
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		return zval_get_double(&valueZv);
	}

	/* (bool) $this->value */
	static bool truthy(zend_string *v)
	{
		zval valueZv;
		ZVAL_STR(&valueZv, v);
		return zend_is_true(&valueZv);
	}

	static bool pushString(zv::Arr &types)
	{
		zval string;
		if (UNEXPECTED(!pt_string_type_new(&string))) return false;
		types.push(zv::Val::adopt(string));
		return true;
	}

	/* new NullType() — the shadowing class */
	static bool pushNull(zv::Arr &types)
	{
		zval nullType;
		if (UNEXPECTED(!pt_null_type_new(&nullType))) return false;
		types.push(zv::Val::adopt(nullType));
		return true;
	}

	/* new <Shadowed>() through its exported constructor */
	static bool pushNew(zv::Arr &types, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		types.push(zv::Val::adopt(raw));
		return true;
	}

	static bool pushBoolean(zv::Arr &types, bool value)
	{
		zval boolean;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&boolean, value))) return false;
		types.push(zv::Val::adopt(boolean));
		return true;
	}

	static bool pushRange(zv::Arr &types, zv::Val range)
	{
		if (UNEXPECTED(range.isUndef())) return false;
		types.push(std::move(range));
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConstantStringType;

bool pt_constant_string_type_new(zval *out, zend_string *value, bool isClassString)
{
	return pt_val_into(ConstantStringType::create(value, isClassString), out);
}

zv::Val pt_constant_string_get_value(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_constant_string_type)) return ConstantStringType(object).getValue();
	/* a subclass may override getValue() */
	zv::Val result = pt_type_call(object, PT_LC("getvalue"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
		zend_type_error("phpstan_turbo: %s::getValue() must return string", ZSTR_VAL(object->ce->name));
		return zv::Val();
	}
	return result;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConstantStringType(Z_OBJ_P(ZEND_THIS))

void pt_register_constant_string_type()
{

	reg::Class cls("PHPStan\\Type\\Constant\\ConstantStringType");
	ptdecl::ConstantStringType::declareClass(cls);
	cls.privateClassConstantLong("DESCRIBE_LIMIT", PT_CST_DESCRIBE_LIMIT);
	/* the declaration order defines the PT_CST_PROP_* slots */
	cls.privateTypedClassPropertyDefaultNull("objectType", "PHPStan\\Type\\ObjectType");
	cls.privateTypedClassPropertyDefaultNull("arrayKeyType", ptcls::type);
	cls.privateTypedArrayPropertyDefaultEmpty("cachedDescriptions");
	cls.privateTypedProperty("value", MAY_BE_STRING);
	cls.privateTypedProperty("isClassString", MAY_BE_BOOL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *value;
		bool isClassString = false;
		if (!zp::parse<zp::Str, zp::Opt<zp::Bool>>(execute_data, value, isClassString)) RETURN_THROWS();
		PT_THIS.construct(value, isClassString);
	});

	cls.method<&ConstantStringType::getValue>(sigs::getValue);

	cls.method<&ConstantStringType::getConstantStrings>(sigs::getConstantStrings);

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isClassString());
	});

	cls.method<&ConstantStringType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&ConstantStringType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method<&ConstantStringType::describe, zp::Obj>(sigs::describe);

	cls.method<&ConstantStringType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isCallable());
	});

	cls.method<&ConstantStringType::getCallableParametersAcceptors, zp::Obj>(sigs::getCallableParametersAcceptors);

	cls.method<&ConstantStringType::toNumber>(sigs::toNumber);

	cls.method<&ConstantStringType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&ConstantStringType::toObjectTypeForInstanceofCheck>(sigs::toObjectTypeForInstanceofCheck);

	cls.method<&ConstantStringType::toObjectTypeForIsACheck, zp::Obj, zp::Bool, zp::Bool>(sigs::toObjectTypeForIsACheck);

	cls.method<&ConstantStringType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&ConstantStringType::toInteger>(sigs::toInteger);

	cls.method<&ConstantStringType::toFloat>(sigs::toFloat);

	cls.method<&ConstantStringType::toArrayKey>(sigs::toArrayKey);

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ConstantStringType::isString()));
	});

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isNumericString());
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isDecimalIntegerString());
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isNonEmptyString());
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isNonFalsyString());
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(ConstantStringType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isLowercaseString());
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isUppercaseString());
	});

	cls.method<&ConstantStringType::hasOffsetValueType, zp::Obj>(sigs::hasOffsetValueType);

	cls.method<&ConstantStringType::getOffsetValueType, zp::Obj>(sigs::getOffsetValueType);

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		(void) unionValues;
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType));
	});

	cls.method<&ConstantStringType::setExistingOffsetValueType, zp::Obj, zp::Obj>(sigs::setExistingOffsetValueType);

	cls.method<&ConstantStringType::append, zp::Obj>(sigs::append);

	cls.method<&ConstantStringType::generalize, zp::Obj>(sigs::generalize);

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
		(void) phpVersion;
		PT_RETURN_VAL(PT_THIS.getSmallerType());
	});

	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
		(void) phpVersion;
		PT_RETURN_VAL(PT_THIS.getSmallerOrEqualType());
	});

	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
		(void) phpVersion;
		PT_RETURN_VAL(PT_THIS.getGreaterType());
	});

	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion;
		if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
		(void) phpVersion;
		PT_RETURN_VAL(PT_THIS.getGreaterOrEqualType());
	});

	cls.method(sigs::canAccessConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.canAccessConstants());
	});

	cls.method<&ConstantStringType::hasConstant, zp::Zval>(sigs::hasConstant);

	cls.method<&ConstantStringType::getConstant, zp::Zval>(sigs::getConstant);

	cls.method<&ConstantStringType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (isSuperTypeOf) */
	ptdecl::ConstantStringType::registerTraits(cls);

	cls.shadow(&pt_ce_constant_string_type);
}

/* }}} */
