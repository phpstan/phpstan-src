/*
 * PHPStanTurbo\SimpleImpurePoint — native implementation of
 * PHPStan\Reflection\Callables\SimpleImpurePoint.
 *
 * A final value class over the twin's three promoted slots, plus the two
 * statics every call handler asks: createFromVariant() (the impure point of a
 * call to a function or method, null for a pure one) and
 * resolvePureUnlessCallableIsImpureVerdict(). Native callers use
 * pt_simple_impure_point_resolve(), which answers createFromVariant() without
 * the intermediate object (the method call handler copies its three values
 * into an ImpurePoint straight away), and pt_simple_impure_point_new(). The
 * reflections are asked through pt_extended_method_reflection_call() (the
 * native method reflections directly), the PHP parameters acceptors and
 * parameters through one cached method site each, the Types through their
 * ops, the scope through its direct entry.
 */

#include "support.h"
#include "generated/SimpleImpurePoint.h"

namespace slots = ptdecl::SimpleImpurePoint::slot;
namespace sigs = ptdecl::SimpleImpurePoint::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"

zend_class_entry *pt_ce_simple_impure_point = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_sip_get_return_type_site;
pt_method_site pt_sip_get_parameters_site;
pt_property_site pt_sip_arg_name_site;
pt_property_site pt_sip_arg_value_site;
pt_property_site pt_sip_identifier_name_site;

/* $variant->getReturnType() */
zv::Val variantReturnType(zval *variant)
{
	return pt_call_method_cached(pt_sip_get_return_type_site, Z_OBJ_P(variant), PT_LC("getreturntype"), 0, NULL);
}

/* $variant->getParameters() */
zv::Val variantParameters(zval *variant)
{
	return pt_call_method_cached(pt_sip_get_parameters_site, Z_OBJ_P(variant), PT_LC("getparameters"), 0, NULL);
}

/* $parameter->isPureUnlessCallableIsImpureParameter() */
zv::Val parameterIsPureUnlessCallableIsImpure(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER);
}

/* $parameter->getName() */
zv::Val parameterName(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
}

/* }}} */

/* the literals of the twin: 'functionCall' / 'methodCall', permanent
 * interned strings (module startup) */
zend_string *pt_sip_function_call = nullptr;
zend_string *pt_sip_method_call = nullptr;

/* $object->$name of a PhpParser node (dereferenced); the twin's warning and
 * null for a non-object or a class without the property */
zval *nodeProperty(pt_property_site &site, zval *object, const char *name, size_t len)
{
	static zval null;
	if (EXPECTED(Z_TYPE_P(object) == IS_OBJECT)) {
		zval *value = pt_property_cached(site, Z_OBJ_P(object), name, len);
		if (EXPECTED(value != NULL)) {
			ZVAL_DEREF(value);
			if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
		}
		zend_error(E_WARNING, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(object)->name), name);
	} else {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(object));
	}
	ZVAL_NULL(&null);
	return &null;
}

/* a TrinaryLogic answer the twin reads with ->yes() / ->no(): its PT_TRI_*
 * value; -1 = pending exception */
zend_long trinaryOf(zv::Val value)
{
	if (UNEXPECTED(value.isUndef())) return -1;
	return pt_type_trinary_value(value.raw());
}

/* $i === $parameterIndex of two array keys */
bool identicalKeys(const zv::ArrayEntry &a, const zv::ArrayEntry &b)
{
	zend_string *aKey = a.stringKeyOrNull();
	zend_string *bKey = b.stringKeyOrNull();
	if (aKey == NULL) return bKey == NULL && a.indexKey() == b.indexKey();
	return bKey != NULL && zend_string_equals(aKey, bKey);
}

/* private const SIDE_EFFECT_FLIP_PARAMETERS = [functionName => [name, pos, testName]] */
struct FlipParameter
{
	const char *function;
	const char *name;
	zend_long position;
	const char *testName;
};

const FlipParameter flipParameters[] = {
	{"print_r", "return", 1, "isTruthy"},
	{"var_export", "return", 1, "isTruthy"},
	{"highlight_string", "return", 1, "isTruthy"},
};

const FlipParameter *flipParameterOf(zend_string *functionName)
{
	for (const FlipParameter &flip : flipParameters) {
		if (zend_string_equals_cstr(functionName, flip.function, strlen(flip.function))) return &flip;
	}
	return NULL;
}

void flipParametersConstant(zval *out)
{
	HashTable *table = (HashTable *) pemalloc(sizeof(HashTable), 1);
	zend_hash_init(table, 3, NULL, NULL, 1);
	for (const FlipParameter &flip : flipParameters) {
		HashTable *entry = (HashTable *) pemalloc(sizeof(HashTable), 1);
		zend_hash_init(entry, 3, NULL, NULL, 1);
		zend_hash_real_init_packed(entry);
		zval value;
		ZVAL_INTERNED_STR(&value, zend_string_init_interned(flip.name, strlen(flip.name), 1));
		zend_hash_next_index_insert(entry, &value);
		ZVAL_LONG(&value, flip.position);
		zend_hash_next_index_insert(entry, &value);
		ZVAL_INTERNED_STR(&value, zend_string_init_interned(flip.testName, strlen(flip.testName), 1));
		zend_hash_next_index_insert(entry, &value);
		GC_ADD_FLAGS(entry, IS_ARRAY_IMMUTABLE);
		GC_SET_REFCOUNT(entry, 2);
		ZVAL_ARR(&value, entry);
		Z_TYPE_INFO(value) = IS_ARRAY;
		zend_hash_str_add_new(table, flip.function, strlen(flip.function), &value);
	}
	GC_ADD_FLAGS(table, IS_ARRAY_IMMUTABLE);
	GC_SET_REFCOUNT(table, 2);
	ZVAL_ARR(out, table);
	Z_TYPE_INFO_P(out) = IS_ARRAY;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Callables\SimpleImpurePoint; UNDEF = pending
 * exception. */
class SimpleImpurePoint
{
public:
	explicit SimpleImpurePoint(zend_object *self) : self(self) {}

	/* __construct(private string $identifier, private string $description,
	 * private bool $certain) */
	void construct(zend_string *identifier, zend_string *description, bool certain) const
	{
		zval value;
		ZVAL_STR(&value, identifier);
		pt_write_slot(self, slots::identifier, &value);
		ZVAL_STR(&value, description);
		pt_write_slot(self, slots::description, &value);
		ZVAL_BOOL(&value, certain);
		pt_write_slot(self, slots::certain, &value);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zend_string *identifier, zend_string *description, bool certain)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_simple_impure_point) != SUCCESS)) return zv::Val();
		SimpleImpurePoint(Z_OBJ(object)).construct(identifier, description, certain);
		return zv::Val::adopt(object);
	}

	zv::Val getIdentifier() const { return read(slots::identifier, "identifier"); }
	zv::Val getDescription() const { return read(slots::description, "description"); }
	zv::Val isCertain() const { return read(slots::certain, "certain"); }

	/* Mirrors createFromVariant() up to the constructor call: out.exists
	 * false for the twin's null; false = pending exception. $variant /
	 * $scope NULL or IS_NULL for null. */
	[[nodiscard]] static bool resolve(zval *function, zval *variant, zval *scope, zval *args, pt_simple_impure_point_data &out)
	{
		out.exists = false;
		if (variant != NULL && Z_TYPE_P(variant) == IS_NULL) variant = NULL;
		if (scope != NULL && Z_TYPE_P(scope) == IS_NULL) scope = NULL;

		zend_long hasSideEffects = trinaryOf(pt_extended_method_reflection_call(function, PT_MR_HAS_SIDE_EFFECTS));
		if (UNEXPECTED(hasSideEffects < 0)) return false;
		if (hasSideEffects == PT_TRI_NO) return true;

		zend_long isPure = trinaryOf(pt_extended_method_reflection_call(function, PT_MR_IS_PURE));
		if (UNEXPECTED(isPure < 0)) return false;
		bool certain = isPure == PT_TRI_NO;
		if (variant != NULL && !certain) {
			zv::Val returnType = variantReturnType(variant);
			if (UNEXPECTED(returnType.isUndef())) return false;
			zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(returnType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid < 0)) return false;
			certain = isVoid == PT_TRI_YES;
		}

		if (!certain && scope != NULL && variant != NULL) {
			zend_long verdict = PT_TRI_YES;
			bool hasVerdict = false;
			if (UNEXPECTED(!resolveVerdict(variant, scope, args, hasVerdict, verdict))) return false;
			if (hasVerdict) {
				if (verdict == PT_TRI_YES) return true;
				if (verdict == PT_TRI_NO) certain = true;
			}
		}

		zend_class_entry *functionReflectionCe = pt_class(PT_CLASS_FUNCTION_REFLECTION);
		if (UNEXPECTED(functionReflectionCe == NULL)) return false;
		if (instanceof_function(Z_OBJCE_P(function), functionReflectionCe)) {
			/* isset(self::SIDE_EFFECT_FLIP_PARAMETERS[$function->getName()]) && $scope !== null */
			zv::Val flipName = pt_extended_method_reflection_call(function, PT_MR_GET_NAME);
			if (UNEXPECTED(flipName.isUndef())) return false;
			const FlipParameter *flip = Z_TYPE_P(flipName.raw()) == IS_STRING ? flipParameterOf(Z_STR_P(flipName.raw())) : NULL;
			if (flip != NULL && scope != NULL) {
				/* [$flipParameterName, ...] = self::SIDE_EFFECT_FLIP_PARAMETERS[$function->getName()] */
				zv::Val again = pt_extended_method_reflection_call(function, PT_MR_GET_NAME);
				if (UNEXPECTED(again.isUndef())) return false;
				bool sideEffectFlipped = false;
				if (UNEXPECTED(!isSideEffectFlipped(flip, scope, args, sideEffectFlipped))) return false;
				if (sideEffectFlipped) return true;
			}

			zv::Val name = pt_extended_method_reflection_call(function, PT_MR_GET_NAME);
			if (UNEXPECTED(name.isUndef())) return false;
			smart_str description = {NULL, 0};
			smart_str_appends(&description, "call to function ");
			appendString(description, name.raw());
			smart_str_appends(&description, "()");
			out.exists = true;
			out.identifier = pt_sip_function_call;
			out.description = smart_str_extract(&description);
			out.certain = certain;
			return true;
		}

		zv::Val declaringClass = pt_extended_method_reflection_call(function, PT_MR_GET_DECLARING_CLASS);
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		zv::Val displayName = pt_class_reflection_get_display_name(Z_OBJ_P(declaringClass.raw()), true);
		if (UNEXPECTED(displayName.isUndef())) return false;
		zv::Val name = pt_extended_method_reflection_call(function, PT_MR_GET_NAME);
		if (UNEXPECTED(name.isUndef())) return false;
		smart_str description = {NULL, 0};
		smart_str_appends(&description, "call to method ");
		appendString(description, displayName.raw());
		smart_str_appends(&description, "::");
		appendString(description, name.raw());
		smart_str_appends(&description, "()");
		out.exists = true;
		out.identifier = pt_sip_method_call;
		out.description = smart_str_extract(&description);
		out.certain = certain;
		return true;
	}

	/* Mirrors resolvePureUnlessCallableIsImpureVerdict(): hasVerdict false
	 * for the twin's null, verdict the PT_TRI_* value otherwise; false =
	 * pending exception */
	[[nodiscard]] static bool resolveVerdict(zval *variant, zval *scope, zval *args, bool &hasVerdict, zend_long &verdict)
	{
		hasVerdict = false;
		verdict = PT_TRI_YES;
		zv::Val parameters = variantParameters(variant);
		if (UNEXPECTED(parameters.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(parameters.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(parameters.raw()));
			return !EG(exception);
		}
		zend_class_entry *extendedParameterCe = pt_class(PT_CLASS_EXTENDED_PARAMETER_REFLECTION);
		if (UNEXPECTED(extendedParameterCe == NULL)) return false;

		for (auto parameterEntry : zv::TableRef(Z_ARRVAL_P(parameters.raw()))) {
			zval *parameter = parameterEntry.value().deref().raw();
			if (Z_TYPE_P(parameter) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(parameter), extendedParameterCe)) continue;
			zend_long pureUnless = trinaryOf(parameterIsPureUnlessCallableIsImpure(parameter));
			if (UNEXPECTED(pureUnless < 0)) return false;
			if (pureUnless == PT_TRI_NO) continue;

			if (!hasVerdict) {
				hasVerdict = true;
				verdict = PT_TRI_YES;
			}

			zval *matchedArg = NULL;
			bool hasNamedParameter = false;
			if (EXPECTED(Z_TYPE_P(args) == IS_ARRAY)) {
				for (auto argEntry : zv::TableRef(Z_ARRVAL_P(args))) {
					zval *arg = argEntry.value().deref().raw();
					zval *argName = nodeProperty(pt_sip_arg_name_site, arg, PT_LC("name"));
					if (UNEXPECTED(EG(exception))) return false;
					if (Z_TYPE_P(argName) != IS_NULL) {
						hasNamedParameter = true;
						zval *identifierName = nodeProperty(pt_sip_identifier_name_site, argName, PT_LC("name"));
						if (UNEXPECTED(EG(exception))) return false;
						zv::Val parameterNameValue = parameterName(parameter);
						if (UNEXPECTED(parameterNameValue.isUndef())) return false;
						if (zend_is_identical(identifierName, parameterNameValue.raw())) {
							matchedArg = arg;
							break;
						}
						continue;
					}
					if (!hasNamedParameter && identicalKeys(argEntry, parameterEntry)) {
						matchedArg = arg;
						break;
					}
				}
			} else {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(args));
				if (UNEXPECTED(EG(exception))) return false;
			}

			if (matchedArg == NULL) continue;

			zval *argValue = nodeProperty(pt_sip_arg_value_site, matchedArg, PT_LC("value"));
			if (UNEXPECTED(EG(exception))) return false;
			if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return false;
			zv::Val argType = pt_mutating_scope_get_type(Z_OBJ_P(scope), argValue);
			if (UNEXPECTED(argType.isUndef())) return false;
			zend_long isNull = pt_type_op_trinary(Z_OBJ_P(argType.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNull < 0)) return false;
			if (isNull == PT_TRI_YES) continue;

			zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(argType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(isCallable < 0)) return false;
			if (isCallable != PT_TRI_YES) {
				verdict = pt_trinary_and(verdict, PT_TRI_MAYBE);
				continue;
			}

			zv::Val acceptors = pt_type_call(Z_OBJ_P(argType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
			if (UNEXPECTED(acceptors.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(acceptors.raw()) != IS_ARRAY)) {
				zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(acceptors.raw()));
				return false;
			}
			if (zend_hash_num_elements(Z_ARRVAL_P(acceptors.raw())) == 0) {
				verdict = pt_trinary_and(verdict, PT_TRI_MAYBE);
				continue;
			}

			for (auto acceptorEntry : zv::TableRef(Z_ARRVAL_P(acceptors.raw()))) {
				zval *acceptor = acceptorEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(acceptor) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function isPure() on %s", zend_zval_value_name(acceptor));
					return false;
				}
				zend_long pure = trinaryOf(pt_type_call(Z_OBJ_P(acceptor), PT_LC("ispure"), 0, NULL));
				if (UNEXPECTED(pure < 0)) return false;
				verdict = pt_trinary_and(verdict, pure);
			}
		}

		return true;
	}

private:
	zend_object *self;

	/* a copy of the typed slot; UNDEF with the uninitialized-read Error pending */
	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* the flip loop over the call's arguments ($checker being 'isTruthy':
	 * $type->toBoolean()->isTrue()->yes()); false = pending exception */
	[[nodiscard]] static bool isSideEffectFlipped(const FlipParameter *flip, zval *scope, zval *args, bool &flipped)
	{
		flipped = false;
		if (UNEXPECTED(Z_TYPE_P(args) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(args));
			return !EG(exception);
		}
		bool hasNamedParameter = false;
		for (auto argEntry : zv::TableRef(Z_ARRVAL_P(args))) {
			zval *arg = argEntry.value().deref().raw();
			bool isFlipParameter = false;
			zval *argName = nodeProperty(pt_sip_arg_name_site, arg, PT_LC("name"));
			if (UNEXPECTED(EG(exception))) return false;
			if (Z_TYPE_P(argName) != IS_NULL) {
				hasNamedParameter = true;
				zval *identifierName = nodeProperty(pt_sip_identifier_name_site, argName, PT_LC("name"));
				if (UNEXPECTED(EG(exception))) return false;
				if (Z_TYPE_P(identifierName) == IS_STRING && zend_string_equals_cstr(Z_STR_P(identifierName), flip->name, strlen(flip->name))) {
					isFlipParameter = true;
				}
			}
			if (!hasNamedParameter && argEntry.stringKeyOrNull() == NULL && (zend_long) argEntry.indexKey() == flip->position) {
				isFlipParameter = true;
			}
			if (isFlipParameter) {
				zval *argValue = nodeProperty(pt_sip_arg_value_site, arg, PT_LC("value"));
				if (UNEXPECTED(EG(exception))) return false;
				zv::Val type = pt_mutating_scope_get_type(Z_OBJ_P(scope), argValue);
				if (UNEXPECTED(type.isUndef())) return false;
				zv::Val boolean = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("toboolean"), 0, NULL);
				if (UNEXPECTED(boolean.isUndef())) return false;
				zend_long isTrue = trinaryOf(pt_type_call(Z_OBJ_P(boolean.raw()), PT_LC("istrue"), 0, NULL));
				if (UNEXPECTED(isTrue < 0)) return false;
				flipped = isTrue == PT_TRI_YES;
				break;
			}
		}
		return true;
	}

	static void appendString(smart_str &out, zval *value)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) {
			smart_str_append(&out, Z_STR_P(value));
			return;
		}
		zend_string *string = zval_get_string(value);
		smart_str_append(&out, string);
		zend_string_release(string);
	}

};

} // namespace phpstanturbo

using phpstanturbo::SimpleImpurePoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_simple_impure_point_new(zend_string *identifier, zend_string *description, bool certain)
{
	return SimpleImpurePoint::create(identifier, description, certain);
}

bool pt_simple_impure_point_resolve(zval *function, zval *variant, zval *scope, zval *args, pt_simple_impure_point_data &out)
{
	return SimpleImpurePoint::resolve(function, variant, scope, args, out);
}

bool pt_simple_impure_point_resolve_verdict(zval *variant, zval *scope, zval *args, bool &hasVerdict, zend_long &verdict)
{
	return SimpleImpurePoint::resolveVerdict(variant, scope, args, hasVerdict, verdict);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_simple_impure_point()
{
	pt_sip_function_call = zend_string_init_interned(PT_LC("functionCall"), 1);
	pt_sip_method_call = zend_string_init_interned(PT_LC("methodCall"), 1);

	reg::Class cls("PHPStan\\Reflection\\Callables\\SimpleImpurePoint");
	ptdecl::SimpleImpurePoint::declareClass(cls);
	ptdecl::SimpleImpurePoint::declareProperties(cls);
	cls.privateClassConstantValue("SIDE_EFFECT_FLIP_PARAMETERS", flipParametersConstant);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *identifier, *description;
		bool certain;
		if (!zp::parse<zp::Str, zp::Str, zp::Bool>(execute_data, identifier, description, certain)) RETURN_THROWS();
		SimpleImpurePoint(Z_OBJ_P(ZEND_THIS)).construct(identifier, description, certain);
	});

	cls.method(sigs::createFromVariant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *function, *variant, *scope = NULL, *args = NULL;
		ZEND_PARSE_PARAMETERS_START(2, 4)
			Z_PARAM_OBJECT(function)
			Z_PARAM_OBJECT_OR_NULL(variant)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(scope)
			Z_PARAM_ARRAY(args)
		ZEND_PARSE_PARAMETERS_END();
		zval emptyArgs;
		if (args == NULL) {
			ZVAL_EMPTY_ARRAY(&emptyArgs);
			args = &emptyArgs;
		}
		pt_simple_impure_point_data data;
		if (UNEXPECTED(!SimpleImpurePoint::resolve(function, variant, scope, args, data))) RETURN_THROWS();
		if (!data.exists) RETURN_NULL();
		zv::Val point = SimpleImpurePoint::create(data.identifier, data.description, data.certain);
		zend_string_release(data.description);
		PT_RETURN_VAL(std::move(point));
	});

	cls.method(sigs::resolvePureUnlessCallableIsImpureVerdict, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *variant, *scope, *args;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Arr>(execute_data, variant, scope, args)) RETURN_THROWS();
		bool hasVerdict = false;
		zend_long verdict = PT_TRI_YES;
		if (UNEXPECTED(!SimpleImpurePoint::resolveVerdict(variant, scope, args, hasVerdict, verdict))) RETURN_THROWS();
		if (!hasVerdict) RETURN_NULL();
		RETURN_COPY(pt_trinary_singleton(verdict));
	});

	cls.method<&SimpleImpurePoint::getIdentifier>(sigs::getIdentifier);
	cls.method<&SimpleImpurePoint::getDescription>(sigs::getDescription);
	cls.method<&SimpleImpurePoint::isCertain>(sigs::isCertain);

	cls.shadow(&pt_ce_simple_impure_point);
}

/* }}} */
