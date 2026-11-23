/*
 * PHPStanTurbo\RuleLevelHelper — native implementation of
 * PHPStan\Rules\RuleLevelHelper.
 *
 * Declared as PHPStan\Rules\RuleLevelHelper itself at activation (final,
 * like the twin; a DI service, so __construct declares the twin's
 * parameters for the container's autowiring). The logic lives in the
 * RuleLevelHelper handle class below, mirroring
 * src/Rules/RuleLevelHelper.php method for method; the registration at the
 * bottom is only the engine ABI glue.
 *
 * The twin's closures are native callback holders
 * (pt_type_native_callback()). None of them needs $this: what they read of
 * it are the check* flags, which travel as a bit set in the holder's first
 * state slot — and transformAcceptedType()'s by-reference $checkForUnion is
 * one more bit of it, read back after the walk. The second state slot holds
 * the closure's other `use` variable ($acceptingType or $traverse).
 *
 * accepts() and findTypeToCheck() are asked by the rules for nearly every
 * call argument, property fetch and method call; here the walks they start
 * and the Type questions they ask stay in C++.
 */

#include "support.h"
#include "generated/RuleLevelHelper.h"
#include "generated/AcceptsResult.h"

namespace sigs = ptdecl::RuleLevelHelper::sig;
namespace slots = ptdecl::RuleLevelHelper::slot;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_rule_level_helper = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Rules\RuleLevelHelper; UNDEF = pending exception. */
class RuleLevelHelper
{
public:
	explicit RuleLevelHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflectionProvider, const bool *flags) const
	{
		pt_write_slot(self, slots::reflectionProvider, reflectionProvider);
		for (uint32_t i = 0; i < 7; i++) {
			zval flag;
			ZVAL_BOOL(&flag, flags[i]);
			pt_write_slot(self, slots::checkNullables + i, &flag);
		}
	}

	/* Mirrors isThis(). */
	static bool isThis(zval *expression, bool &out)
	{
		bool isVariable;
		if (UNEXPECTED(!pt_type_instanceof(expression, PT_CLASS_VARIABLE, isVariable))) return false;
		out = false;
		if (!isVariable) return true;
		zval rv;
		zval *name = zend_read_property(Z_OBJCE_P(expression), Z_OBJ_P(expression), "name", sizeof("name") - 1, 1, &rv);
		if (UNEXPECTED(EG(exception))) return false;
		ZVAL_DEREF(name);
		out = zv::Ref(name).stringEquals("this");
		return true;
	}

	/* Mirrors accepts(). */
	zv::Val accepts(zval *acceptingType, zval *acceptedType, bool strictTypes) const
	{
		/* [$acceptedType, $checkForUnion] = $this->transformAcceptedType($acceptingType, $acceptedType); */
		bool checkForUnion;
		zv::Val transformedAccepted = transformAcceptedType(acceptingType, acceptedType, checkForUnion);
		if (UNEXPECTED(transformedAccepted.isUndef())) return zv::Val();
		zv::Val transformedAccepting = transformCommonType(flags(), acceptingType);
		if (UNEXPECTED(transformedAccepting.isUndef())) return zv::Val();

		zval args[2];
		ZVAL_COPY_VALUE(&args[0], transformedAccepted.raw());
		ZVAL_BOOL(&args[1], strictTypes);
		zv::Val accepts = pt_type_call(Z_OBJ_P(transformedAccepting.raw()), PT_LC("accepts"), 2, args);
		if (UNEXPECTED(accepts.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(accepts.raw()) != IS_OBJECT)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		/* new RuleLevelHelperAcceptsResult($checkForUnion ? $accepts->yes() : !$accepts->no(), $accepts->reasons) */
		zval resultArgs[2];
		zv::Val reasonsHold;
		if (EXPECTED(Z_OBJCE_P(accepts.raw()) == pt_ce_accepts_result)) {
			zend_object *object = Z_OBJ_P(accepts.raw());
			zend_long value = pt_trinary_value(Z_OBJ_P(OBJ_PROP_NUM(object, ptdecl::AcceptsResult::slot::result)));
			ZVAL_BOOL(&resultArgs[0], checkForUnion ? value == PT_TRI_YES : value != PT_TRI_NO);
			ZVAL_COPY_VALUE(&resultArgs[1], OBJ_PROP_NUM(object, ptdecl::AcceptsResult::slot::reasons));
		} else {
			/* the PHP twin of AcceptsResult, declared next to the native one in the differential tests */
			bool answer;
			if (UNEXPECTED(!callBool(accepts.raw(), checkForUnion ? "yes" : "no", checkForUnion ? 3 : 2, answer))) return zv::Val();
			ZVAL_BOOL(&resultArgs[0], checkForUnion ? answer : !answer);
			zval rv;
			zval *reasons = zend_read_property(Z_OBJCE_P(accepts.raw()), Z_OBJ_P(accepts.raw()), "reasons", sizeof("reasons") - 1, 0, &rv);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			reasonsHold = zv::Val::copyOf(zv::Ref(reasons).deref());
			ZVAL_COPY_VALUE(&resultArgs[1], reasonsHold.raw());
		}
		return pt_type_new(PT_CLASS_RULE_LEVEL_HELPER_ACCEPTS_RESULT, 2, resultArgs);
	}

	/* Mirrors findTypeToCheck(). */
	zv::Val findTypeToCheck(zval *scope, zval *var, zend_string *unknownClassErrorPattern, zval *unionTypeCriteriaCallback) const
	{
		if (flag(slots::checkThisOnly)) {
			bool varIsThis;
			if (UNEXPECTED(!isThis(var, varIsThis))) return zv::Val();
			if (!varIsThis) return errorResult(NULL);
		}
		/* $type = $scope->getType($var); */
		zv::Val type = call1(scope, PT_LC("gettype"), var);
		if (UNEXPECTED(type.isUndef())) return zv::Val();

		Found found;
		if (UNEXPECTED(!findTypeToCheckImplementation(found, scope, var, type.raw(), unknownClassErrorPattern, unionTypeCriteriaCallback, true))) return zv::Val();
		return foundTypeResult(found);
	}

private:
	zend_object *self;

	static constexpr zend_long PT_RLH_CHECK_NULLABLES = 1;
	/* transformAcceptedType()'s $checkForUnion: $this->checkUnionTypes
	 * before the walk, set by its closure for a benevolent union */
	static constexpr zend_long PT_RLH_CHECK_FOR_UNION = 2;
	static constexpr zend_long PT_RLH_CHECK_EXPLICIT_MIXED = 4;
	static constexpr zend_long PT_RLH_CHECK_IMPLICIT_MIXED = 8;
	static constexpr zend_long PT_RLH_CHECK_BENEVOLENT_UNION_TYPES = 16;

	/* the arguments of `new FoundTypeResult(...)`: the type, and the
	 * referenced classes, the errors and the tip when they are not `[]`,
	 * `[]` and null */
	struct Found
	{
		zv::Val type;
		zv::Val referencedClasses;
		zv::Val unknownClassErrors;
		zv::Val tip;
	};

	bool flag(uint32_t slot) const { return Z_TYPE_P(OBJ_PROP_NUM(self, slot)) == IS_TRUE; }

	/* the check* properties the closures read, as PT_RLH_* bits */
	zend_long flags() const
	{
		return (flag(slots::checkNullables) ? PT_RLH_CHECK_NULLABLES : zend_long{0})
			| (flag(slots::checkUnionTypes) ? PT_RLH_CHECK_FOR_UNION : zend_long{0})
			| (flag(slots::checkExplicitMixed) ? PT_RLH_CHECK_EXPLICIT_MIXED : zend_long{0})
			| (flag(slots::checkImplicitMixed) ? PT_RLH_CHECK_IMPLICIT_MIXED : zend_long{0})
			| (flag(slots::checkBenevolentUnionTypes) ? PT_RLH_CHECK_BENEVOLENT_UNION_TYPES : zend_long{0});
	}

	static bool isA(zval *value, zend_class_entry *ce)
	{
		return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	}

	static zv::Val call0(zval *object, const char *lcname, size_t len)
	{
		return pt_type_call(Z_OBJ_P(object), lcname, len, 0, NULL);
	}

	static zv::Val call1(zval *object, const char *lcname, size_t len, zval *argument)
	{
		zval arg;
		ZVAL_COPY_VALUE(&arg, argument);
		return pt_type_call(Z_OBJ_P(object), lcname, len, 1, &arg);
	}

	/* $object->method() of a method returning bool; false = pending exception */
	[[nodiscard]] static bool callBool(zval *object, const char *lcname, size_t len, bool &out)
	{
		zv::Val value = call0(object, lcname, len);
		if (UNEXPECTED(value.isUndef())) return false;
		out = zend_is_true(value.raw());
		return true;
	}

	/* $callback($type) of a `callable(Type): bool`; false = pending exception */
	[[nodiscard]] static bool callCriteria(zval *callback, zval *type, bool &out)
	{
		zv::Val value = pt_type_call_callable(callback, 1, type);
		if (UNEXPECTED(value.isUndef())) return false;
		out = zend_is_true(value.raw());
		return true;
	}

	/* NULL for a null value: the pt_*_type_new() arguments take NULL for null */
	static zval *orNull(zv::Val &value)
	{
		return Z_TYPE_P(value.raw()) == IS_NULL ? NULL : value.raw();
	}

	[[nodiscard]] static bool checkClosureArguments(uint32_t argc, zval *argv, uint32_t objects)
	{
		if (EXPECTED(argc >= 2)) {
			bool ok = true;
			for (uint32_t i = 0; i < objects; i++) {
				ok = ok && Z_TYPE(argv[i]) == IS_OBJECT;
			}
			if (EXPECTED(ok)) return true;
		}
		zend_argument_count_error("Too few arguments to function %s::{closure}(), %u passed and exactly 2 expected", ZSTR_VAL(pt_ce_rule_level_helper->name), argc);
		return false;
	}

	/* Mirrors traverseWithoutMapping(); acceptingType NULL = null. */
	static zv::Val traverseWithoutMapping(zend_long flags, zval *type, zval *acceptingType, zval *traverse)
	{
		if (isA(type, pt_ce_generic_object_type) && acceptingType != NULL) {
			zval state;
			ZVAL_LONG(&state, flags);
			zv::Val callback = pt_type_native_callback(traverseSimultaneouslyCallback, &state, traverse);
			if (UNEXPECTED(callback.isUndef())) return zv::Val();
			zval args[2];
			ZVAL_COPY_VALUE(&args[0], acceptingType);
			ZVAL_COPY_VALUE(&args[1], callback.raw());
			return pt_type_call(Z_OBJ_P(type), PT_LC("traversesimultaneously"), 2, args);
		}

		return pt_type_call_callable(traverse, 1, type);
	}

	/* traverseWithoutMapping()'s `function (Type $type, Type $acceptingType)
	 * use ($traverse): Type` — state: the flags, $traverse */
	static void traverseSimultaneouslyCallback(zval *flagsState, zval *traverse, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!checkClosureArguments(argc, argv, 2))) return;
		zval *type = &argv[0];
		if (!(Z_LVAL_P(flagsState) & PT_RLH_CHECK_NULLABLES) && !isA(type, pt_ce_benevolent_union_type)) {
			bool containsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(type, containsNull))) return;
			if (containsNull) {
				bool acceptingContainsNull;
				if (UNEXPECTED(!pt_type_combinator_contains_null(&argv[1], acceptingContainsNull))) return;
				if (!acceptingContainsNull) {
					zv::Val withoutNull = pt_type_combinator_remove_null(type);
					if (UNEXPECTED(withoutNull.isUndef())) return;
					zv::Val traversed = pt_type_call_callable(traverse, 1, withoutNull.raw());
					if (UNEXPECTED(traversed.isUndef())) return;
					traversed.intoReturnValue(return_value);
					return;
				}
			}
		}

		ZVAL_COPY(return_value, type);
	}

	/* Mirrors transformCommonType(). */
	static zv::Val transformCommonType(zend_long flags, zval *type)
	{
		if (!(flags & (PT_RLH_CHECK_EXPLICIT_MIXED | PT_RLH_CHECK_IMPLICIT_MIXED))) {
			return zv::Val::copyOf(zv::Ref(type));
		}

		zval state;
		ZVAL_LONG(&state, flags);
		zv::Val callback = pt_type_native_callback(transformCommonTypeCallback, &state, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* transformCommonType()'s `function (Type $type, callable $traverse):
	 * Type` — state: the flags */
	static void transformCommonTypeCallback(zval *flagsState, zval *, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!checkClosureArguments(argc, argv, 1))) return;
		zend_long flags = Z_LVAL_P(flagsState);
		zval *type = &argv[0];
		if (isA(type, pt_ce_generic_object_type)) {
			bool isTemplateType;
			if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplateType))) return;
			if (!isTemplateType) {
				zv::Val transformed = transformGenericObjectTypeArguments(flags, type, &argv[1]);
				if (UNEXPECTED(transformed.isUndef())) return;
				transformed.intoReturnValue(return_value);
				return;
			}
		}

		/* return $this->transformMixedType($type) ?? $traverse($type); */
		zv::Val transformed = transformMixedType(flags, type);
		if (UNEXPECTED(transformed.isUndef())) return;
		if (Z_TYPE_P(transformed.raw()) == IS_NULL) {
			transformed = pt_type_call_callable(&argv[1], 1, type);
			if (UNEXPECTED(transformed.isUndef())) return;
		}
		transformed.intoReturnValue(return_value);
	}

	/* Mirrors transformMixedType(): null for a type it does not change. */
	static zv::Val transformMixedType(zend_long flags, zval *type)
	{
		if (isA(type, pt_ce_template_mixed_type) && (flags & PT_RLH_CHECK_EXPLICIT_MIXED)) {
			return call0(type, PT_LC("tostrictmixedtype"));
		}
		if (isA(type, pt_ce_mixed_type)) {
			bool isExplicitMixed;
			if (UNEXPECTED(!callBool(type, PT_LC("isexplicitmixed"), isExplicitMixed))) return zv::Val();
			if (flags & (isExplicitMixed ? PT_RLH_CHECK_EXPLICIT_MIXED : PT_RLH_CHECK_IMPLICIT_MIXED)) {
				return pt_type_new_ce(pt_ce_strict_mixed_type, 0, NULL);
			}
		}

		return zv::Val::null();
	}

	/* $variance->invariant() of a TemplateTypeVariance; false = pending exception */
	[[nodiscard]] static bool isInvariant(zval *variance, bool &out)
	{
		if (UNEXPECTED(Z_TYPE_P(variance) != IS_OBJECT)) {
			pt_throw_should_not_happen();
			return false;
		}
		return callBool(variance, PT_LC("invariant"), out);
	}

	/* Mirrors transformGenericObjectTypeArguments(). */
	static zv::Val transformGenericObjectTypeArguments(zend_long flags, zval *type, zval *traverse)
	{
		zv::Val classReflection = call0(type, PT_LC("getclassreflection"));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (Z_TYPE_P(classReflection.raw()) != IS_OBJECT) {
			return pt_type_call_callable(traverse, 1, type);
		}

		zv::Val templateTypeMap = call0(classReflection.raw(), PT_LC("gettemplatetypemap"));
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val templateTypes = call1(classReflection.raw(), PT_LC("typemaptolist"), templateTypeMap.raw());
		if (UNEXPECTED(templateTypes.isUndef())) return zv::Val();
		zv::Val variances = call0(type, PT_LC("getvariances"));
		if (UNEXPECTED(variances.isUndef())) return zv::Val();
		zv::Val arguments = call0(type, PT_LC("gettypes"));
		if (UNEXPECTED(arguments.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(templateTypes.raw()) != IS_ARRAY || Z_TYPE_P(variances.raw()) != IS_ARRAY || Z_TYPE_P(arguments.raw()) != IS_ARRAY)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		bool typesChanged = false;
		zv::Arr types = zv::Arr::empty();
		zend_ulong i = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(arguments.raw())) {
			zval *argument = entry.value().deref().raw();
			zval *templateType = zend_hash_index_find(Z_ARRVAL_P(templateTypes.raw()), i);
			zval *variance = zend_hash_index_find(Z_ARRVAL_P(variances.raw()), i);
			i++;

			/* ($variances[$i] ?? TemplateTypeVariance::createInvariant())->invariant()
			 *     && $templateType instanceof TemplateType
			 *     && $templateType->getVariance()->invariant() */
			bool invariant = true;
			if (variance != NULL && Z_TYPE_P(variance) != IS_NULL) {
				if (UNEXPECTED(!isInvariant(variance, invariant))) return zv::Val();
			}
			if (invariant) {
				invariant = false;
				if (templateType != NULL) {
					if (UNEXPECTED(!pt_type_instanceof(templateType, PT_CLASS_TEMPLATE_TYPE, invariant))) return zv::Val();
				}
				if (invariant) {
					zv::Val templateVariance = call0(templateType, PT_LC("getvariance"));
					if (UNEXPECTED(templateVariance.isUndef())) return zv::Val();
					if (UNEXPECTED(!isInvariant(templateVariance.raw(), invariant))) return zv::Val();
				}
			}

			zv::Val newArgument;
			if (invariant) {
				/* $this->transformMixedType($argument) ?? $argument */
				newArgument = transformMixedType(flags, argument);
				if (UNEXPECTED(newArgument.isUndef())) return zv::Val();
				if (Z_TYPE_P(newArgument.raw()) == IS_NULL) {
					newArgument = zv::Val::copyOf(zv::Ref(argument));
				}
			} else {
				newArgument = pt_type_call_callable(traverse, 1, argument);
				if (UNEXPECTED(newArgument.isUndef())) return zv::Val();
			}

			if (Z_TYPE_P(newArgument.raw()) != IS_OBJECT || Z_OBJ_P(newArgument.raw()) != Z_OBJ_P(argument)) {
				typesChanged = true;
			}
			types.push(std::move(newArgument));
		}

		zv::Val subtractedType = call0(type, PT_LC("getsubtractedtype"));
		if (UNEXPECTED(subtractedType.isUndef())) return zv::Val();
		zv::Val newSubtractedType = zv::Val::null();
		bool subtractedChanged = false;
		if (Z_TYPE_P(subtractedType.raw()) == IS_OBJECT) {
			newSubtractedType = pt_type_call_callable(traverse, 1, subtractedType.raw());
			if (UNEXPECTED(newSubtractedType.isUndef())) return zv::Val();
			subtractedChanged = Z_TYPE_P(newSubtractedType.raw()) != IS_OBJECT || Z_OBJ_P(newSubtractedType.raw()) != Z_OBJ_P(subtractedType.raw());
		}
		if (!typesChanged && !subtractedChanged) {
			return zv::Val::copyOf(zv::Ref(type));
		}

		/* new GenericObjectType($type->getClassName(), $types, $newSubtractedType, variances: $variances) */
		zv::Val className = call0(type, PT_LC("getclassname"));
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(className.raw()) != IS_STRING)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval result;
		if (UNEXPECTED(!pt_generic_object_type_new(&result, Z_STR_P(className.raw()), types.raw(), orNull(newSubtractedType), NULL, variances.raw()))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* Mirrors transformAcceptedType(): the transformed type, $checkForUnion
	 * in the out parameter. */
	zv::Val transformAcceptedType(zval *acceptingType, zval *acceptedType, bool &checkForUnion) const
	{
		/* $checkForUnion = $this->checkUnionTypes; */
		zend_long ownFlags = flags();
		zval state;
		ZVAL_LONG(&state, ownFlags);
		zv::Val callback = pt_type_native_callback(transformAcceptedTypeCallback, &state, acceptingType);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val mapped = pt_type_traverser_map_memoized_of(acceptedType, callback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		checkForUnion = (Z_LVAL_P(pt_type_native_callback_state(callback.raw(), 0)) & PT_RLH_CHECK_FOR_UNION) != 0;

		return transformCommonType(ownFlags, mapped.raw());
	}

	/* the CallableType and ClosureType branches of the closure below: the
	 * same callable with the return type traversed */
	static zv::Val transformCallable(zend_long flags, zval *acceptingType, zval *acceptedType, zval *traverse, bool isClosure)
	{
		bool isCommonCallable;
		if (UNEXPECTED(!callBool(acceptedType, PT_LC("iscommoncallable"), isCommonCallable))) return zv::Val();
		if (isCommonCallable) {
			return zv::Val::copyOf(zv::Ref(acceptedType));
		}

		/* $acceptingReturnType = $acceptingType instanceof ParametersAcceptor ? $acceptingType->getReturnType() : null; */
		zv::Val acceptingReturnType = zv::Val::null();
		bool isParametersAcceptor;
		if (UNEXPECTED(!pt_type_instanceof(acceptingType, PT_CLASS_PARAMETERS_ACCEPTOR, isParametersAcceptor))) return zv::Val();
		if (isParametersAcceptor) {
			acceptingReturnType = call0(acceptingType, PT_LC("getreturntype"));
			if (UNEXPECTED(acceptingReturnType.isUndef())) return zv::Val();
		}

#define PT_RLH_GET(variable, lcname) \
	zv::Val variable = call0(acceptedType, PT_LC(lcname)); \
	if (UNEXPECTED(variable.isUndef())) return zv::Val()

		PT_RLH_GET(parameters, "getparameters");
		PT_RLH_GET(returnType, "getreturntype");
		zv::Val newReturnType = traverseWithoutMapping(flags, returnType.raw(), orNull(acceptingReturnType), traverse);
		if (UNEXPECTED(newReturnType.isUndef())) return zv::Val();
		bool isVariadic;
		if (UNEXPECTED(!callBool(acceptedType, PT_LC("isvariadic"), isVariadic))) return zv::Val();
		PT_RLH_GET(templateTypeMap, "gettemplatetypemap");
		PT_RLH_GET(resolvedTemplateTypeMap, "getresolvedtemplatetypemap");

		zval result;
		if (!isClosure) {
			PT_RLH_GET(templateTags, "gettemplatetags");
			PT_RLH_GET(isPure, "ispure");
			if (UNEXPECTED(!pt_callable_type_new(&result, parameters.raw(), newReturnType.raw(), isVariadic, templateTypeMap.raw(), resolvedTemplateTypeMap.raw(), templateTags.raw(), isPure.raw()))) return zv::Val();
			return zv::Val::adopt(result);
		}

		PT_RLH_GET(callSiteVarianceMap, "getcallsitevariancemap");
		PT_RLH_GET(templateTags, "gettemplatetags");
		PT_RLH_GET(throwPoints, "getthrowpoints");
		PT_RLH_GET(impurePoints, "getimpurepoints");
		PT_RLH_GET(invalidateExpressions, "getinvalidateexpressions");
		PT_RLH_GET(usedVariables, "getusedvariables");
		PT_RLH_GET(acceptsNamedArguments, "acceptsnamedarguments");
		PT_RLH_GET(mustUseReturnValue, "mustusereturnvalue");
		PT_RLH_GET(isStatic, "isstaticclosure");
		PT_RLH_GET(origin, "getorigin");
#undef PT_RLH_GET
		if (UNEXPECTED(!pt_closure_type_new(
			&result, parameters.raw(), newReturnType.raw(), isVariadic, templateTypeMap.raw(), resolvedTemplateTypeMap.raw(),
			callSiteVarianceMap.raw(), templateTags.raw(), throwPoints.raw(), impurePoints.raw(), invalidateExpressions.raw(),
			usedVariables.raw(), acceptsNamedArguments.raw(), mustUseReturnValue.raw(), NULL, isStatic.raw(), NULL, orNull(origin)
		))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* transformAcceptedType()'s `function (Type $acceptedType, callable
	 * $traverse) use ($acceptingType, &$checkForUnion): Type` — state: the
	 * flags with $checkForUnion among them, $acceptingType */
	static void transformAcceptedTypeCallback(zval *flagsState, zval *acceptingType, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!checkClosureArguments(argc, argv, 1))) return;
		zend_long flags = Z_LVAL_P(flagsState);
		zval *acceptedType = &argv[0];
		zval *traverse = &argv[1];
		zv::Val result;
		bool isCallable = isA(acceptedType, pt_ce_callable_type);
		if (isCallable || isA(acceptedType, pt_ce_closure_type)) {
			result = transformCallable(flags, acceptingType, acceptedType, traverse, !isCallable);
		} else if (isA(acceptedType, pt_ce_generic_object_type)) {
			result = traverseWithoutMapping(flags, acceptedType, acceptingType, traverse);
		} else {
			bool isBenevolentUnion = isA(acceptedType, pt_ce_benevolent_union_type);
			if (
				!(flags & PT_RLH_CHECK_NULLABLES)
				&& !isA(acceptingType, pt_ce_null_type)
				&& !isA(acceptedType, pt_ce_null_type)
				&& !isBenevolentUnion
			) {
				zv::Val withoutNull = pt_type_combinator_remove_null(acceptedType);
				if (UNEXPECTED(withoutNull.isUndef())) return;
				result = pt_type_call_callable(traverse, 1, withoutNull.raw());
			} else if ((flags & PT_RLH_CHECK_BENEVOLENT_UNION_TYPES) && isBenevolentUnion) {
				/* $checkForUnion = true; */
				ZVAL_LONG(flagsState, flags | PT_RLH_CHECK_FOR_UNION);
				zv::Val strictUnion = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("tostrictunion"), 1, acceptedType);
				if (UNEXPECTED(strictUnion.isUndef())) return;
				result = pt_type_call_callable(traverse, 1, strictUnion.raw());
			} else {
				result = pt_type_call_callable(traverse, 1, acceptedType);
			}
		}
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* new FoundTypeResult($found->type, ...) */
	static zv::Val foundTypeResult(Found &found)
	{
		zval args[4];
		ZVAL_COPY_VALUE(&args[0], found.type.raw());
		if (found.referencedClasses.isUndef()) {
			ZVAL_EMPTY_ARRAY(&args[1]);
		} else {
			ZVAL_COPY_VALUE(&args[1], found.referencedClasses.raw());
		}
		if (found.unknownClassErrors.isUndef()) {
			ZVAL_EMPTY_ARRAY(&args[2]);
		} else {
			ZVAL_COPY_VALUE(&args[2], found.unknownClassErrors.raw());
		}
		if (found.tip.isUndef()) {
			ZVAL_NULL(&args[3]);
		} else {
			ZVAL_COPY_VALUE(&args[3], found.tip.raw());
		}
		return pt_type_new(PT_CLASS_FOUND_TYPE_RESULT, 4, args);
	}

	/* $found = [new ErrorType(), [], $errors, null]; false = pending exception */
	[[nodiscard]] static bool foundError(Found &found, zv::Val *errors)
	{
		zval errorType;
		if (UNEXPECTED(!pt_error_type_new(&errorType))) return false;
		found.type = zv::Val::adopt(errorType);
		if (errors != NULL) {
			found.unknownClassErrors = std::move(*errors);
		}
		return true;
	}

	/* new FoundTypeResult(new ErrorType(), [], [], null) */
	static zv::Val errorResult(zv::Val *errors)
	{
		Found found;
		if (UNEXPECTED(!foundError(found, errors))) return zv::Val();
		return foundTypeResult(found);
	}

	/* RuleErrorBuilder::message(sprintf($unknownClassErrorPattern, $referencedClass))
	 *     ->line($var->getStartLine())->identifier('class.notFound')
	 *     [->discoveringSymbolsTip()]->build() */
	zv::Val unknownClassError(zval *var, zend_string *unknownClassErrorPattern, zval *referencedClass) const
	{
		zv::Val sprintfName = zv::Val::string("sprintf", sizeof("sprintf") - 1);
		zval sprintfArgs[2];
		ZVAL_STR(&sprintfArgs[0], unknownClassErrorPattern);
		ZVAL_COPY_VALUE(&sprintfArgs[1], referencedClass);
		zv::Val message = pt_type_call_callable(sprintfName.raw(), 2, sprintfArgs);
		if (UNEXPECTED(message.isUndef())) return zv::Val();
		zv::Val builder = pt_type_call_static(PT_CLASS_RULE_ERROR_BUILDER, PT_LC("message"), 1, message.raw());
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		zv::Val line = call0(var, PT_LC("getstartline"));
		if (UNEXPECTED(line.isUndef())) return zv::Val();
		zv::Val chained = call1(builder.raw(), PT_LC("line"), line.raw());
		if (UNEXPECTED(chained.isUndef())) return zv::Val();
		zv::Val identifier = zv::Val::string("class.notFound", sizeof("class.notFound") - 1);
		chained = call1(builder.raw(), PT_LC("identifier"), identifier.raw());
		if (UNEXPECTED(chained.isUndef())) return zv::Val();
		if (flag(slots::discoveringSymbolsTip)) {
			chained = call0(builder.raw(), PT_LC("discoveringsymbolstip"));
			if (UNEXPECTED(chained.isUndef())) return zv::Val();
		}
		return call0(builder.raw(), PT_LC("build"));
	}

	/* $type->getObjectClassNames() === [$className] */
	[[nodiscard]] static bool hasOnlyObjectClassName(zval *type, const char *className, size_t len, bool &out)
	{
		zv::Val names = call0(type, PT_LC("getobjectclassnames"));
		if (UNEXPECTED(names.isUndef())) return false;
		out = false;
		if (Z_TYPE_P(names.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(names.raw())) != 1) return true;
		zval *only = zend_hash_index_find(Z_ARRVAL_P(names.raw()), 0);
		out = only != NULL && zv::Ref(only).deref().stringEquals(className, len);
		return true;
	}

	/* Mirrors findTypeToCheckImplementation(): the arguments of the
	 * FoundTypeResult it returns in found; false = pending exception. */
	[[nodiscard]] bool findTypeToCheckImplementation(Found &found, zval *scope, zval *var, zval *type, zend_string *unknownClassErrorPattern, zval *unionTypeCriteriaCallback, bool isTopLevel) const
	{
		zv::Val typeHold;
		if (!flag(slots::checkNullables)) {
			zend_long isNull = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnull"), 0, NULL);
			if (UNEXPECTED(isNull < 0)) return false;
			if (isNull != PT_TRI_YES) {
				zval nullType;
				if (UNEXPECTED(!pt_null_type_new(&nullType))) return false;
				zv::Val nullTypeHold = zv::Val::adopt(nullType);
				bool nullMeetsCriteria;
				if (UNEXPECTED(!callCriteria(unionTypeCriteriaCallback, nullTypeHold.raw(), nullMeetsCriteria))) return false;
				if (!nullMeetsCriteria) {
					typeHold = pt_type_combinator_remove_null(type);
					if (UNEXPECTED(typeHold.isUndef())) return false;
					type = typeHold.raw();
				}
			}
		}

		bool isMixed = isA(type, pt_ce_mixed_type);
		bool checkExplicitMixed = flag(slots::checkExplicitMixed);
		bool checkImplicitMixed = flag(slots::checkImplicitMixed);
		if ((checkExplicitMixed || checkImplicitMixed) && isMixed) {
			bool isExplicitMixed;
			if (UNEXPECTED(!callBool(type, PT_LC("isexplicitmixed"), isExplicitMixed))) return false;
			if (isExplicitMixed ? checkExplicitMixed : checkImplicitMixed) {
				found.type = isA(type, pt_ce_template_mixed_type)
					? call0(type, PT_LC("tostrictmixedtype"))
					: pt_type_new_ce(pt_ce_strict_mixed_type, 0, NULL);
				return !found.type.isUndef();
			}
		}

		if (isMixed || isA(type, pt_ce_never_type)) {
			return foundError(found, NULL);
		}

		zv::Arr errors = zv::Arr::empty();
		bool hasClassExistsClass = false;
		zv::Val directClassNames;

		if (isTopLevel) {
			directClassNames = call0(type, PT_LC("getobjectclassnames"));
			if (UNEXPECTED(directClassNames.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(directClassNames.raw()) != IS_ARRAY)) {
				pt_throw_should_not_happen();
				return false;
			}
			zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
			for (zv::ArrayEntry entry : zv::ArrRef(directClassNames.raw())) {
				zval *referencedClass = entry.value().deref().raw();
				bool hasClass;
				if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), referencedClass, hasClass))) return false;
				if (hasClass) {
					zv::Val classReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), referencedClass);
					if (UNEXPECTED(classReflection.isUndef())) return false;
					bool isTrait;
					if (UNEXPECTED(!callBool(classReflection.raw(), PT_LC("istrait"), isTrait))) return false;
					if (!isTrait) continue;
				}

				zv::Val isInClassExists = call1(scope, PT_LC("isinclassexists"), referencedClass);
				if (UNEXPECTED(isInClassExists.isUndef())) return false;
				if (zend_is_true(isInClassExists.raw())) {
					hasClassExistsClass = true;
					continue;
				}

				zv::Val error = unknownClassError(var, unknownClassErrorPattern, referencedClass);
				if (UNEXPECTED(error.isUndef())) return false;
				errors.push(std::move(error));
			}
		}

		if (zend_hash_num_elements(Z_ARRVAL_P(errors.raw())) > 0 || hasClassExistsClass) {
			zv::Val errorsValue(std::move(errors));
			return foundError(found, &errorsValue);
		}

		bool checkUnionTypes = flag(slots::checkUnionTypes);
		if (!checkUnionTypes) {
			zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
			if (UNEXPECTED(isObject < 0)) return false;
			if (isObject == PT_TRI_YES) {
				zv::Val classNames = call0(type, PT_LC("getobjectclassnames"));
				if (UNEXPECTED(classNames.isUndef())) return false;
				if (Z_TYPE_P(classNames.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(classNames.raw())) == 0) {
					return foundError(found, NULL);
				}
			}
		}

		bool isUnion = isA(type, pt_ce_union_type);
		bool isBenevolentUnion = isUnion && isA(type, pt_ce_benevolent_union_type);
		bool checkBenevolentUnionTypes = flag(slots::checkBenevolentUnionTypes);
		zv::Val unionTypes;
		if (isUnion) {
			bool shouldFilterUnion = (!checkUnionTypes && !isBenevolentUnion) || (!checkBenevolentUnionTypes && isBenevolentUnion);

			unionTypes = call0(type, PT_LC("gettypes"));
			if (UNEXPECTED(unionTypes.isUndef())) return false;
			zv::Arr newTypes = zv::Arr::empty();
			for (zv::ArrayEntry entry : zv::ArrRef(unionTypes.raw())) {
				zval *innerType = entry.value().deref().raw();
				if (shouldFilterUnion) {
					bool meetsCriteria;
					if (UNEXPECTED(!callCriteria(unionTypeCriteriaCallback, innerType, meetsCriteria))) return false;
					if (!meetsCriteria) continue;
				}

				Found inner;
				if (UNEXPECTED(!findTypeToCheckImplementation(inner, scope, var, innerType, unknownClassErrorPattern, unionTypeCriteriaCallback, false))) return false;
				newTypes.push(std::move(inner.type));
			}

			if (zend_hash_num_elements(Z_ARRVAL_P(newTypes.raw())) > 0) {
				zv::Val newUnion = pt_type_combinator_call_spread(PT_LC("union"), Z_ARRVAL_P(newTypes.raw()));
				if (UNEXPECTED(newUnion.isUndef())) return false;
				if (!checkBenevolentUnionTypes && isBenevolentUnion) {
					newUnion = pt_type_call_static_ce(pt_ce_type_utils, PT_LC("tobenevolentunion"), 1, newUnion.raw());
					if (UNEXPECTED(newUnion.isUndef())) return false;
				}

				found.type = std::move(newUnion);
				found.referencedClasses = std::move(directClassNames);
				return true;
			}
		}

		if (isA(type, pt_ce_intersection_type)) {
			zv::Val innerTypes = call0(type, PT_LC("gettypes"));
			if (UNEXPECTED(innerTypes.isUndef())) return false;
			zv::Arr newTypes = zv::Arr::empty();
			bool changed = false;
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zval *innerType = entry.value().deref().raw();
				if (isA(innerType, pt_ce_template_mixed_type)) {
					changed = true;
					zv::Val strictMixedType = call0(innerType, PT_LC("tostrictmixedtype"));
					if (UNEXPECTED(strictMixedType.isUndef())) return false;
					Found inner;
					if (UNEXPECTED(!findTypeToCheckImplementation(inner, scope, var, strictMixedType.raw(), unknownClassErrorPattern, unionTypeCriteriaCallback, false))) return false;
					newTypes.push(std::move(inner.type));
					continue;
				}
				newTypes.push(zv::Ref(innerType));
			}

			if (changed) {
				found.type = pt_type_combinator_call_spread(PT_LC("intersect"), Z_ARRVAL_P(newTypes.raw()));
				if (UNEXPECTED(found.type.isUndef())) return false;
				found.referencedClasses = std::move(directClassNames);
				return true;
			}
		}

		if (isUnion && zend_hash_num_elements(Z_ARRVAL_P(unionTypes.raw())) == 2) {
			zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
			if (UNEXPECTED(isObject < 0)) return false;
			if (isObject == PT_TRI_YES) {
				zval *first = zend_hash_index_find(Z_ARRVAL_P(unionTypes.raw()), 0);
				zval *second = zend_hash_index_find(Z_ARRVAL_P(unionTypes.raw()), 1);
				bool matches = first != NULL && second != NULL;
				if (matches && UNEXPECTED(!hasOnlyObjectClassName(first, PT_LC("PhpParser\\Node\\Arg"), matches))) return false;
				if (matches && UNEXPECTED(!hasOnlyObjectClassName(second, PT_LC("PhpParser\\Node\\VariadicPlaceholder"), matches))) return false;
				if (matches) {
					bool meetsCriteria;
					if (UNEXPECTED(!callCriteria(unionTypeCriteriaCallback, type, meetsCriteria))) return false;
					if (!meetsCriteria) {
						found.tip = zv::Val::string(PT_LC("Use <fg=cyan>->getArgs()</> instead of <fg=cyan>->args</>."));
					}
				}
			}
		}

		found.type = zv::Val::copyOf(zv::Ref(type));
		found.referencedClasses = std::move(directClassNames);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::RuleLevelHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_rule_level_helper)
{
	reg::Class cls("PHPStan\\Rules\\RuleLevelHelper");
	ptdecl::RuleLevelHelper::declareClass(cls);
	ptdecl::RuleLevelHelper::declareProperties(cls);

	/* eight parameters: zp::parse stops at six */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		bool flags[7];
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_BOOL(flags[0])
			Z_PARAM_BOOL(flags[1])
			Z_PARAM_BOOL(flags[2])
			Z_PARAM_BOOL(flags[3])
			Z_PARAM_BOOL(flags[4])
			Z_PARAM_BOOL(flags[5])
			Z_PARAM_BOOL(flags[6])
		ZEND_PARSE_PARAMETERS_END();
		RuleLevelHelper(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider, flags);
	});

	cls.method(sigs::isThis, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expression;
		if (!zp::parse<zp::Obj>(execute_data, expression)) RETURN_THROWS();
		bool result;
		if (UNEXPECTED(!RuleLevelHelper::isThis(expression, result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType, *acceptedType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool>(execute_data, acceptingType, acceptedType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(RuleLevelHelper(Z_OBJ_P(ZEND_THIS)).accepts(acceptingType, acceptedType, strictTypes));
	});

	cls.method(sigs::findTypeToCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *var, *unionTypeCriteriaCallback;
		zend_string *unknownClassErrorPattern;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Str, zp::Zval>(execute_data, scope, var, unknownClassErrorPattern, unionTypeCriteriaCallback)) RETURN_THROWS();
		PT_RETURN_VAL(RuleLevelHelper(Z_OBJ_P(ZEND_THIS)).findTypeToCheck(scope, var, unknownClassErrorPattern, unionTypeCriteriaCallback));
	});

	cls.shadow(&pt_ce_rule_level_helper);
}

/* }}} */
