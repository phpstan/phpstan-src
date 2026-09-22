/*
 * PHPStanTurbo\TypeUtils — native implementation of PHPStan\Type\TypeUtils.
 *
 * Declared as PHPStan\Type\TypeUtils itself at activation: final, static
 * helpers only, no state. The twin's private map() has no PHP-visible
 * counterpart natively (a private method of a final class is never called
 * from outside) — it is the C++ helper the two get*() methods and
 * getAccessoryTypes() delegate to, taking the class entry the twin's
 * class-name string resolves to.
 *
 * The shadowed classes the twin names (ConstantIntegerType, IntegerRangeType,
 * UnionType, BenevolentUnionType, IntersectionType, ThisType, HasPropertyType,
 * NeverType) are tested through the class entries the native code holds; the
 * PHP ones (AccessoryType, TemplateType, TemplateBenevolentUnionType,
 * TemplateUnionType, TypeTraverser, LateResolvableTraverser, TypeCombinator)
 * through the class map.
 */

#include "TypeTraits.h"
#include "generated/TypeUtils.h"

namespace sigs = ptdecl::TypeUtils::sig;

zend_class_entry *pt_ce_type_utils = nullptr;

namespace phpstanturbo {

struct ConstantArrayCombinationContext
{
	zv::Arr result = zv::Arr::create(0);
};

static bool collectConstantArrayCombination(zval *combination, void *opaque)
{
	auto *context = static_cast<ConstantArrayCombinationContext *>(opaque);
	zv::Val intersected;
	bool first = true;
	for (zv::ArrayEntry memberEntry : zv::ArrRef(combination)) {
		if (first) {
			intersected = zv::Val::copyOf(memberEntry.value());
			first = false;
			continue;
		}
		zv::Args args{intersected.raw(), memberEntry.value().raw()};
		intersected = pt_type_combinator_call(PT_LC("intersect"), 2, args);
		if (UNEXPECTED(intersected.isUndef())) return false;
	}
	if (UNEXPECTED(first)) {
		zend_throw_error(NULL, "Undefined array key 0");
		return false;
	}
	if (zv::Ref(intersected.raw()).isObject() && instanceof_function(Z_OBJCE_P(intersected.raw()), pt_ce_never_type)) return true;
	context->result.push(std::move(intersected));
	return true;
}

/* Mirrors PHPStan\Type\TypeUtils. */
class TypeUtils
{
public:
	/* self::map(ConstantIntegerType::class, $type, false); UNDEF = pending
	 * exception */
	static zv::Val getConstantIntegers(zval *type) { return map(pt_ce_constant_integer_type, type, false, true); }

	/* self::map(IntegerRangeType::class, $type, false); UNDEF = pending
	 * exception */
	static zv::Val getIntegerRanges(zval *type) { return map(pt_ce_integer_range_type, type, false, true); }

	/* the private map($typeClass, $type, $inspectIntersections,
	 * $stopOnUnmatched): [$type] for an instance of the class, the matches
	 * over a union's members (none as soon as one member matches nothing,
	 * when stopping on unmatched), the matching members of an intersection
	 * when inspecting those, [] otherwise; UNDEF = pending exception */
	static zv::Val map(zend_class_entry *typeClass, zval *type, bool inspectIntersections, bool stopOnUnmatched)
	{
		if (instanceof_function(Z_OBJCE_P(type), typeClass)) return listOf(type);

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
			zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(type));
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			zv::Arr matchingTypes = zv::Arr::create(0);
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return a list of Type");
					return zv::Val();
				}
				zv::Val matchingInner = map(typeClass, innerType.raw(), inspectIntersections, stopOnUnmatched);
				if (UNEXPECTED(matchingInner.isUndef())) return zv::Val();
				if (zv::ArrRef(matchingInner.raw()).size() == 0) {
					if (stopOnUnmatched) return zv::Val(zv::Arr::empty());
					continue;
				}
				for (zv::ArrayEntry mapped : zv::ArrRef(matchingInner.raw())) {
					matchingTypes.push(mapped.value());
				}
			}
			return zv::Val(std::move(matchingTypes));
		}

		if (inspectIntersections && instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			zv::Val innerTypes = getTypes(type);
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			zv::Arr matchingTypes = zv::Arr::create(0);
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: IntersectionType::getTypes() must return a list of Type");
					return zv::Val();
				}
				if (!instanceof_function(Z_OBJCE_P(innerType.raw()), typeClass)) {
					if (stopOnUnmatched) return zv::Val(zv::Arr::empty());
					continue;
				}
				matchingTypes.push(innerType);
			}
			return zv::Val(std::move(matchingTypes));
		}

		return zv::Val(zv::Arr::empty());
	}

	/* the type itself for a BenevolentUnionType, new
	 * BenevolentUnionType($type->getTypes()) for any other UnionType, the
	 * type otherwise (UnionType.cpp's helper); UNDEF = pending exception */
	static zv::Val toBenevolentUnion(zval *type) { return pt_union_to_benevolent(type); }

	/* a TemplateBenevolentUnionType rebuilt as a TemplateUnionType over the
	 * strict union of its bound, new UnionType($type->getTypes()) for any
	 * other BenevolentUnionType, the type otherwise; UNDEF = pending
	 * exception */
	static zv::Val toStrictUnion(zval *type)
	{
		bool templateBenevolent;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_template_benevolent_union_type, templateBenevolent))) return zv::Val();
		if (templateBenevolent) {
			/* new TemplateUnionType($type->getScope(), $type->getStrategy(),
			 * $type->getVariance(), $type->getName(),
			 * static::toStrictUnion($type->getBound()), $type->getDefault()) */
			zend_object *object = Z_OBJ_P(type);
			zv::Val scope = pt_type_call(object, PT_LC("getscope"), 0, NULL);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zv::Val strategy = pt_type_call(object, PT_LC("getstrategy"), 0, NULL);
			if (UNEXPECTED(strategy.isUndef())) return zv::Val();
			zv::Val variance = pt_type_call(object, PT_LC("getvariance"), 0, NULL);
			if (UNEXPECTED(variance.isUndef())) return zv::Val();
			zv::Val name = pt_type_call(object, PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val bound = pt_type_call(object, PT_LC("getbound"), 0, NULL);
			if (UNEXPECTED(bound.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(bound.raw()).isObject())) {
				zend_type_error("phpstan_turbo: %s::getBound() must return a Type", ZSTR_VAL(object->ce->name));
				return zv::Val();
			}
			zv::Val strictBound = toStrictUnion(bound.raw());
			if (UNEXPECTED(strictBound.isUndef())) return zv::Val();
			zv::Val defaultType = pt_type_call(object, PT_LC("getdefault"), 0, NULL);
			if (UNEXPECTED(defaultType.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
				zend_type_error("phpstan_turbo: %s::getName() must return string", ZSTR_VAL(object->ce->name));
				return zv::Val();
			}
			zval created;
			if (UNEXPECTED(!pt_template_union_type_new(&created, scope.raw(), strategy.raw(), variance.raw(), Z_STR_P(name.raw()), strictBound.raw(), defaultType.raw()))) {
				return zv::Val();
			}
			return zv::Val::adopt(created);
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_benevolent_union_type)) {
			/* new UnionType($type->getTypes()) */
			zv::Val types = pt_union_type_get_types(Z_OBJ_P(type));
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			zval result;
			if (UNEXPECTED(!pt_union_type_new(&result, types.raw()))) return zv::Val();
			return zv::Val::adopt(result);
		}

		return zv::Val::copyOf(zv::Ref(type));
	}

	/* a union's members flattened recursively; a type with constant arrays
	 * expanded into every intersection of their power-set variants (the
	 * NeverType results dropped) unless the estimate exceeds the limit;
	 * [$type] otherwise; UNDEF = pending exception */
	static zv::Val flattenTypes(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
			zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(type));
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(0);
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return a list of Type");
					return zv::Val();
				}
				zv::Val flattenTypes = TypeUtils::flattenTypes(innerType.raw());
				if (UNEXPECTED(flattenTypes.isUndef())) return zv::Val();
				for (zv::ArrayEntry flattened : zv::ArrRef(flattenTypes.raw())) {
					types.push(flattened.value());
				}
			}
			return zv::Val(std::move(types));
		}

		zv::Val constantArrays = pt_type_op(Z_OBJ_P(type), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(constantArrays.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getConstantArrays() must return array", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}
		if (zv::ArrRef(constantArrays.raw()).size() != 0) {
			/* Estimate the total number of power-set variants before
			 * expanding: each ConstantArrayType with N optional keys produces
			 * 2^N variants from getAllArrays(), multiplied across the
			 * constant arrays; bail out above the limit rather than allocate
			 * O(2^N). The twin's `16384 / max($arrayCount, 1)` is a float
			 * division — compared as one here. */
			zend_long estimatedCount = 1;
			bool bail = false;
			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zv::Ref constantArray = entry.value();
				if (UNEXPECTED(!constantArray.isObject())) {
					zend_type_error("phpstan_turbo: getConstantArrays() must return a list of ConstantArrayType");
					return zv::Val();
				}
				zv::Val optionalKeys = pt_type_call(Z_OBJ_P(constantArray.raw()), PT_LC("getoptionalkeys"), 0, NULL);
				if (UNEXPECTED(optionalKeys.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(optionalKeys.raw()).isArray())) {
					zend_type_error("phpstan_turbo: ConstantArrayType::getOptionalKeys() must return array");
					return zv::Val();
				}
				zend_long optionalCount = (zend_long) zv::ArrRef(optionalKeys.raw()).size();
				zend_long arrayCount = optionalCount <= 20 ? ((zend_long) 1 << optionalCount) : ZEND_LONG_MAX;
				if (arrayCount > 16384 || (double) estimatedCount > 16384.0 / (double) (arrayCount > 1 ? arrayCount : 1)) {
					bail = true;
					break;
				}
				estimatedCount *= arrayCount;
			}

			if (bail) return listOf(type);

			zv::Arr newTypes = zv::Arr::create(zv::ArrRef(constantArrays.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
				zv::Val allArrays = pt_type_call(Z_OBJ_P(entry.value().raw()), PT_LC("getallarrays"), 0, NULL);
				if (UNEXPECTED(allArrays.isUndef())) return zv::Val();
				newTypes.push(std::move(allArrays));
			}

			ConstantArrayCombinationContext context;
			if (UNEXPECTED(!pt_combinations_helper_for_each(newTypes.raw(), collectConstantArrayCombination, &context))) return zv::Val();
			return zv::Val(std::move(context.result));
		}

		return listOf(type);
	}

	/* the type itself for a ThisType, the first ThisType found in a union's
	 * or intersection's members, null otherwise; UNDEF = pending exception */
	static zv::Val findThisType(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_this_type)) return zv::Val::copyOf(zv::Ref(type));

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type) || instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			zv::Val innerTypes = getTypes(type);
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: getTypes() must return a list of Type");
					return zv::Val();
				}
				zv::Val thisType = findThisType(innerType.raw());
				if (UNEXPECTED(thisType.isUndef())) return zv::Val();
				if (!zv::Ref(thisType.raw()).isNull()) return thisType;
			}
		}

		return zv::Val::null();
	}

	/* the type itself when it is callable, the first callable member of a
	 * union, null otherwise; UNDEF = pending exception */
	static zv::Val findCallableType(zval *type)
	{
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(type));

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
			zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(type));
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return a list of Type");
					return zv::Val();
				}
				zv::Val callableType = findCallableType(innerType.raw());
				if (UNEXPECTED(callableType.isUndef())) return zv::Val();
				if (!zv::Ref(callableType.raw()).isNull()) return callableType;
			}
		}

		return zv::Val::null();
	}

	/* [$type] for a HasPropertyType, the HasPropertyTypes of a union's or
	 * intersection's members merged, [] otherwise; UNDEF = pending exception */
	static zv::Val getHasPropertyTypes(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_has_property_type)) return listOf(type);

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type) || instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
			zv::Val innerTypes = getTypes(type);
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			/* array_merge(...$hasPropertyTypes) over the lists: renumbered */
			zv::Arr hasPropertyTypes = zv::Arr::create(0);
			for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
				zv::Ref innerType = entry.value();
				if (UNEXPECTED(!innerType.isObject())) {
					zend_type_error("phpstan_turbo: getTypes() must return a list of Type");
					return zv::Val();
				}
				zv::Val inner = getHasPropertyTypes(innerType.raw());
				if (UNEXPECTED(inner.isUndef())) return zv::Val();
				for (zv::ArrayEntry found : zv::ArrRef(inner.raw())) {
					hasPropertyTypes.push(found.value());
				}
			}
			return zv::Val(std::move(hasPropertyTypes));
		}

		return zv::Val(zv::Arr::empty());
	}

	/* self::map(AccessoryType::class, $type, inspectIntersections: true,
	 * stopOnUnmatched: false); UNDEF = pending exception */
	static zv::Val getAccessoryTypes(zval *type)
	{
		zend_class_entry *accessoryType = pt_class(PT_CLASS_ACCESSORY_TYPE);
		if (UNEXPECTED(accessoryType == NULL)) return zv::Val();
		return map(accessoryType, type, true, false);
	}

	/* whether TypeTraverser::map() meets a TemplateType anywhere in the
	 * type (the traversal stops descending once one is found); false =
	 * pending exception */
	static bool containsTemplateType(zval *type, bool &out)
	{
		zval containsTemplateType;
		ZVAL_FALSE(&containsTemplateType);
		zv::Val callback = pt_type_native_callback(containsTemplateTypeCallback, &containsTemplateType, NULL);
		if (UNEXPECTED(callback.isUndef())) return false;
		zv::Val mapped;
		if (UNEXPECTED(!pt_type_traverser_map(mapped.raw(), type, callback.raw()))) return false;
		zval *state = pt_type_native_callback_state(callback.raw(), 0);
		out = Z_TYPE_P(state) == IS_TRUE;
		return true;
	}

	/* the type itself when it has no template or late-resolvable type,
	 * TypeTraverser::map($type, new LateResolvableTraverser($resolveUnresolvableTypes))
	 * otherwise; UNDEF = pending exception */
	static zv::Val resolveLateResolvableTypes(zval *type, bool resolveUnresolvableTypes)
	{
		zv::Val has = pt_type_op(Z_OBJ_P(type), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		if (!zend_is_true(has.raw())) return zv::Val::copyOf(zv::Ref(type));

		zval flag;
		ZVAL_BOOL(&flag, resolveUnresolvableTypes);
		zv::Val traverser = pt_type_new(PT_CLASS_LATE_RESOLVABLE_TRAVERSER, 1, &flag);
		if (UNEXPECTED(traverser.isUndef())) return zv::Val();
		zv::Val mapped;
		if (UNEXPECTED(!pt_type_traverser_map(mapped.raw(), type, traverser.raw()))) return zv::Val();
		return mapped;
	}

private:
	/* [$type] */
	static zv::Val listOf(zval *type)
	{
		zv::Arr list = zv::Arr::create(1);
		list.push(zv::Ref(type));
		return zv::Val(std::move(list));
	}

	/* $type->getTypes() of a union or intersection through its class entry,
	 * checked to be an array; UNDEF = pending exception */
	static zv::Val getTypes(zval *type)
	{
		zv::Val types = instanceof_function(Z_OBJCE_P(type), pt_ce_union_type) ? pt_union_type_get_types(Z_OBJ_P(type)) : pt_type_call(Z_OBJ_P(type), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getTypes() must return array", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}
		return types;
	}

	/* containsTemplateType()'s `static function (Type $type, callable
	 * $traverse) use (&$containsTemplateType)`: a TemplateType sets the flag;
	 * once set the type is returned as is, else the traversal descends */
	static void containsTemplateTypeCallback(zval *containsTemplateType, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc != 2 || Z_TYPE_P(&argv[0]) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: TypeTraverser::map() must call back with a Type and the traverse callable");
			return;
		}
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(&argv[0], PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
		if (isTemplate) {
			ZVAL_TRUE(containsTemplateType);
		}
		if (Z_TYPE_P(containsTemplateType) == IS_TRUE) {
			ZVAL_COPY(return_value, &argv[0]);
			return;
		}
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, &argv[0]);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeUtils;

zv::Val pt_type_utils_find_callable_type(zval *type)
{
	return TypeUtils::findCallableType(type);
}

zv::Val pt_type_utils_flatten_types(zval *type)
{
	return TypeUtils::flattenTypes(type);
}

bool pt_type_utils_contains_template_type(zval *type, bool &out)
{
	return TypeUtils::containsTemplateType(type, out);
}

zv::Val pt_type_utils_get_integer_ranges(zval *type)
{
	return TypeUtils::getIntegerRanges(type);
}

zv::Val pt_type_utils_resolve_late_resolvable_types(zval *type)
{
	return TypeUtils::resolveLateResolvableTypes(type, true);
}

zv::Val pt_type_utils_resolve_late_resolvable_types_ex(zval *type, bool resolveUnresolvableTypes)
{
	return TypeUtils::resolveLateResolvableTypes(type, resolveUnresolvableTypes);
}

zv::Val pt_type_utils_find_this_type(zval *type)
{
	return TypeUtils::findThisType(type);
}

/* {{{ engine ABI glue: parameter parsing + registration */

/* one handler per Type-taking static returning through fn */
#define PT_TYPE_UTILS_UNARY(fn) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *type; \
		ZEND_PARSE_PARAMETERS_START(1, 1) \
			Z_PARAM_OBJECT(type) \
		ZEND_PARSE_PARAMETERS_END(); \
		PT_RETURN_VAL(TypeUtils::fn(type)); \
	}

void pt_register_type_utils()
{
	reg::Class cls("PHPStan\\Type\\TypeUtils");
	ptdecl::TypeUtils::declareClass(cls);
	ptdecl::TypeUtils::declareProperties(cls);

	static const reg::Arg nullableThisType = reg::obj("", "PHPStan\\Type\\ThisType", true);

	cls.method("getConstantIntegers", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(getConstantIntegers), &ptret::array);
	cls.method("getIntegerRanges", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(getIntegerRanges), &ptret::array);
	cls.method("toBenevolentUnion", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(toBenevolentUnion), &ptret::type);
	cls.method("toStrictUnion", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(toStrictUnion), &ptret::type);
	cls.method("flattenTypes", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(flattenTypes), &ptret::array);
	cls.method("findThisType", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(findThisType), &nullableThisType);
	cls.method("findCallableType", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(findCallableType), &ptret::nullableType);
	cls.method("getHasPropertyTypes", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(getHasPropertyTypes), &ptret::array);
	cls.method("getAccessoryTypes", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, PT_TYPE_UTILS_UNARY(getAccessoryTypes), &ptret::array);

	cls.method(sigs::containsTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		bool contains;
		if (UNEXPECTED(!TypeUtils::containsTemplateType(type, contains))) RETURN_THROWS();
		RETURN_BOOL(contains);
	});

	cls.method(sigs::resolveLateResolvableTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool resolveUnresolvableTypes = true;
		if (!zp::parse<zp::Obj, zp::Opt<zp::Bool>>(execute_data, type, resolveUnresolvableTypes)) RETURN_THROWS();
		PT_RETURN_VAL(TypeUtils::resolveLateResolvableTypes(type, resolveUnresolvableTypes));
	});

	cls.shadow(&pt_ce_type_utils);
}

/* }}} */
