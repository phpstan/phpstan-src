/*
 * PHPStanTurbo\TypehintHelper — native implementation of
 * PHPStan\Type\TypehintHelper.
 *
 * Declared as PHPStan\Type\TypehintHelper itself at activation: final,
 * static helpers only, no state.
 *
 * The reflection types are the BetterReflection adapters (the twin's
 * `instanceof ReflectionUnionType` names the adapter, not the core class —
 * a core reflection type takes the "Unexpected type" throw, natively too);
 * they, the parser name nodes and ParserNodeTypeToPHPStanType go through
 * the class map. The Type classes the twin names (ArrayType,
 * ConstantArrayType, MixedType, NeverType, ErrorType, BenevolentUnionType,
 * UnionType, IterableType) are tested through the class entries the native
 * code holds.
 */

#include "TypeTraits.h"
#include "generated/TypehintHelper.h"

namespace sigs = ptdecl::TypehintHelper::sig;

zend_class_entry *pt_ce_typehint_helper = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\TypehintHelper. */
class TypehintHelper
{
public:
	/* decideTypeFromReflection(?ReflectionType $reflectionType, ?Type
	 * $phpDocType = null, ClassReflection|null $selfClass = null, bool
	 * $isVariadic = false): the PHPDoc type (its item type for a variadic
	 * array) or mixed without a reflection type; a union/intersection
	 * reflection type combined member by member; a named type resolved
	 * through ParserNodeTypeToPHPStanType (nullable added back) — each
	 * decided against the PHPDoc type; the null arguments NULL or IS_NULL;
	 * UNDEF = pending exception */
	static zv::Val decideTypeFromReflection(zval *reflectionType, zval *phpDocType, zval *selfClass, bool isVariadic)
	{
		if (isNull(reflectionType)) {
			/* $isVariadic && ($phpDocType instanceof ArrayType || $phpDocType
			 * instanceof ConstantArrayType) → $phpDocType->getItemType() */
			if (isVariadic && !isNull(phpDocType)) {
				if (instanceof_function(Z_OBJCE_P(phpDocType), pt_ce_array_type)) return pt_array_type_get_item_type(Z_OBJ_P(phpDocType));
				if (instanceof_function(Z_OBJCE_P(phpDocType), pt_ce_constant_array_type)) {
					return pt_type_call(Z_OBJ_P(phpDocType), PT_LC("getitemtype"), 0, NULL);
				}
			}
			/* $phpDocType ?? new MixedType() */
			if (!isNull(phpDocType)) return zv::Val::copyOf(zv::Ref(phpDocType));
			return pt_type_new_mixed_type();
		}

		zend_object *reflection = Z_OBJ_P(reflectionType);
		bool isUnion;
		if (UNEXPECTED(!pt_type_instanceof(reflectionType, PT_CLASS_REFLECTION_UNION_TYPE, isUnion))) return zv::Val();
		if (isUnion) {
			/* TypeCombinator::union(...array_map(static fn (ReflectionType $type): Type =>
			 * self::decideTypeFromReflection($type, selfClass: $selfClass), $reflectionType->getTypes())) */
			zv::Val innerReflectionTypes = reflectionTypes(reflection);
			if (UNEXPECTED(innerReflectionTypes.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(zv::ArrRef(innerReflectionTypes.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(innerReflectionTypes.raw())) {
				zv::Val inner = decideTypeFromReflection(entry.value().raw(), NULL, selfClass, false);
				if (UNEXPECTED(inner.isUndef())) return zv::Val();
				types.push(std::move(inner));
			}
			zv::Val type = pt_type_combinator_call_spread(PT_LC("union"), Z_ARRVAL_P(types.raw()));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			return decideType(type.raw(), phpDocType);
		}

		bool isIntersection;
		if (UNEXPECTED(!pt_type_instanceof(reflectionType, PT_CLASS_REFLECTION_INTERSECTION_TYPE, isIntersection))) return zv::Val();
		if (isIntersection) {
			zv::Val innerReflectionTypes = reflectionTypes(reflection);
			if (UNEXPECTED(innerReflectionTypes.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(zv::ArrRef(innerReflectionTypes.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(innerReflectionTypes.raw())) {
				zv::Val innerType = decideTypeFromReflection(entry.value().raw(), NULL, selfClass, false);
				if (UNEXPECTED(innerType.isUndef())) return zv::Val();
				/* !$innerType->isObject()->yes() → new NeverType() */
				zend_long isObject = pt_type_call_trinary(Z_OBJ_P(innerType.raw()), PT_LC("isobject"), 0, NULL);
				if (UNEXPECTED(isObject < 0)) return zv::Val();
				if (isObject != PT_TRI_YES) return pt_type_new_never_type();
				types.push(std::move(innerType));
			}
			zv::Val intersected = pt_type_combinator_call_spread(PT_LC("intersect"), Z_ARRVAL_P(types.raw()));
			if (UNEXPECTED(intersected.isUndef())) return zv::Val();
			return decideType(intersected.raw(), phpDocType);
		}

		bool isNamed;
		if (UNEXPECTED(!pt_type_instanceof(reflectionType, PT_CLASS_REFLECTION_NAMED_TYPE, isNamed))) return zv::Val();
		if (!isNamed) {
			/* throw new ShouldNotHappenException(sprintf('Unexpected type: %s', get_class($reflectionType))) */
			zend_class_entry *shouldNotHappen = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
			if (UNEXPECTED(shouldNotHappen == NULL)) return zv::Val();
			zend_throw_exception_ex(shouldNotHappen, 0, "Unexpected type: %s", ZSTR_VAL(reflection->ce->name));
			return zv::Val();
		}

		/* new Identifier($name) for an identifier, new FullyQualified($name)
		 * otherwise */
		zv::Val isIdentifier = pt_type_call(reflection, PT_LC("isidentifier"), 0, NULL);
		if (UNEXPECTED(isIdentifier.isUndef())) return zv::Val();
		zv::Val name = pt_type_call(reflection, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::getName() must return string", ZSTR_VAL(reflection->ce->name));
			return zv::Val();
		}
		zv::Val typeNode = pt_type_new(zend_is_true(isIdentifier.raw()) ? PT_CLASS_IDENTIFIER : PT_CLASS_FULLY_QUALIFIED, 1, name.raw());
		if (UNEXPECTED(typeNode.isUndef())) return zv::Val();

		/* ParserNodeTypeToPHPStanType::resolve($typeNode, $selfClass) */
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], typeNode.raw());
		if (isNull(selfClass)) {
			ZVAL_NULL(&args[1]);
		} else {
			ZVAL_COPY_VALUE(&args[1], selfClass);
		}
		zv::Val type = pt_type_call_static(PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE, PT_LC("resolve"), 2, args);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(type.raw()).isObject())) {
			zend_type_error("phpstan_turbo: ParserNodeTypeToPHPStanType::resolve() must return a Type");
			return zv::Val();
		}

		/* $reflectionType->allowsNull() → TypeCombinator::addNull($type) */
		zv::Val allowsNull = pt_type_call(reflection, PT_LC("allowsnull"), 0, NULL);
		if (UNEXPECTED(allowsNull.isUndef())) return zv::Val();
		if (zend_is_true(allowsNull.raw())) {
			type = combinator1(PT_LC("addnull"), type.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		return decideType(type.raw(), phpDocType);
	}

	/* decideType(Type $type, ?Type $phpDocType): the native type, or the
	 * PHPDoc type where it refines the native one (a PHPDoc null dropped for
	 * a non-nullable native type; an explicit never or a void over implicit
	 * mixed taken as is; array PHPDoc types over an iterable native type
	 * loosened to iterable; the members of a native union the PHPDoc type
	 * does not cover added back, a native null kept); $phpDocType NULL or
	 * IS_NULL for null; UNDEF = pending exception */
	static zv::Val decideType(zval *typeIn, zval *phpDocTypeIn)
	{
		zv::Val type = zv::Val::copyOf(zv::Ref(typeIn));
		zv::Val phpDocType = isNull(phpDocTypeIn) ? zv::Val::null() : zv::Val::copyOf(zv::Ref(phpDocTypeIn));

		/* $phpDocType !== null && $type->isNull()->no() → removeNull($phpDocType) */
		if (!zv::Ref(phpDocType.raw()).isNull()) {
			zend_long isNullTrinary = pt_type_op_trinary(Z_OBJ_P(type.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNullTrinary < 0)) return zv::Val();
			if (isNullTrinary == PT_TRI_NO) {
				phpDocType = combinator1(PT_LC("removenull"), phpDocType.raw());
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
			}
		}
		if (instanceof_function(Z_OBJCE_P(type.raw()), pt_ce_benevolent_union_type)) return type;

		if (!zv::Ref(phpDocType.raw()).isNull() && !instanceof_function(Z_OBJCE_P(phpDocType.raw()), pt_ce_error_type)) {
			/* $phpDocType instanceof NeverType && $phpDocType->isExplicit() */
			if (instanceof_function(Z_OBJCE_P(phpDocType.raw()), pt_ce_never_type)) {
				bool isExplicit;
				if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(phpDocType.raw()), isExplicit))) return zv::Val();
				if (isExplicit) return phpDocType;
			}
			/* $type instanceof MixedType && !$type->isExplicitMixed() && $phpDocType->isVoid()->yes() */
			if (instanceof_function(Z_OBJCE_P(type.raw()), pt_ce_mixed_type)) {
				bool explicitMixed;
				if (UNEXPECTED(!isExplicitMixed(type.raw(), explicitMixed))) return zv::Val();
				if (!explicitMixed) {
					zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(phpDocType.raw()), PT_OP_IS_VOID, 0, NULL);
					if (UNEXPECTED(isVoid < 0)) return zv::Val();
					if (isVoid == PT_TRI_YES) return phpDocType;
				}
			}

			/* TypeCombinator::removeNull($type) instanceof IterableType */
			zv::Val withoutNull = combinator1(PT_LC("removenull"), type.raw());
			if (UNEXPECTED(withoutNull.isUndef())) return zv::Val();
			if (zv::Ref(withoutNull.raw()).isObject() && instanceof_function(Z_OBJCE_P(withoutNull.raw()), pt_ce_iterable_type)) {
				if (instanceof_function(Z_OBJCE_P(phpDocType.raw()), pt_ce_union_type)) {
					zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(phpDocType.raw()));
					if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
					if (UNEXPECTED(!zv::Ref(innerTypes.raw()).isArray())) {
						zend_type_error("phpstan_turbo: UnionType::getTypes() must return array");
						return zv::Val();
					}
					zv::Arr newInnerTypes = zv::Arr::create(zv::ArrRef(innerTypes.raw()).size());
					for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
						zv::Ref innerType = entry.value();
						if (UNEXPECTED(!innerType.isObject())) {
							zend_type_error("phpstan_turbo: UnionType::getTypes() must return a list of Type");
							return zv::Val();
						}
						bool mixedKeyedArray;
						if (UNEXPECTED(!isArrayWithMixedKey(innerType.raw(), mixedKeyedArray))) return zv::Val();
						if (mixedKeyedArray) {
							/* new IterableType($innerType->getIterableKeyType(), $innerType->getItemType()) */
							zv::Val keyType = pt_type_op(Z_OBJ_P(innerType.raw()), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
							if (UNEXPECTED(keyType.isUndef())) return zv::Val();
							zv::Val itemType = pt_array_type_get_item_type(Z_OBJ_P(innerType.raw()));
							if (UNEXPECTED(itemType.isUndef())) return zv::Val();
							zval iterable;
							if (UNEXPECTED(!pt_iterable_type_new(&iterable, keyType.raw(), itemType.raw()))) return zv::Val();
							newInnerTypes.push(zv::Val::adopt(iterable));
						} else {
							newInnerTypes.push(innerType);
						}
					}
					/* new UnionType($innerTypes) */
					zval unionType;
					if (UNEXPECTED(!pt_union_type_new(&unionType, newInnerTypes.raw()))) return zv::Val();
					phpDocType = zv::Val::adopt(unionType);
				} else {
					bool mixedKeyedArray;
					if (UNEXPECTED(!isArrayWithMixedKey(phpDocType.raw(), mixedKeyedArray))) return zv::Val();
					if (mixedKeyedArray) {
						/* new IterableType($phpDocType->getKeyType(), $phpDocType->getItemType()) */
						zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(phpDocType.raw()));
						if (UNEXPECTED(keyType.isUndef())) return zv::Val();
						zv::Val itemType = pt_array_type_get_item_type(Z_OBJ_P(phpDocType.raw()));
						if (UNEXPECTED(itemType.isUndef())) return zv::Val();
						zval iterable;
						if (UNEXPECTED(!pt_iterable_type_new(&iterable, keyType.raw(), itemType.raw()))) return zv::Val();
						phpDocType = zv::Val::adopt(iterable);
					}
				}
			}

			bool phpDocDecides;
			if (UNEXPECTED(!decidesForPhpDoc(type.raw(), phpDocType.raw(), phpDocDecides))) return zv::Val();
			zv::Val resultType = phpDocDecides ? zv::Val::copyOf(zv::Ref(phpDocType.raw())) : zv::Val::copyOf(zv::Ref(type.raw()));

			if (instanceof_function(Z_OBJCE_P(type.raw()), pt_ce_union_type)) {
				/* the members of the native union the result does not cover
				 * (isSuperTypeOf($resultType)->no()) are unioned back in */
				zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(type.raw()));
				if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(innerTypes.raw()).isArray())) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return array");
					return zv::Val();
				}
				zv::Arr unionArguments = zv::Arr::create(1 + zv::ArrRef(innerTypes.raw()).size());
				unionArguments.push(zv::Ref(resultType.raw()));
				uint32_t addToUnionTypes = 0;
				for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
					zv::Ref innerType = entry.value();
					if (UNEXPECTED(!innerType.isObject())) {
						zend_type_error("phpstan_turbo: UnionType::getTypes() must return a list of Type");
						return zv::Val();
					}
					zv::Val isSuperType = pt_type_op(Z_OBJ_P(innerType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, resultType.raw());
					if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
					zend_long trinary = pt_type_result_trinary(isSuperType.raw());
					if (UNEXPECTED(trinary < 0)) return zv::Val();
					if (trinary != PT_TRI_NO) continue;
					unionArguments.push(innerType);
					addToUnionTypes++;
				}
				if (addToUnionTypes > 0) {
					/* TypeCombinator::union($resultType, ...$addToUnionTypes) */
					type = pt_type_combinator_call_spread(PT_LC("union"), Z_ARRVAL_P(unionArguments.raw()));
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				} else {
					type = std::move(resultType);
				}
			} else {
				/* TypeCombinator::containsNull($type) → addNull($resultType) */
				zv::Val containsNull = pt_type_combinator_call(PT_LC("containsnull"), 1, type.raw());
				if (UNEXPECTED(containsNull.isUndef())) return zv::Val();
				if (zend_is_true(containsNull.raw())) {
					type = combinator1(PT_LC("addnull"), resultType.raw());
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				} else {
					type = std::move(resultType);
				}
			}
		}

		return type;
	}

private:
	/* a NULL pointer or a null zval — the twins' `=== null` */
	static bool isNull(zval *value) { return value == NULL || Z_TYPE_P(value) == IS_NULL; }

	/* TypeCombinator::<method>($type); UNDEF = pending exception */
	static zv::Val combinator1(const char *lcname, size_t len, zval *type)
	{
		return pt_type_combinator_call(lcname, len, 1, type);
	}

	/* $reflectionType->getTypes(), checked to be an array of objects; UNDEF
	 * = pending exception */
	static zv::Val reflectionTypes(zend_object *reflection)
	{
		zv::Val types = pt_type_call(reflection, PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getTypes() must return array", ZSTR_VAL(reflection->ce->name));
			return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			if (UNEXPECTED(!entry.value().isObject())) {
				zend_type_error("phpstan_turbo: %s::getTypes() must return a list of ReflectionType", ZSTR_VAL(reflection->ce->name));
				return zv::Val();
			}
		}
		return types;
	}

	/* $type->isExplicitMixed() of a MixedType, through its class entry;
	 * false = pending exception */
	[[nodiscard]] static bool isExplicitMixed(zval *type, bool &out)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $t instanceof ArrayType && $t->getKeyType()->describe(VerbosityLevel::typeOnly()) === 'mixed';
	 * false = pending exception */
	[[nodiscard]] static bool isArrayWithMixedKey(zval *type, bool &out)
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_array_type)) {
			out = false;
			return true;
		}
		zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(type));
		if (UNEXPECTED(keyType.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(keyType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: ArrayType::getKeyType() must return a Type");
			return false;
		}
		zval *level = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_TYPE_ONLY);
		if (UNEXPECTED(level == NULL)) return false;
		zv::Val description = pt_type_op(Z_OBJ_P(keyType.raw()), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return false;
		out = zv::Ref(description.raw()).isString() && zend_string_equals_literal(Z_STR_P(description.raw()), "mixed");
		return true;
	}

	/* ($type->isCallable()->yes() && $phpDocType->isCallable()->yes())
	 * || ((!$phpDocType instanceof NeverType || ($type instanceof MixedType && !$type->isExplicitMixed()))
	 *     && $type->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocType))->yes())
	 * — short-circuited as the twin evaluates it; false = pending exception */
	[[nodiscard]] static bool decidesForPhpDoc(zval *type, zval *phpDocType, bool &out)
	{
		zend_long typeCallable = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(typeCallable < 0)) return false;
		if (typeCallable == PT_TRI_YES) {
			zend_long phpDocCallable = pt_type_op_trinary(Z_OBJ_P(phpDocType), PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(phpDocCallable < 0)) return false;
			if (phpDocCallable == PT_TRI_YES) {
				out = true;
				return true;
			}
		}

		bool neverAllowed = true;
		if (instanceof_function(Z_OBJCE_P(phpDocType), pt_ce_never_type)) {
			neverAllowed = false;
			if (instanceof_function(Z_OBJCE_P(type), pt_ce_mixed_type)) {
				bool explicitMixed;
				if (UNEXPECTED(!isExplicitMixed(type, explicitMixed))) return false;
				neverAllowed = !explicitMixed;
			}
		}
		if (!neverAllowed) {
			out = false;
			return true;
		}

		zv::Val bounds = pt_type_template_type_helper_resolve_to_bounds(phpDocType);
		if (UNEXPECTED(bounds.isUndef())) return false;
		zv::Val isSuperType = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, bounds.raw());
		if (UNEXPECTED(isSuperType.isUndef())) return false;
		zend_long trinary = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(trinary < 0)) return false;
		out = trinary == PT_TRI_YES;
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypehintHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_typehint_helper()
{
	reg::Class cls("PHPStan\\Type\\TypehintHelper");
	ptdecl::TypehintHelper::declareClass(cls);
	ptdecl::TypehintHelper::declareProperties(cls);

	cls.method(sigs::decideTypeFromReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionType, *phpDocType = NULL, *selfClass = NULL;
		bool isVariadic = false;
		if (!zp::parse<zp::ObjOrNull, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::Bool>>(execute_data, reflectionType, phpDocType, selfClass, isVariadic)) RETURN_THROWS();
		PT_RETURN_VAL(TypehintHelper::decideTypeFromReflection(reflectionType, phpDocType, selfClass, isVariadic));
	});

	cls.method(sigs::decideType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpDocType;
		if (!zp::parse<zp::Obj, zp::ObjOrNull>(execute_data, type, phpDocType)) RETURN_THROWS();
		PT_RETURN_VAL(TypehintHelper::decideType(type, phpDocType));
	});

	cls.shadow(&pt_ce_typehint_helper);
}

/* }}} */
