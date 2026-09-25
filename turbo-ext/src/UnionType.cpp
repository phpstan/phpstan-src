/*
 * PHPStanTurbo\UnionType — native implementation of PHPStan\Type\UnionType.
 *
 * Declared as PHPStan\Type\UnionType itself at activation: not final (the
 * native BenevolentUnionType and the PHP TemplateUnionType extend it — their
 * constructors call parent::__construct(), so the constructor is a proper
 * method), implementing PHPStan\Type\CompoundType. State is the twin's
 * eight private properties, declared typed property slots in the twin's
 * declaration order — the six memos ($sortedTypesCache, $cachedDescriptions,
 * $finiteTypeSet, $finiteTypes, $isNull, $isCallable) first, the promoted
 * $types and $normalized after them — so the std object handlers do
 * GC/clone and a PHP subclass's own properties follow them. The one trait
 * the twin uses (NonGeneralizableTypeTrait) comes from the shared registrar
 * in TypeTraits.cpp, run after the class's own methods.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (BenevolentUnionType's
 * describe(), unionResults(), unionTypes() and pickFromTypes();
 * TemplateUnionType's accepts() and describe()) — with a direct C++ call
 * when the method is this class's own (the fast path for a
 * TemplateUnionType too, which overrides only what its trait declares) and
 * a direct call into BenevolentUnionType.cpp when it is the benevolent
 * override. The `static fn (Type $type) => $type->x(...)` closures the twin
 * hands to those three protected methods are UnionMemberOp values
 * (TypeTraits.h): applied natively on the fast paths, and wrapped into a
 * PHPStanTurbo\NativeCallback only when a PHP subclass overrides the method
 * they are passed to.
 *
 * The private slots of another UnionType (`$type->types`) are read
 * directly, as the twin does from inside the class; FiniteTypeSet and
 * UnionTypeHelper stay PHP and are called through the class map.
 */

#include "TypeTraits.h"
#include "generated/UnionType.h"

namespace slots = ptdecl::UnionType::slot;
namespace sigs = ptdecl::UnionType::sig;

zend_class_entry *pt_ce_union_type = nullptr;

/* the twin's inline limit of described members in describe() */
#define PT_UT_DESCRIBE_TYPES_LIMIT 1024

/* AcceptsResult.cpp: the result objects' slots and the reasons merge both
 * result twins share */
#define PT_RESULT_PROP_RESULT 0
#define PT_RESULT_PROP_REASONS 1
zval *pt_result_array_slot(zend_object *object, uint32_t slot, const char *propertyName);
bool pt_reasons_merge(zval *result, zval *const *arrays, uint32_t count, bool mergeKeys, bool unique);

/* the handlers of the methods the twin calls on $this and a subclass may
 * override — registered under these names so the fast-path identity test
 * (pt_type_method_is) has a pointer to compare against */
static void ZEND_FASTCALL utGetTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utGetFiniteTypeSet(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utGetSortedTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utAccepts(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utDescribe(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utIsEnum(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utIsInteger(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utGetEnumCases(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utUnionResults(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utUnionTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL utPickFromTypes(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* the instanceof checks the twin makes, as helpers: a class-map class
 * (false = pending exception) or a shadowed class the native code holds */
[[nodiscard]] static bool isInstance(zval *value, int classIdx, bool &out)
{
	return pt_type_instanceof(value, classIdx, out);
}

static bool isInstance(zval *value, zend_class_entry *ce, bool &out)
{
	out = Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* $object->method(...) that returns a Type: the result checked to be an
 * object (the engine's return check of the PHP twin); UNDEF = pending
 * exception */
static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_type(object, lcname, len, argc, argv);
}

/* the PT_TRI_* value of a TrinaryLogic-valued zv::Val (UNDEF = pending
 * exception → -1) */
static zend_long trinaryOf(zv::Val value)
{
	if (UNEXPECTED(value.isUndef())) return -1;
	return pt_type_trinary_value(value.raw());
}

/* TypeCombinator::union(...$types) over a PHP array of types */
static zv::Val combinatorUnion(HashTable *types)
{
	return pt_type_combinator_call_spread(PT_LC("union"), types);
}

/* TypeCombinator::<method>($a, $b); UNDEF = pending exception */
static zv::Val combinator2(const char *lcname, size_t len, zval *a, zval *b)
{
	zv::Args args{a, b};
	return pt_type_combinator_call(lcname, len, 2, args);
}

/* throw new ShouldNotHappenException($message) ($message NULL = the
 * default) */
static void throwShouldNotHappen(zval *message)
{
	if (message == NULL) {
		pt_throw_should_not_happen();
		return;
	}
	zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, message);
	if (UNEXPECTED(exception.isUndef())) return;
	zval raw = exception.take();
	zend_throw_exception_object(&raw);
}

/* throw new <class-map exception>(...$args) */
static void throwMapped(int classIdx, uint32_t argc, zval *argv)
{
	zv::Val exception = pt_type_new(classIdx, argc, argv);
	if (UNEXPECTED(exception.isUndef())) return;
	zval raw = exception.take();
	zend_throw_exception_object(&raw);
}

/* Class::NAME of a class-map class — a literal class constant, borrowed;
 * NULL = pending exception */
[[nodiscard]] static zval *classConstant(int classIdx, const char *name, size_t len)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return NULL;
	zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
	if (UNEXPECTED(constant == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
	return &constant->value;
}

/* VerbosityLevel::<factory>() for a PT_VERBOSITY_LEVEL_* value (the
 * shadowing class's singleton, VerbosityLevel.cpp) */
static zv::Val verbosityLevel(zend_long value)
{
	return pt_type_verbosity_level(value);
}

/* new ErrorType() / new NeverType() */
static zv::Val errorType() { return pt_type_new_error_type(); }
static zv::Val neverType() { return pt_type_new_never_type(); }

/* the value of `$str1 === $str2` for two zvals holding strings */
static bool sameString(zval *a, zval *b)
{
	return Z_TYPE_P(a) == IS_STRING && Z_TYPE_P(b) == IS_STRING && zend_string_equals(Z_STR_P(a), Z_STR_P(b));
}

/* the spread of a PHP array into an argument vector (the packed table's own
 * slots when it has no holes, an emalloc'd copy otherwise) */
struct SpreadArgs
{
	zval *argv;
	uint32_t count;
	bool owned;

	explicit SpreadArgs(HashTable *args)
	{
		count = zend_hash_num_elements(args);
		if (EXPECTED(HT_IS_PACKED(args) && HT_IS_WITHOUT_HOLES(args))) {
			argv = args->arPacked;
			owned = false;
			return;
		}
		argv = (zval *) safe_emalloc(count, sizeof(zval), 0);
		uint32_t i = 0;
		for (zv::ArrayEntry entry : zv::TableRef(args)) {
			ZVAL_COPY_VALUE(&argv[i++], entry.value().raw());
		}
		count = i;
		owned = true;
	}

	SpreadArgs(const SpreadArgs &) = delete;
	SpreadArgs &operator=(const SpreadArgs &) = delete;

	~SpreadArgs()
	{
		if (owned) {
			efree(argv);
		}
	}
};

/* AcceptsResult::createYes()/createMaybe()/createNo() */
static zv::Val acceptsResult(zend_long value) { return pt_type_accepts_result(value); }
static zv::Val isSuperTypeOfResult(zend_long value) { return pt_type_is_super_type_of_result(value); }

/* $a->or($b) on two AcceptsResults: natively for two native ones (the
 * value fold, the merged and deduplicated reasons), through the method
 * otherwise; UNDEF = pending exception */
static zv::Val acceptsOr(zv::Val a, zval *b)
{
	if (UNEXPECTED(a.isUndef() || b == NULL)) return zv::Val();
	if (EXPECTED(Z_TYPE_P(a.raw()) == IS_OBJECT && Z_OBJCE_P(a.raw()) == pt_ce_accepts_result && Z_TYPE_P(b) == IS_OBJECT && Z_OBJCE_P(b) == pt_ce_accepts_result)) {
		zend_long left = pt_result_value(Z_OBJ_P(a.raw()));
		if (UNEXPECTED(left < 0)) return zv::Val();
		zend_long right = pt_result_value(Z_OBJ_P(b));
		if (UNEXPECTED(right < 0)) return zv::Val();
		zval *leftReasons = pt_result_array_slot(Z_OBJ_P(a.raw()), PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(leftReasons == NULL)) return zv::Val();
		zval *rightReasons = pt_result_array_slot(Z_OBJ_P(b), PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(rightReasons == NULL)) return zv::Val();
		zval *arrays[2] = { leftReasons, rightReasons };
		zval reasons;
		if (UNEXPECTED(!pt_reasons_merge(&reasons, arrays, 2, true, true))) return zv::Val();
		zval result;
		if (UNEXPECTED(!pt_accepts_result_create(&result, pt_trinary_singleton(left | right), &reasons))) return zv::Val();
		return zv::Val::adopt(result);
	}
	if (UNEXPECTED(!zv::Ref(a.raw()).isObject())) {
		zend_type_error("phpstan_turbo: expected %s, %s given", ZSTR_VAL(pt_ce_accepts_result->name), zend_zval_value_name(a.raw()));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(a.raw()), PT_LC("or"), 1, b);
}

/* sprintf('Type #%d from the union: %s', $index, $reason) */
static zv::Val decoratedReason(zend_long index, zend_string *reason)
{
	smart_str str = {NULL, 0};
	smart_str_appendl(&str, "Type #", 6);
	smart_str_append_long(&str, index);
	smart_str_appendl(&str, " from the union: ", sizeof(" from the union: ") - 1);
	smart_str_append(&str, reason);
	smart_str_0(&str);
	return zv::Val::adoptString(str.s);
}

/* the decorateReasons() callback of accepts() as a PHP callable, for a
 * result object that is not the native AcceptsResult; state0 = the index */
static void decorateReasonCallback(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc != 1 || Z_TYPE_P(&argv[0]) != IS_STRING)) {
		zend_type_error("phpstan_turbo: the decorateReasons() callback expects one string");
		return;
	}
	decoratedReason(Z_LVAL_P(state0), Z_STR_P(&argv[0])).intoReturnValue(return_value);
}

/* $innerResult->decorateReasons(static fn (string $reason) => sprintf('Type
 * #%d from the union: %s', $index, $reason)); UNDEF = pending exception */
static zv::Val decorateReasons(zval *result, zend_long index)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_accepts_result)) {
		zval *reasons = pt_result_array_slot(Z_OBJ_P(result), PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(reasons == NULL)) return zv::Val();
		zv::Arr decorated = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(reasons)));
		for (zv::ArrayEntry entry : zv::ArrRef(reasons)) {
			zv::Ref reason = entry.value().deref();
			if (UNEXPECTED(!reason.isString())) {
				zend_type_error("phpstan_turbo: a reason must be a string");
				return zv::Val();
			}
			decorated.push(decoratedReason(index, reason.asString()));
		}
		zval created;
		zval reasonsRaw = decorated.take();
		if (UNEXPECTED(!pt_accepts_result_create(&created, OBJ_PROP_NUM(Z_OBJ_P(result), PT_RESULT_PROP_RESULT), &reasonsRaw))) return zv::Val();
		return zv::Val::adopt(created);
	}
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", ZSTR_VAL(pt_ce_accepts_result->name), zend_zval_value_name(result));
		return zv::Val();
	}
	zv::Val indexZv = zv::Val::integer(index);
	zv::Val callback = pt_type_native_callback(decorateReasonCallback, indexZv.raw(), NULL);
	if (UNEXPECTED(callback.isUndef())) return zv::Val();
	return pt_type_call(Z_OBJ_P(result), PT_LC("decoratereasons"), 1, callback.raw());
}

/* ->reasons of a result object (borrowed): the native slot, the public
 * property of anything else; NULL = pending exception (*holder then owns
 * the property read) */
[[nodiscard]] static zval *resultReasons(zval *result, zval *holder)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_accepts_result)) {
		return pt_result_array_slot(Z_OBJ_P(result), PT_RESULT_PROP_REASONS, "reasons");
	}
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", ZSTR_VAL(pt_ce_accepts_result->name), zend_zval_value_name(result));
		return NULL;
	}
	zval *reasons = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("reasons"), 0, holder);
	if (UNEXPECTED(reasons == NULL || EG(exception))) return NULL;
	ZVAL_DEREF(reasons);
	if (UNEXPECTED(Z_TYPE_P(reasons) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: ->reasons must be an array");
		return NULL;
	}
	return reasons;
}

/* Mirrors PHPStan\Type\UnionType. State lives in the PHP object's slots. */
class UnionType
{
public:
	explicit UnionType(zend_object *self) : self(self) {}

	/* __construct(private array $types, private bool $normalized = false):
	 * the promoted properties are written first (as the engine does), then
	 * fewer than two members or a member that is a UnionType but no
	 * TemplateType throws; false = pending exception */
	[[nodiscard]] bool construct(zval *typesArg, bool normalized)
	{
		zval *typesSlot = OBJ_PROP_NUM(self, slots::types);
		zval *normalizedSlot = OBJ_PROP_NUM(self, slots::normalized);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, typesSlot);
		ZVAL_COPY(typesSlot, typesArg);
		ZVAL_BOOL(normalizedSlot, normalized);
		Z_PROP_FLAG_P(typesSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(normalizedSlot) = 0;
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}

		if (zend_hash_num_elements(Z_ARRVAL_P(typesArg)) < 2) {
			throwCannotCreate(typesArg);
			return false;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(typesArg)) {
			zval *type = entry.value().deref().raw();
			/* a member that is not an object is rejected here: the twin
			 * stores it unchecked and fails at its first use, but the
			 * native code reading the members — in this family and in
			 * every port iterating getTypes() — relies on objects */
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: %s::__construct(): every member must be a %s, %s given", ZSTR_VAL(pt_ce_union_type->name), ptcls::type, zend_zval_value_name(type));
				return false;
			}
			if (!instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) continue;
			bool isTemplate;
			if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return false;
			if (isTemplate) continue;
			throwCannotCreate(typesArg);
			return false;
		}
		return true;
	}

	/* new UnionType($types) — exactly the class, as the twin's `new
	 * UnionType` sites spell it; $types consumed; UNDEF = pending exception */
	static zv::Val create(zv::Val types, bool normalized = false)
	{
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_union_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!UnionType(Z_OBJ(object)).construct(types.raw(), normalized))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* $this->types (borrowed); NULL with an Error pending when the
	 * constructor never ran, as the twin's typed-property read raises */
	[[nodiscard]] zval *types() const { return typesOf(self); }

	/* $type->types of another UnionType instance */
	static zval *typesOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::types);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$types must not be accessed before initialization", ZSTR_VAL(pt_ce_union_type->name));
			return NULL;
		}
		return slot;
	}

	/* $object->getTypes() through the object's class entry; UNDEF = pending
	 * exception */
	static zv::Val getTypesOf(zend_object *object)
	{
		if (EXPECTED(pt_type_method_is(object, PT_LC("gettypes"), utGetTypes))) {
			zval *types = typesOf(object);
			if (UNEXPECTED(types == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(types));
		}
		return pt_type_call_array(object, PT_LC("gettypes"), 0, NULL);
	}

	zv::Val getTypes() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(types));
	}

	/* $this->getTypes() through the object's class entry */
	zv::Val thisGetTypes() const { return getTypesOf(self); }

	/* TypeCombinator::union(...$newTypes) of the members $filterCb keeps,
	 * $this when it keeps every one; UNDEF = pending exception */
	zv::Val filterTypes(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *innerType = entry.value().deref().raw();
			zval arg;
			ZVAL_COPY_VALUE(&arg, innerType);
			zval keep;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &keep))) return zv::Val();
			bool keeps = zend_is_true(&keep);
			zval_ptr_dtor(&keep);
			if (!keeps) {
				changed = true;
				continue;
			}
			newTypes.push(zv::Ref(innerType));
		}
		if (!changed) return thisValue();
		return combinatorUnion(newTypes.table());
	}

	/* $this->normalized; false with an Error pending when uninitialized */
	[[nodiscard]] bool isNormalized(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::normalized);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$normalized must not be accessed before initialization", ZSTR_VAL(pt_ce_union_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* $this->finiteTypeSet ??= FiniteTypeSet::create($this->types) ?? false,
	 * null for false: the set object (owned copy) or a null value; UNDEF =
	 * pending exception */
	zv::Val getFiniteTypeSet() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::finiteTypeSet);
		if (Z_TYPE_P(slot) == IS_NULL) {
			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return zv::Val();
			zv::Val created = pt_type_finite_type_set_create(types);
			if (UNEXPECTED(created.isUndef())) return zv::Val();
			slot = OBJ_PROP_NUM(self, slots::finiteTypeSet);
			if (zv::Ref(created.raw()).isNull()) {
				zv::Ref(slot).assign(zv::Val::boolean(false));
			} else {
				zv::Ref(slot).assign(std::move(created));
			}
		}
		if (Z_TYPE_P(slot) == IS_FALSE) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(slot));
	}

	/* $this->getFiniteTypeSet() through the object's class entry */
	zv::Val thisGetFiniteTypeSet() const { return finiteTypeSetOf(self); }

	/* $type->getFiniteTypeSet() of another union, the same way */
	static zv::Val finiteTypeSetOf(zend_object *object)
	{
		if (EXPECTED(object->ce == pt_ce_union_type || pt_type_method_is(object, PT_LC("getfinitetypeset"), utGetFiniteTypeSet))) {
			return UnionType(object).getFiniteTypeSet();
		}
		zv::Val result = pt_type_call(object, PT_LC("getfinitetypeset"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isNull() && !zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getFiniteTypeSet() must return ?FiniteTypeSet");
			return zv::Val();
		}
		return result;
	}

	/* $this->sortedTypesCache ??= UnionTypeHelper::sortTypes($this->types);
	 * an owned copy of the list; UNDEF = pending exception */
	zv::Val getSortedTypes() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::sortedTypesCache);
		if (Z_TYPE_P(slot) == IS_NULL) {
			zval *types = this->types();
			if (UNEXPECTED(types == NULL)) return zv::Val();
			zv::Val sorted = pt_union_type_helper_sort_types(types);
			if (UNEXPECTED(sorted.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(sorted.raw()).isArray())) {
				zend_type_error("phpstan_turbo: UnionTypeHelper::sortTypes() must return array");
				return zv::Val();
			}
			slot = OBJ_PROP_NUM(self, slots::sortedTypesCache);
			zv::Ref(slot).assign(std::move(sorted));
		}
		return zv::Val::copyOf(zv::Ref(slot));
	}

	/* $this->getSortedTypes() through the object's class entry (protected —
	 * a subclass may still override it) */
	zv::Val thisGetSortedTypes() const { return sortedTypesOf(self); }

	static zv::Val sortedTypesOf(zend_object *object)
	{
		if (EXPECTED(object->ce == pt_ce_union_type || pt_type_method_is(object, PT_LC("getsortedtypes"), utGetSortedTypes))) {
			return UnionType(object).getSortedTypes();
		}
		return pt_type_call_array(object, PT_LC("getsortedtypes"), 0, NULL);
	}

	/* the concatenation of every member's getReferencedClasses() */
	zv::Val getReferencedClasses() const
	{
		return concatOf(UnionMemberOp::call(PT_LC("getreferencedclasses")));
	}

	/* array_values(array_unique($this->pickFromTypes(getObjectClassNames, isObject))) */
	zv::Val getObjectClassNames() const
	{
		zv::Val names = pickFromTypes(UnionMemberOp::call(PT_LC("getobjectclassnames")), UnionCriteria{ PT_LC("isobject") });
		if (UNEXPECTED(names.isUndef())) return zv::Val();
		return uniqueStrings(zv::ArrRef(names.raw()));
	}

	zv::Val getObjectClassReflections() const { return pickFromTypes(UnionMemberOp::call(PT_LC("getobjectclassreflections")), UnionCriteria{ PT_LC("isobject") }); }
	zv::Val getArrays() const { return pickFromTypes(UnionMemberOp::call(PT_LC("getarrays")), UnionCriteria{ PT_LC("isarray") }); }
	zv::Val getConstantArrays() const { return pickFromTypes(UnionMemberOp::call(PT_LC("getconstantarrays")), UnionCriteria{ PT_LC("isarray") }); }
	zv::Val getConstantStrings() const { return pickFromTypes(UnionMemberOp::call(PT_LC("getconstantstrings")), UnionCriteria{ PT_LC("isstring") }); }
	zv::Val getEnumCases() const { return pickFromTypes(UnionMemberOp::call(PT_LC("getenumcases")), UnionCriteria{ PT_LC("isobject") }); }

	/* $this->getEnumCases() through the object's class entry */
	zv::Val thisGetEnumCases() const
	{
		if (EXPECTED(own(PT_LC("getenumcases"), utGetEnumCases))) return getEnumCases();
		return pt_type_call_array(self, PT_LC("getenumcases"), 0, NULL);
	}

	/* accepts(): the finite-value shortcuts, the iterable and the
	 * DateTimeInterface/Throwable rewrites, then the member-by-member or()
	 * with the reasons decorated per member, the compound callbacks and the
	 * enum-case expansion; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zval strictZv = {};
		ZVAL_BOOL(&strictZv, strictTypes);

		zv::Val finiteTypeSet = thisGetFiniteTypeSet();
		if (UNEXPECTED(finiteTypeSet.isUndef())) return zv::Val();
		if (!zv::Ref(finiteTypeSet.raw()).isNull()) {
			zend_object *set = Z_OBJ_P(finiteTypeSet.raw());
			zv::Val key = finiteKey(type);
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			if (zv::Ref(key.raw()).isString()) {
				int has = pt_type_call_is_true(set, PT_LC("has"), 1, key.raw());
				if (UNEXPECTED(has < 0)) return zv::Val();
				if (has == 1) return acceptsResult(PT_TRI_YES);
			}
			int complete = pt_type_call_is_true(set, PT_LC("iscomplete"), 0, NULL);
			if (UNEXPECTED(complete < 0)) return zv::Val();
			if (complete == 1) {
				if (zv::Ref(key.raw()).isString()) {
					/* one member of each other kind is enough */
					zv::Val result = acceptsResult(PT_TRI_NO);
					zv::Val representatives = pt_type_call_array(set, PT_LC("getrepresentativesofotherkinds"), 1, type);
					if (UNEXPECTED(representatives.isUndef())) return zv::Val();
					for (zv::ArrayEntry entry : zv::ArrRef(representatives.raw())) {
						zv::Args args{type, &strictZv};
						zv::Val inner = pt_type_op(entry.value().deref().asObject(), PT_OP_ACCEPTS, 2, args);
						if (UNEXPECTED(inner.isUndef())) return zv::Val();
						result = acceptsOr(std::move(result), inner.raw());
						if (UNEXPECTED(result.isUndef())) return zv::Val();
					}
					return result;
				}

				/* $type instanceof self && !$type instanceof TemplateType */
				if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) {
					bool isTemplate;
					if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
					if (!isTemplate) {
						zv::Val otherSet = finiteTypeSetOf(Z_OBJ_P(type));
						if (UNEXPECTED(otherSet.isUndef())) return zv::Val();
						if (!zv::Ref(otherSet.raw()).isNull()) {
							int otherComplete = pt_type_call_is_true(Z_OBJ_P(otherSet.raw()), PT_LC("iscomplete"), 0, NULL);
							if (UNEXPECTED(otherComplete < 0)) return zv::Val();
							if (otherComplete == 1) {
								zv::Args args{&selfZv, &strictZv};
								return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
							}
						}
					}
				}
			}
		}

		bool isIterable;
		if (UNEXPECTED(!isInstance(type, pt_ce_iterable_type, isIterable))) return zv::Val();
		if (isIterable) {
			zv::Val arrayOrTraversable = callType(Z_OBJ_P(type), PT_LC("toarrayortraversable"), 0, NULL);
			if (UNEXPECTED(arrayOrTraversable.isUndef())) return zv::Val();
			return thisAccepts(arrayOrTraversable.raw(), strictTypes);
		}

		/* foreach (self::EQUAL_UNION_CLASSES as $baseClass => $classes) */
		zend_class_constant *equalUnionClasses = (zend_class_constant *) zend_hash_str_find_ptr(&pt_ce_union_type->constants_table, PT_LC("EQUAL_UNION_CLASSES"));
		if (UNEXPECTED(equalUnionClasses == NULL || Z_TYPE(equalUnionClasses->value) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::EQUAL_UNION_CLASSES not found", ZSTR_VAL(pt_ce_union_type->name));
			return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(&equalUnionClasses->value)) {
			zv::Val baseClass = zv::Val::string(entry.stringKey());
			zv::Val baseObject = pt_type_new_object_type(baseClass.raw());
			if (UNEXPECTED(baseObject.isUndef())) return zv::Val();
			int equal = pt_type_call_is_true(Z_OBJ_P(type), PT_LC("equals"), 1, baseObject.raw());
			if (UNEXPECTED(equal < 0)) return zv::Val();
			if (equal == 0) continue;
			zv::ArrRef classes(entry.value().raw());
			zv::Arr objects = zv::Arr::create(classes.size());
			for (zv::ArrayEntry classEntry : classes) {
				zv::Val object = pt_type_new_object_type(classEntry.value().raw());
				if (UNEXPECTED(object.isUndef())) return zv::Val();
				objects.push(std::move(object));
			}
			zv::Val unionType = combinatorUnion(objects.table());
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			zend_long accepted = acceptsTrinary(thisAccepts(unionType.raw(), strictTypes));
			if (UNEXPECTED(accepted < 0)) return zv::Val();
			if (accepted == PT_TRI_YES) return acceptsResult(PT_TRI_YES);
			break;
		}

		zv::Val sorted = thisGetSortedTypes();
		if (UNEXPECTED(sorted.isUndef())) return zv::Val();
		zv::Arr innerAccepts = zv::Arr::create(zv::ArrRef(sorted.raw()).size());
		zv::Val result = acceptsResult(PT_TRI_NO);
		zend_long i = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
			zv::Args args{type, &strictZv};
			zv::Val innerResult = pt_type_op(entry.value().deref().asObject(), PT_OP_ACCEPTS, 2, args);
			if (UNEXPECTED(innerResult.isUndef())) return zv::Val();
			zv::Val decorated = decorateReasons(innerResult.raw(), i + 1);
			if (UNEXPECTED(decorated.isUndef())) return zv::Val();
			innerAccepts.push(std::move(innerResult));
			result = acceptsOr(std::move(result), decorated.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			i++;
		}
		zend_long resultValue = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(resultValue < 0)) return zv::Val();
		if (resultValue == PT_TRI_YES) return result;

		/* the reasons every member shares, decorated per member */
		zv::Val commonReasons;
		for (zv::ArrayEntry entry : innerAccepts.arrRef()) {
			zval holder;
			ZVAL_UNDEF(&holder);
			zval *reasons = resultReasons(entry.value().raw(), &holder);
			if (UNEXPECTED(reasons == NULL)) return zv::Val();
			if (commonReasons.isUndef()) {
				commonReasons = zv::Val::copyOf(zv::Ref(reasons));
			} else {
				/* array_values(array_intersect($commonReasons, $innerResult->reasons)) */
				zv::Arr kept = zv::Arr::create(0);
				for (zv::ArrayEntry common : zv::ArrRef(commonReasons.raw())) {
					bool found = false;
					for (zv::ArrayEntry other : zv::ArrRef(reasons)) {
						if (sameString(common.value().deref().raw(), other.value().deref().raw())) {
							found = true;
							break;
						}
					}
					if (found) {
						kept.push(common.value());
					}
				}
				commonReasons = zv::Val(std::move(kept));
			}
			zval_ptr_dtor(&holder);
		}
		if (!commonReasons.isUndef() && zv::ArrRef(commonReasons.raw()).size() > 0) {
			zv::Arr decorated = zv::Arr::create(0);
			for (zend_long k = 0; k < i; k++) {
				for (zv::ArrayEntry reason : zv::ArrRef(commonReasons.raw())) {
					decorated.push(decoratedReason(k + 1, reason.value().deref().asString()));
				}
			}
			result = withReasons(result.raw(), std::move(decorated));
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		/* $type instanceof CompoundType && !CallableType && !TemplateType && !IntersectionType */
		bool compound;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			bool isCallable, isTemplate;
			if (UNEXPECTED(!isInstance(type, pt_ce_callable_type, isCallable) || !isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (!isCallable && !isTemplate && !instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
				zv::Args args{&selfZv, &strictZv};
				return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
			}
		}

		bool isTemplateUnion;
		if (UNEXPECTED(!isInstance(type, pt_ce_template_union_type, isTemplateUnion))) return zv::Val();
		if (isTemplateUnion) {
			zv::Args args{&selfZv, &strictZv};
			zv::Val accepted = pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
			if (UNEXPECTED(accepted.isUndef())) return zv::Val();
			return acceptsOr(std::move(result), accepted.raw());
		}

		zend_long typeIsEnum = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isenum"), 0, NULL);
		if (UNEXPECTED(typeIsEnum < 0)) return zv::Val();
		if (typeIsEnum == PT_TRI_YES) {
			zend_long thisEnum = thisIsEnum();
			if (UNEXPECTED(thisEnum < 0)) return zv::Val();
			if (thisEnum != PT_TRI_NO) {
				zv::Val cases = pt_type_call_array(Z_OBJ_P(type), PT_LC("getenumcases"), 0, NULL);
				if (UNEXPECTED(cases.isUndef())) return zv::Val();
				zv::Val enumCasesUnion = combinatorUnion(zv::ArrRef(cases.raw()).table());
				if (UNEXPECTED(enumCasesUnion.isUndef())) return zv::Val();
				int equal = pt_type_call_is_true(Z_OBJ_P(type), PT_LC("equals"), 1, enumCasesUnion.raw());
				if (UNEXPECTED(equal < 0)) return zv::Val();
				if (equal == 0) return thisAccepts(enumCasesUnion.raw(), strictTypes);
			}
		}

		return result;
	}

	/* $otherType->isSubTypeOf($this) for the compound types answering
	 * from their side, the finite-value shortcuts, then no or'ed with every
	 * member's isSuperTypeOf() (the first yes wins), or'ed once more with the
	 * late-resolvable answer; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *otherType) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);

		bool delegate = false;
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_union_type)) {
			bool isTemplateUnion;
			if (UNEXPECTED(!isInstance(otherType, pt_ce_template_union_type, isTemplateUnion))) return zv::Val();
			delegate = !isTemplateUnion;
		}
		if (!delegate) {
			bool isIterable;
			if (UNEXPECTED(!isInstance(otherType, pt_ce_iterable_type, isIterable))) return zv::Val();
			if (isIterable) {
				bool isTemplateIterable;
				if (UNEXPECTED(!isInstance(otherType, pt_ce_template_iterable_type, isTemplateIterable))) return zv::Val();
				delegate = !isTemplateIterable;
			}
		}
		if (!delegate) {
			delegate = instanceof_function(Z_OBJCE_P(otherType), pt_ce_never_type) || instanceof_function(Z_OBJCE_P(otherType), pt_ce_integer_range_type);
		}
		if (delegate) return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);

		zv::Val types = getTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val finiteTypeSet = thisGetFiniteTypeSet();
		if (UNEXPECTED(finiteTypeSet.isUndef())) return zv::Val();
		if (!zv::Ref(finiteTypeSet.raw()).isNull()) {
			zend_object *set = Z_OBJ_P(finiteTypeSet.raw());
			zv::Val key = finiteKey(otherType);
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			if (zv::Ref(key.raw()).isString()) {
				int has = pt_type_call_is_true(set, PT_LC("has"), 1, key.raw());
				if (UNEXPECTED(has < 0)) return zv::Val();
				if (has == 1) return isSuperTypeOfResult(PT_TRI_YES);
				int complete = pt_type_call_is_true(set, PT_LC("iscomplete"), 0, NULL);
				if (UNEXPECTED(complete < 0)) return zv::Val();
				if (complete == 1) return isSuperTypeOfResult(PT_TRI_NO);
				types = pt_type_call_array(set, PT_LC("getothers"), 0, NULL);
				if (UNEXPECTED(types.isUndef())) return zv::Val();
			}
		}

		zv::Arr results = zv::Arr::create(zv::ArrRef(types.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val result = pt_type_op(entry.value().deref().asObject(), PT_OP_IS_SUPER_TYPE_OF, 1, otherType);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_long value = pt_type_result_trinary(result.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return result;
			results.push(std::move(result));
		}
		zv::Val no = isSuperTypeOfResult(PT_TRI_NO);
		if (UNEXPECTED(no.isUndef())) return zv::Val();
		zv::Val result = pt_is_super_type_of_result_spread(Z_OBJ_P(no.raw()), false, results.table());
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		bool orWithSubType;
		if (UNEXPECTED(!isInstance(otherType, pt_ce_template_union_type, orWithSubType))) return zv::Val();
		if (!orWithSubType) {
			bool lateResolvable;
			if (UNEXPECTED(!isInstance(otherType, PT_CLASS_LATE_RESOLVABLE_TYPE, lateResolvable))) return zv::Val();
			if (lateResolvable) {
				bool compound, isTemplate;
				if (UNEXPECTED(!isInstance(otherType, PT_CLASS_COMPOUND_TYPE, compound) || !isInstance(otherType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) {
					return zv::Val();
				}
				orWithSubType = compound && !isTemplate;
			}
		}
		if (orWithSubType) {
			zv::Val subType = pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
			if (UNEXPECTED(subType.isUndef())) return zv::Val();
			return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("or"), 1, subType.raw());
		}

		return result;
	}

	/* the finite-set containment when it settles the question, else
	 * IsSuperTypeOfResult::extremeIdentity() over $otherType->isSuperTypeOf()
	 * of every member; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		zend_long containment = finiteTypeSetContainedIn(otherType, false);
		if (UNEXPECTED(containment == -1)) return zv::Val();
		if (containment != CONTAINMENT_UNSETTLED) {
			if (containment == PT_TRI_MAYBE) return isSuperTypeOfResult(PT_TRI_MAYBE);
			return isSuperTypeOfResult(containment == PT_TRI_YES ? PT_TRI_YES : PT_TRI_NO);
		}

		zv::Val results = mapReversed(PT_LC("issupertypeof"), Z_OBJ_P(otherType), 0, NULL);
		if (UNEXPECTED(results.isUndef())) return zv::Val();
		return pt_is_super_type_of_result_extreme_identity_spread(zv::ArrRef(results.raw()).table());
	}

	/* yes when the finite set is contained, else AcceptsResult::extremeIdentity()
	 * over $acceptingType->accepts() of every member; UNDEF = pending exception */
	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zend_long containment = finiteTypeSetContainedIn(acceptingType, true);
		if (UNEXPECTED(containment == -1)) return zv::Val();
		if (containment == PT_TRI_YES) return acceptsResult(PT_TRI_YES);

		zval strictZv = {};
		ZVAL_BOOL(&strictZv, strictTypes);
		zv::Val results = mapReversed(PT_LC("accepts"), Z_OBJ_P(acceptingType), 1, &strictZv);
		if (UNEXPECTED(results.isUndef())) return zv::Val();
		return pt_accepts_result_extreme_identity_spread(zv::ArrRef(results.raw()).table());
	}

	/* the same class (`$type instanceof static`) with the same member
	 * count, then the finite sets or member-by-member equals() with each
	 * match consumed once; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), self->ce)) {
			out = false;
			return true;
		}
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return false;
		zval *otherTypes = typesOf(Z_OBJ_P(type));
		if (UNEXPECTED(otherTypes == NULL)) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) != zend_hash_num_elements(Z_ARRVAL_P(otherTypes))) {
			out = false;
			return true;
		}

		zv::Val finiteTypeSet = thisGetFiniteTypeSet();
		if (UNEXPECTED(finiteTypeSet.isUndef())) return false;
		if (!zv::Ref(finiteTypeSet.raw()).isNull()) {
			int complete = pt_type_call_is_true(Z_OBJ_P(finiteTypeSet.raw()), PT_LC("iscomplete"), 0, NULL);
			if (UNEXPECTED(complete < 0)) return false;
			if (complete == 1) {
				zv::Val otherSet = finiteTypeSetOf(Z_OBJ_P(type));
				if (UNEXPECTED(otherSet.isUndef())) return false;
				if (!zv::Ref(otherSet.raw()).isNull()) {
					int otherComplete = pt_type_call_is_true(Z_OBJ_P(otherSet.raw()), PT_LC("iscomplete"), 0, NULL);
					if (UNEXPECTED(otherComplete < 0)) return false;
					if (otherComplete == 1) {
						zend_long contained = pt_type_call_trinary(Z_OBJ_P(finiteTypeSet.raw()), PT_LC("containedin"), 1, otherSet.raw());
						if (UNEXPECTED(contained < 0)) return false;
						out = contained == PT_TRI_YES;
						return true;
					}
				}
			}
		}

		/* the other members, each consumed by its first match */
		zv::Val otherTypesCopy = zv::Val::copyOf(zv::Ref(otherTypes));
		uint32_t otherCount = zend_hash_num_elements(Z_ARRVAL_P(otherTypesCopy.raw()));
		zval **others = (zval **) safe_emalloc(otherCount > 0 ? otherCount : 1, sizeof(zval *), 0);
		bool *used = (bool *) ecalloc(otherCount > 0 ? otherCount : 1, sizeof(bool));
		uint32_t n = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(otherTypesCopy.raw())) {
			others[n++] = entry.value().deref().raw();
		}
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		bool ok = true;
		bool result = true;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *innerType = entry.value().deref().asObject();
			bool match = false;
			for (uint32_t j = 0; j < n; j++) {
				if (used[j]) continue;
				int equal = pt_type_call_is_true(innerType, PT_LC("equals"), 1, others[j]);
				if (UNEXPECTED(equal < 0)) {
					ok = false;
					break;
				}
				if (equal == 0) continue;
				match = true;
				used[j] = true;
				break;
			}
			if (!ok) break;
			if (!match) {
				result = false;
				break;
			}
		}
		if (ok && result) {
			for (uint32_t j = 0; j < n; j++) {
				if (!used[j]) {
					result = false;
					break;
				}
			}
		}
		efree(others);
		efree(used);
		if (!ok) return false;
		out = result;
		return true;
	}

	/* describe(): the per-level cache, the type-only level describing the
	 * union of the members with their constant values generalized, every
	 * other level the sorted members joined with '|'; an owned string, UNDEF
	 * = pending exception */
	zv::Val describe(zval *level) const
	{
		/* $level->getLevelValue() — the shadowing VerbosityLevel's slot, or
		 * the PHP twin's method (VerbosityLevel.cpp) */
		zend_long levelValue;
		if (UNEXPECTED(!pt_verbosity_level_value_of(level, levelValue))) return zv::Val();
		zval *cache = OBJ_PROP_NUM(self, slots::cachedDescriptions);
		if (EXPECTED(Z_TYPE_P(cache) == IS_ARRAY)) {
			zval *cached = zend_hash_index_find(Z_ARRVAL_P(cache), levelValue);
			if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));
		}

		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		zv::Val description;
		if (which == PT_VERBOSITY_TYPE_ONLY) {
			zv::Val sorted = thisGetSortedTypes();
			if (UNEXPECTED(sorted.isUndef())) return zv::Val();
			zv::Val lessSpecific = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
			if (UNEXPECTED(lessSpecific.isUndef())) return zv::Val();
			zv::Arr generalized = zv::Arr::create(zv::ArrRef(sorted.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
				zend_object *type = entry.value().deref().asObject();
				zend_long isConstant = pt_type_call_trinary(type, PT_LC("isconstantvalue"), 0, NULL);
				if (UNEXPECTED(isConstant < 0)) return zv::Val();
				bool generalize = false;
				if (isConstant == PT_TRI_YES) {
					/* $type->isTrue()->or($type->isFalse())->no() */
					zend_long isTrue = pt_type_call_trinary(type, PT_LC("istrue"), 0, NULL);
					if (UNEXPECTED(isTrue < 0)) return zv::Val();
					zend_long isFalse = pt_type_call_trinary(type, PT_LC("isfalse"), 0, NULL);
					if (UNEXPECTED(isFalse < 0)) return zv::Val();
					generalize = (isTrue | isFalse) == PT_TRI_NO;
				}
				if (generalize) {
					zv::Val general = callType(type, PT_LC("generalize"), 1, lessSpecific.raw());
					if (UNEXPECTED(general.isUndef())) return zv::Val();
					generalized.push(std::move(general));
				} else {
					generalized.push(entry.value());
				}
			}
			zv::Val types = combinatorUnion(generalized.table());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			if (zv::Ref(types.raw()).instanceOf(pt_ce_union_type)) {
				zv::Val innerSorted = sortedTypesOf(Z_OBJ_P(types.raw()));
				if (UNEXPECTED(innerSorted.isUndef())) return zv::Val();
				description = joinTypes(zv::ArrRef(innerSorted.raw()), level, which);
			} else {
				zv::Arr single = zv::Arr::create(1);
				single.push(std::move(types));
				description = joinTypes(single.arrRef(), level, which);
			}
		} else {
			zv::Val sorted = thisGetSortedTypes();
			if (UNEXPECTED(sorted.isUndef())) return zv::Val();
			description = joinTypes(zv::ArrRef(sorted.raw()), level, which);
		}
		if (UNEXPECTED(description.isUndef())) return zv::Val();

		cache = OBJ_PROP_NUM(self, slots::cachedDescriptions);
		if (Z_TYPE_P(cache) != IS_ARRAY) {
			zv::Ref(cache).assign(zv::Val(zv::Arr::create(4)));
		}
		SEPARATE_ARRAY(cache);
		zval copy;
		ZVAL_COPY(&copy, description.raw());
		zend_hash_index_update(Z_ARRVAL_P(cache), levelValue, &copy);
		return description;
	}

	/* $this->describe($level) through the object's class entry */
	zv::Val thisDescribe(zval *level) const
	{
		if (EXPECTED(own(PT_LC("describe"), utDescribe))) return describe(level);
		zv::Val result = pt_type_op(self, PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return result;
	}

	/* describe()'s $joinTypes: every member's description, parenthesized
	 * for a closure/callable/template union, for a template with a bound
	 * that must be shown, and for an intersection containing '&'; the
	 * duplicates numbered (#n) at the precise and cache levels and dropped
	 * elsewhere; at most PT_UT_DESCRIBE_TYPES_LIMIT joined with '|' */
	static zv::Val joinTypes(zv::ArrRef types, zval *level, pt_verbosity_case which)
	{
		uint32_t count = types.size();
		zv::Arr typeNames = zv::Arr::create(count);
		zend_long i = 0;
		for (zv::ArrayEntry entry : types) {
			zval *type = entry.value().deref().raw();
			bool wrap = false;
			bool isClosureOrCallableOrTemplateUnion;
			if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_closure_type, isClosureOrCallableOrTemplateUnion))) return zv::Val();
			if (!isClosureOrCallableOrTemplateUnion && UNEXPECTED(!isInstance(type, pt_ce_callable_type, isClosureOrCallableOrTemplateUnion))) return zv::Val();
			if (!isClosureOrCallableOrTemplateUnion && UNEXPECTED(!isInstance(type, pt_ce_template_union_type, isClosureOrCallableOrTemplateUnion))) {
				return zv::Val();
			}
			zv::Val description;
			if (isClosureOrCallableOrTemplateUnion) {
				wrap = true;
			} else {
				bool isTemplate;
				if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
				if (isTemplate) {
					/* $i >= count($types) - 1 — $i is the array key */
					bool isLast = (entry.stringKeyOrNull() == NULL ? (zend_long) entry.indexKey() : i) >= (zend_long) count - 1;
					zv::Val bound = callType(Z_OBJ_P(type), PT_LC("getbound"), 0, NULL);
					if (UNEXPECTED(bound.isUndef())) return zv::Val();
					if (!isLast && (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE)) {
						bool plainMixed = false;
						if (zv::Ref(bound.raw()).instanceOf(pt_ce_mixed_type)) {
							zv::Val subtracted = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("getsubtractedtype"), 0, NULL);
							if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
							if (zv::Ref(subtracted.raw()).isNull()) {
								bool isTemplateMixed;
								if (UNEXPECTED(!isInstance(bound.raw(), pt_ce_template_mixed_type, isTemplateMixed))) return zv::Val();
								plainMixed = !isTemplateMixed;
							}
						}
						wrap = !plainMixed;
					}
				} else if (instanceof_function(Z_OBJCE_P(type), pt_ce_intersection_type)) {
					description = describeOf(type, level);
					if (UNEXPECTED(description.isUndef())) return zv::Val();
					zend_string *s = zv::Ref(description.raw()).asString();
					wrap = memchr(ZSTR_VAL(s), '&', ZSTR_LEN(s)) != NULL;
				}
			}
			if (description.isUndef()) {
				description = describeOf(type, level);
				if (UNEXPECTED(description.isUndef())) return zv::Val();
			}
			if (wrap) {
				zend_string *s = zv::Ref(description.raw()).asString();
				smart_str wrapped = {NULL, 0};
				smart_str_appendc(&wrapped, '(');
				smart_str_append(&wrapped, s);
				smart_str_appendc(&wrapped, ')');
				smart_str_0(&wrapped);
				typeNames.push(zv::Val::adoptString(wrapped.s));
			} else {
				typeNames.push(std::move(description));
			}
			i++;
		}

		zv::Arr joined = zv::Arr::create(count);
		if (which == PT_VERBOSITY_PRECISE || which == PT_VERBOSITY_CACHE) {
			/* the duplicated names numbered in order of appearance */
			zv::ScratchTable counts(count);
			for (zv::ArrayEntry entry : typeNames.arrRef()) {
				zval *slot = zend_hash_find(counts.table(), Z_STR_P(entry.value().raw()));
				if (slot == NULL) {
					zval one;
					ZVAL_LONG(&one, 1);
					zend_hash_add_new(counts.table(), Z_STR_P(entry.value().raw()), &one);
				} else {
					Z_LVAL_P(slot)++;
				}
			}
			zv::ScratchTable indexes(count);
			for (zv::ArrayEntry entry : typeNames.arrRef()) {
				zend_string *name = Z_STR_P(entry.value().raw());
				zval *occurrences = zend_hash_find(counts.table(), name);
				if (occurrences == NULL || Z_LVAL_P(occurrences) < 2) {
					joined.push(entry.value());
					continue;
				}
				zval *index = zend_hash_find(indexes.table(), name);
				zend_long next;
				if (index == NULL) {
					next = 1;
					zval first;
					ZVAL_LONG(&first, 1);
					zend_hash_add_new(indexes.table(), name, &first);
				} else {
					next = ++Z_LVAL_P(index);
				}
				smart_str numbered = {NULL, 0};
				smart_str_append(&numbered, name);
				smart_str_appendc(&numbered, '#');
				smart_str_append_long(&numbered, next);
				smart_str_0(&numbered);
				joined.push(zv::Val::adoptString(numbered.s));
			}
		} else {
			/* array_unique(): the first occurrence of every name */
			zv::ScratchTable seen(count);
			for (zv::ArrayEntry entry : typeNames.arrRef()) {
				if (zend_hash_add_empty_element(seen.table(), Z_STR_P(entry.value().raw())) == NULL) continue;
				joined.push(entry.value());
			}
		}

		smart_str result = {NULL, 0};
		uint32_t n = 0;
		bool truncated = zend_hash_num_elements(joined.table()) > PT_UT_DESCRIBE_TYPES_LIMIT;
		for (zv::ArrayEntry entry : joined.arrRef()) {
			if (truncated && n == PT_UT_DESCRIBE_TYPES_LIMIT) break;
			if (n > 0) {
				smart_str_appendc(&result, '|');
			}
			smart_str_append(&result, Z_STR_P(entry.value().raw()));
			n++;
		}
		if (truncated) {
			smart_str_appendl(&result, "|\xE2\x80\xA6", 4);
		}
		smart_str_0(&result);
		if (result.s == NULL) return zv::Val::string("", 0);
		return zv::Val::adoptString(result.s);
	}

	/* the unionTypes() family, one member call each; UNDEF = pending
	 * exception */
	zv::Val getTemplateType(zval *ancestorClassName, zval *templateTypeName) const
	{
		zv::Args args{ancestorClassName, templateTypeName};
		return unionTypes(UnionMemberOp::call(PT_LC("gettemplatetype"), 2, args));
	}

	zend_long isObject() const { return unionResults(UnionMemberOp::call(PT_LC("isobject"))); }
	zv::Val getClassStringType() const { return unionTypes(UnionMemberOp::call(PT_LC("getclassstringtype"))); }
	zend_long isEnum() const { return unionResults(UnionMemberOp::call(PT_LC("isenum"))); }

	/* $this->isEnum() through the object's class entry; -1 = pending
	 * exception */
	[[nodiscard]] zend_long thisIsEnum() const
	{
		if (EXPECTED(own(PT_LC("isenum"), utIsEnum))) return isEnum();
		return pt_type_call_trinary(self, PT_LC("isenum"), 0, NULL);
	}

	zend_long canAccessProperties() const { return unionResults(UnionMemberOp::call(PT_LC("canaccessproperties"))); }
	zend_long hasProperty(zval *propertyName) const { return unionResults(UnionMemberOp::call(PT_LC("hasproperty"), 1, propertyName)); }

	/* getUnresolved*Prototype(): the prototypes of the members having the
	 * member, fetched on $this; none throws the Missing*FromReflectionException,
	 * one is returned as is, more are combined; UNDEF = pending exception */
	zv::Val unresolvedPropertyPrototype(const char *hasLcname, size_t hasLen, const char *prototypeLcname, size_t prototypeLen, zval *propertyName, zval *scope) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr prototypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long has = pt_type_call_trinary(type, hasLcname, hasLen, 1, propertyName);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has != PT_TRI_YES) continue;
			zv::Args args{propertyName, scope};
			zv::Val prototype = callType(type, prototypeLcname, prototypeLen, 2, args);
			if (UNEXPECTED(prototype.isUndef())) return zv::Val();
			zv::Val fetched = pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("withfechedontype"), 1, &selfZv);
			if (UNEXPECTED(fetched.isUndef())) return zv::Val();
			prototypes.push(std::move(fetched));
		}
		uint32_t propertiesCount = zend_hash_num_elements(prototypes.table());
		if (propertiesCount == 0) {
			zv::Val typeOnly = verbosityLevel(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
			zv::Val description = thisDescribe(typeOnly.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			zv::Args args{description.raw(), propertyName};
			throwMapped(PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION, 2, args);
			return zv::Val();
		}
		if (propertiesCount == 1) return zv::Val::copyOf(prototypes.arrRef().findIndex(0));
		return pt_type_new(PT_CLASS_UNION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 1, prototypes.raw());
	}

	zv::Val getUnresolvedPropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPropertyPrototype(PT_LC("hasproperty"), PT_LC("getunresolvedpropertyprototype"), propertyName, scope);
	}

	zend_long hasInstanceProperty(zval *propertyName) const { return unionResults(UnionMemberOp::call(PT_LC("hasinstanceproperty"), 1, propertyName)); }

	zv::Val getUnresolvedInstancePropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPropertyPrototype(PT_LC("hasinstanceproperty"), PT_LC("getunresolvedinstancepropertyprototype"), propertyName, scope);
	}

	zend_long hasStaticProperty(zval *propertyName) const { return unionResults(UnionMemberOp::call(PT_LC("hasstaticproperty"), 1, propertyName)); }

	zv::Val getUnresolvedStaticPropertyPrototype(zval *propertyName, zval *scope) const
	{
		return unresolvedPropertyPrototype(PT_LC("hasstaticproperty"), PT_LC("getunresolvedstaticpropertyprototype"), propertyName, scope);
	}

	zend_long canCallMethods() const { return unionResults(UnionMemberOp::call(PT_LC("cancallmethods"))); }
	zend_long hasMethod(zval *methodName) const { return unionResults(UnionMemberOp::call(PT_LC("hasmethod"), 1, methodName)); }

	zv::Val getUnresolvedMethodPrototype(zval *methodName, zval *scope) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		bool thisIsTemplate;
		if (UNEXPECTED(!isInstance(&selfZv, PT_CLASS_TEMPLATE_TYPE, thisIsTemplate))) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr prototypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long has = pt_type_call_trinary(type, PT_LC("hasmethod"), 1, methodName);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has != PT_TRI_YES) continue;
			zv::Args args{methodName, scope};
			zv::Val prototype = callType(type, PT_LC("getunresolvedmethodprototype"), 2, args);
			if (UNEXPECTED(prototype.isUndef())) return zv::Val();
			if (thisIsTemplate) {
				prototype = pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("withcalledontype"), 1, &selfZv);
				if (UNEXPECTED(prototype.isUndef())) return zv::Val();
			}
			prototypes.push(std::move(prototype));
		}
		uint32_t methodsCount = zend_hash_num_elements(prototypes.table());
		if (methodsCount == 0) {
			zv::Val typeOnly = verbosityLevel(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
			zv::Val description = thisDescribe(typeOnly.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			zv::Args args{description.raw(), methodName};
			throwMapped(PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION, 2, args);
			return zv::Val();
		}
		if (methodsCount == 1) return zv::Val::copyOf(prototypes.arrRef().findIndex(0));
		zv::Args args{methodName, prototypes.raw()};
		return pt_type_new(PT_CLASS_UNION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION, 2, args);
	}

	zend_long canAccessConstants() const { return unionResults(UnionMemberOp::call(PT_LC("canaccessconstants"))); }

	/* hasInternal(canAccessConstants, hasConstant): lazyExtremeIdentity of
	 * no for a member that cannot access constants, its hasConstant()
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long hasConstant(zval *constantName) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		if (UNEXPECTED(zend_hash_num_elements(Z_ARRVAL_P(types)) == 0)) {
			throwShouldNotHappen(NULL);
			return -1;
		}
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zend_long last = -2;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long can = pt_type_call_trinary(type, PT_LC("canaccessconstants"), 0, NULL);
			if (UNEXPECTED(can < 0)) return -1;
			zend_long result = PT_TRI_NO;
			if (can != PT_TRI_NO) {
				result = pt_type_call_trinary(type, PT_LC("hasconstant"), 1, constantName);
				if (UNEXPECTED(result < 0)) return -1;
			}
			if (last == -2) {
				last = result;
				continue;
			}
			if (last == result) continue;
			return PT_TRI_MAYBE;
		}
		return last;
	}

	/* getInternal(hasConstant, getConstant): the constant of the first
	 * member answering yes (a later yes never outranks it); none throws;
	 * UNDEF = pending exception */
	zv::Val getConstant(zval *constantName) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zend_long result = -2;
		zv::Val object;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long has = pt_type_call_trinary(type, PT_LC("hasconstant"), 1, constantName);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has != PT_TRI_YES) continue;
			/* $result->compareTo($has) !== $has: the stronger of the two, or
			 * null when equal */
			if (result != -2) {
				zend_long stronger = result > has ? result : (has > result ? has : -3);
				if (stronger != has) continue;
			}
			zv::Val get = pt_type_call(type, PT_LC("getconstant"), 1, constantName);
			if (UNEXPECTED(get.isUndef())) return zv::Val();
			result = has;
			object = std::move(get);
		}
		if (object.isUndef() || zv::Ref(object.raw()).isNull()) {
			throwShouldNotHappen(NULL);
			return zv::Val();
		}
		return object;
	}

	zend_long isIterable() const { return unionResults(UnionMemberOp::call(PT_LC("isiterable"))); }
	zend_long isIterableAtLeastOnce() const { return unionResults(UnionMemberOp::call(PT_LC("isiterableatleastonce"))); }
	zv::Val getArraySize() const { return unionTypes(UnionMemberOp::call(PT_LC("getarraysize"))); }
	zv::Val getIterableKeyType() const { return unionTypes(UnionMemberOp::call(PT_LC("getiterablekeytype"))); }
	zv::Val getIterableValueType() const { return unionTypes(UnionMemberOp::call(PT_LC("getiterablevaluetype"))); }

	/* the notBenevolentUnionResults() family: never the benevolent
	 * override, a private helper of the twin */
	zend_long isArray() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isarray"))); }
	zend_long isConstantArray() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isconstantarray"))); }
	zend_long isOversizedArray() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isoversizedarray"))); }
	zend_long isList() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("islist"))); }
	zend_long isString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isstring"))); }
	zend_long isNumericString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isnumericstring"))); }
	zend_long isDecimalIntegerString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isdecimalintegerstring"))); }
	zend_long isNonEmptyString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isnonemptystring"))); }
	zend_long isNonFalsyString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isnonfalsystring"))); }
	zend_long isLiteralString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isliteralstring"))); }
	zend_long isLowercaseString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("islowercasestring"))); }
	zend_long isUppercaseString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isuppercasestring"))); }
	zend_long isClassString() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isclassstring"))); }
	zv::Val getClassStringObjectType() const { return unionTypes(UnionMemberOp::call(PT_LC("getclassstringobjecttype"))); }
	zv::Val getObjectTypeOrClassStringObjectType() const { return unionTypes(UnionMemberOp::call(PT_LC("getobjecttypeorclassstringobjecttype"))); }
	zend_long isVoid() const { return unionResults(UnionMemberOp::call(PT_LC("isvoid"))); }
	zend_long isScalar() const { return unionResults(UnionMemberOp::call(PT_LC("isscalar"))); }

	/* notBenevolentUnionResults(looseCompare()->toTrinaryLogic())->toBooleanType() */
	zv::Val looseCompare(zval *type, zval *phpVersion) const
	{
		zv::Args args{type, phpVersion};
		return booleanTypeOf(notBenevolentUnionResults(UnionMemberOp::looseCompare(args)));
	}

	zend_long isOffsetAccessible() const { return unionResults(UnionMemberOp::call(PT_LC("isoffsetaccessible"))); }
	zend_long isOffsetAccessLegal() const { return unionResults(UnionMemberOp::call(PT_LC("isoffsetaccesslegal"))); }
	zend_long hasOffsetValueType(zval *offsetType) const { return unionResults(UnionMemberOp::call(PT_LC("hasoffsetvaluetype"), 1, offsetType)); }

	/* the union of the members' offset value types that are no ErrorType,
	 * an ErrorType when none is; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr valueTypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val valueType = callType(entry.value().deref().asObject(), PT_LC("getoffsetvaluetype"), 1, offsetType);
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			bool isError;
			if (UNEXPECTED(!isInstance(valueType.raw(), pt_ce_error_type, isError))) return zv::Val();
			if (isError) continue;
			valueTypes.push(std::move(valueType));
		}
		if (zend_hash_num_elements(valueTypes.table()) == 0) return errorType();
		return combinatorUnion(valueTypes.table());
	}

	zv::Val setOffsetValueType(zval *offsetType, zval *valueType, bool unionValues) const
	{
		zval args[3];
		if (offsetType == NULL) {
			ZVAL_NULL(&args[0]);
		} else {
			ZVAL_COPY_VALUE(&args[0], offsetType);
		}
		ZVAL_COPY_VALUE(&args[1], valueType);
		ZVAL_BOOL(&args[2], unionValues);
		return unionTypes(UnionMemberOp::call(PT_LC("setoffsetvaluetype"), 3, args));
	}

	zv::Val setExistingOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Args args{offsetType, valueType};
		return unionTypes(UnionMemberOp::call(PT_LC("setexistingoffsetvaluetype"), 2, args));
	}

	zv::Val unsetOffset(zval *offsetType) const { return unionTypes(UnionMemberOp::call(PT_LC("unsetoffset"), 1, offsetType)); }

	zv::Val getKeysArrayFiltered(zval *filterValueType, zval *strict) const
	{
		zv::Args args{filterValueType, strict};
		return unionTypes(UnionMemberOp::call(PT_LC("getkeysarrayfiltered"), 2, args));
	}

	zv::Val getKeysArray() const { return unionTypes(UnionMemberOp::call(PT_LC("getkeysarray"))); }
	zv::Val getValuesArray() const { return unionTypes(UnionMemberOp::call(PT_LC("getvaluesarray"))); }

	zv::Val chunkArray(zval *lengthType, zval *preserveKeys) const
	{
		zv::Args args{lengthType, preserveKeys};
		return unionTypes(UnionMemberOp::call(PT_LC("chunkarray"), 2, args));
	}

	zv::Val fillKeysArray(zval *valueType) const { return unionTypes(UnionMemberOp::call(PT_LC("fillkeysarray"), 1, valueType)); }
	zv::Val flipArray() const { return unionTypes(UnionMemberOp::call(PT_LC("fliparray"))); }
	zv::Val intersectKeyArray(zval *otherArraysType) const { return unionTypes(UnionMemberOp::call(PT_LC("intersectkeyarray"), 1, otherArraysType)); }
	zv::Val popArray() const { return unionTypes(UnionMemberOp::call(PT_LC("poparray"))); }
	zv::Val reverseArray(zval *preserveKeys) const { return unionTypes(UnionMemberOp::call(PT_LC("reversearray"), 1, preserveKeys)); }

	zv::Val searchArray(zval *needleType, zval *strict) const
	{
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], needleType);
		if (strict == NULL) {
			ZVAL_NULL(&args[1]);
		} else {
			ZVAL_COPY_VALUE(&args[1], strict);
		}
		return unionTypes(UnionMemberOp::call(PT_LC("searcharray"), 2, args));
	}

	zv::Val shiftArray() const { return unionTypes(UnionMemberOp::call(PT_LC("shiftarray"))); }
	zv::Val shuffleArray() const { return unionTypes(UnionMemberOp::call(PT_LC("shufflearray"))); }

	zv::Val sliceArray(zval *offsetType, zval *lengthType, zval *preserveKeys) const
	{
		zv::Args args{offsetType, lengthType, preserveKeys};
		return unionTypes(UnionMemberOp::call(PT_LC("slicearray"), 3, args));
	}

	zv::Val spliceArray(zval *offsetType, zval *lengthType, zval *replacementType) const
	{
		zv::Args args{offsetType, lengthType, replacementType};
		return unionTypes(UnionMemberOp::call(PT_LC("splicearray"), 3, args));
	}

	zv::Val truncateListToSize(zval *sizeType) const { return unionTypes(UnionMemberOp::call(PT_LC("truncatelisttosize"), 1, sizeType)); }
	zv::Val makeListMaybe() const { return unionTypes(UnionMemberOp::call(PT_LC("makelistmaybe"))); }
	zv::Val mapValueType(zval *cb) const { return unionTypes(UnionMemberOp::call(PT_LC("mapvaluetype"), 1, cb)); }
	zv::Val mapKeyType(zval *cb) const { return unionTypes(UnionMemberOp::call(PT_LC("mapkeytype"), 1, cb)); }
	zv::Val makeAllArrayKeysOptional() const { return unionTypes(UnionMemberOp::call(PT_LC("makeallarraykeysoptional"))); }
	zv::Val changeKeyCaseArray(zval *caseArg) const { return unionTypes(UnionMemberOp::call(PT_LC("changekeycasearray"), 1, caseArg)); }
	zv::Val filterArrayRemovingFalsey() const { return unionTypes(UnionMemberOp::call(PT_LC("filterarrayremovingfalsey"))); }

	/* the single enum case of $this->getEnumCases(), null otherwise */
	zv::Val getEnumCaseObject() const
	{
		zv::Val cases = thisGetEnumCases();
		if (UNEXPECTED(cases.isUndef())) return zv::Val();
		if (zend_hash_num_elements(zv::ArrRef(cases.raw()).table()) == 1) {
			zval *first = zend_hash_index_find(zv::ArrRef(cases.raw()).table(), 0);
			if (first != NULL) return zv::Val::copyOf(zv::Ref(first));
		}
		return zv::Val::null();
	}

	/* $this->isCallable ??= $this->unionResults(isCallable); -1 = pending
	 * exception */
	[[nodiscard]] zend_long isCallable() const { return memoizedTrinary(slots::isCallable, UnionMemberOp::call(PT_LC("iscallable")), false); }

	/* array_merge of the acceptors of the members that may be callable;
	 * none throws; UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors(zval *scope) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr acceptors = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zend_long callable = pt_type_op_trinary(type, PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(callable < 0)) return zv::Val();
			if (callable == PT_TRI_NO) continue;
			zv::Val inner = pt_type_call_array(type, PT_LC("getcallableparametersacceptors"), 1, scope);
			if (UNEXPECTED(inner.isUndef())) return zv::Val();
			for (zv::ArrayEntry acceptor : zv::ArrRef(inner.raw())) {
				acceptors.push(acceptor.value());
			}
		}
		if (zend_hash_num_elements(acceptors.table()) == 0) {
			throwShouldNotHappen(NULL);
			return zv::Val();
		}
		return zv::Val(std::move(acceptors));
	}

	zend_long isCloneable() const { return unionResults(UnionMemberOp::call(PT_LC("iscloneable"))); }

	zend_long isSmallerThan(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("issmallerthan"), 2, args));
	}

	zend_long isSmallerThanOrEqual(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("issmallerthanorequal"), 2, args));
	}

	/* $this->isNull ??= $this->notBenevolentUnionResults(isNull) */
	zend_long isNull() const { return memoizedTrinary(slots::isNull, UnionMemberOp::call(PT_LC("isnull")), true); }

	zend_long isConstantValue() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isconstantvalue"))); }
	zend_long isConstantScalarValue() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isconstantscalarvalue"))); }
	zv::Val getConstantScalarTypes() const { return notBenevolentPickFromTypes(UnionMemberOp::call(PT_LC("getconstantscalartypes"))); }
	zv::Val getConstantScalarValues() const { return notBenevolentPickFromTypes(UnionMemberOp::call(PT_LC("getconstantscalarvalues"))); }
	zend_long isTrue() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("istrue"))); }
	zend_long isFalse() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isfalse"))); }
	zend_long isBoolean() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isboolean"))); }
	zend_long isFloat() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isfloat"))); }
	zend_long isInteger() const { return notBenevolentUnionResults(UnionMemberOp::call(PT_LC("isinteger"))); }

	/* $this->isInteger() through the object's class entry */
	zend_long thisIsInteger() const
	{
		if (EXPECTED(own(PT_LC("isinteger"), utIsInteger))) return isInteger();
		return pt_type_op_trinary(self, PT_OP_IS_INTEGER, 0, NULL);
	}

	zv::Val getSmallerType(zval *phpVersion) const { return unionTypes(UnionMemberOp::call(PT_LC("getsmallertype"), 1, phpVersion)); }
	zv::Val getSmallerOrEqualType(zval *phpVersion) const { return unionTypes(UnionMemberOp::call(PT_LC("getsmallerorequaltype"), 1, phpVersion)); }
	zv::Val getGreaterType(zval *phpVersion) const { return unionTypes(UnionMemberOp::call(PT_LC("getgreatertype"), 1, phpVersion)); }
	zv::Val getGreaterOrEqualType(zval *phpVersion) const { return unionTypes(UnionMemberOp::call(PT_LC("getgreaterorequaltype"), 1, phpVersion)); }

	/* $otherType->isSmallerThan($type, $phpVersion) per member */
	zend_long isGreaterThan(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return notBenevolentUnionResults(UnionMemberOp::reversed(PT_LC("issmallerthan"), 2, args));
	}

	zend_long isGreaterThanOrEqual(zval *otherType, zval *phpVersion) const
	{
		zv::Args args{otherType, phpVersion};
		return notBenevolentUnionResults(UnionMemberOp::reversed(PT_LC("issmallerthanorequal"), 2, args));
	}

	/* the twin's `: BooleanType` return type, checked as the engine checks
	 * it — a subclass's unionTypes() may hand back anything */
	zv::Val toBoolean() const
	{
		zv::Val type = unionTypes(UnionMemberOp::call(PT_LC("toboolean")));
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(type.raw()).instanceOf(pt_ce_boolean_type))) {
			zend_type_error("%s::toBoolean(): Return value must be of type %s, %s returned", ZSTR_VAL(pt_ce_union_type->name), ZSTR_VAL(pt_ce_boolean_type->name), ZSTR_VAL(Z_OBJCE_P(type.raw())->name));
			return zv::Val();
		}
		return type;
	}
	zv::Val toNumber() const { return unionTypes(UnionMemberOp::call(PT_LC("tonumber"))); }
	zv::Val toBitwiseNotType() const { return unionTypes(UnionMemberOp::call(PT_LC("tobitwisenottype"))); }
	zv::Val toGetClassResultType() const { return unionTypes(UnionMemberOp::call(PT_LC("togetclassresulttype"))); }
	zv::Val toClassConstantType(zval *reflectionProvider) const { return unionTypes(UnionMemberOp::call(PT_LC("toclassconstanttype"), 1, reflectionProvider)); }

	/* new ClassNameToObjectTypeResult(TypeCombinator::union(...$types),
	 * $uncertainty) over the members' results; UNDEF = pending exception */
	zv::Val objectTypeForCheck(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr resultTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
		bool uncertainty = false;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val result = callType(entry.value().deref().asObject(), lcname, len, argc, argv);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *resultType = zend_read_property(Z_OBJCE_P(result.raw()), Z_OBJ_P(result.raw()), PT_LC("type"), 0, &rv);
			if (UNEXPECTED(resultType == NULL || EG(exception))) return zv::Val();
			resultTypes.push(zv::Ref(resultType).deref());
			zval_ptr_dtor(&rv);
			ZVAL_UNDEF(&rv);
			zval *resultUncertainty = zend_read_property(Z_OBJCE_P(result.raw()), Z_OBJ_P(result.raw()), PT_LC("uncertainty"), 0, &rv);
			if (UNEXPECTED(resultUncertainty == NULL || EG(exception))) return zv::Val();
			bool uncertain = zend_is_true(resultUncertainty);
			zval_ptr_dtor(&rv);
			if (!uncertain) continue;
			uncertainty = true;
		}
		zv::Val unionType = combinatorUnion(resultTypes.table());
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		zv::Args args{unionType.raw(), uncertainty};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	zv::Val toObjectTypeForInstanceofCheck() const { return objectTypeForCheck(PT_LC("toobjecttypeforinstanceofcheck"), 0, NULL); }

	zv::Val toObjectTypeForIsACheck(zval *objectOrClassType, bool allowString, bool allowSameClass) const
	{
		zv::Args args{objectOrClassType, allowString, allowSameClass};
		return objectTypeForCheck(PT_LC("toobjecttypeforisacheck"), 3, args);
	}

	zv::Val toAbsoluteNumber() const { return unionTypes(UnionMemberOp::call(PT_LC("toabsolutenumber"))); }
	zv::Val toString() const { return unionTypes(UnionMemberOp::call(PT_LC("tostring"))); }
	zv::Val toInteger() const { return unionTypes(UnionMemberOp::call(PT_LC("tointeger"))); }
	zv::Val toFloat() const { return unionTypes(UnionMemberOp::call(PT_LC("tofloat"))); }
	zv::Val toArray() const { return unionTypes(UnionMemberOp::call(PT_LC("toarray"))); }

	/* every member's toArrayKey(), the StringType members kept as they are
	 * under the PREVENT casting level when $this may be an integer; UNDEF =
	 * pending exception */
	zv::Val toArrayKey() const
	{
		zv::Val level = pt_type_call_static(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE, PT_LC("getlevel"), 0, NULL);
		if (UNEXPECTED(level.isUndef())) return zv::Val();
		zval *prevent = classConstant(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE, PT_LC("PREVENT"));
		if (UNEXPECTED(prevent == NULL)) return zv::Val();
		bool keepStrings = sameString(level.raw(), prevent);
		if (keepStrings) {
			zend_long isInteger = thisIsInteger();
			if (UNEXPECTED(isInteger < 0)) return zv::Val();
			keepStrings = isInteger != PT_TRI_NO;
		}
		if (!keepStrings) return unionTypes(UnionMemberOp::call(PT_LC("toarraykey")));
		return unionTypes(UnionMemberOp::toArrayKeyKeepingStrings());
	}

	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		zval strict;
		ZVAL_BOOL(&strict, strictTypes);
		return unionTypes(UnionMemberOp::call(PT_LC("tocoercedargumenttype"), 1, &strict));
	}

	/* inferTemplateTypes(): a received member some member definitely accepts
	 * is absorbed by it (never excepted), only the rest is inferred against
	 * the non-naked-template members, then — when that gave nothing —
	 * against every member; UNDEF = pending exception */
	zv::Val inferTemplateTypes(zval *receivedTypeArg) const
	{
		zv::Val receivedType = zv::Val::copyOf(zv::Ref(receivedTypeArg));
		bool isIterable;
		if (UNEXPECTED(!isInstance(receivedType.raw(), pt_ce_iterable_type, isIterable))) return zv::Val();
		if (isIterable) {
			receivedType = callType(Z_OBJ_P(receivedType.raw()), PT_LC("toarrayortraversable"), 0, NULL);
			if (UNEXPECTED(receivedType.isUndef())) return zv::Val();
		}

		zv::Val types = pt_type_template_type_map_empty();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val receivedTypes;
		if (zv::Ref(receivedType.raw()).instanceOf(pt_ce_union_type)) {
			receivedTypes = getTypesOf(Z_OBJ_P(receivedType.raw()));
			if (UNEXPECTED(receivedTypes.isUndef())) return zv::Val();
		} else {
			zv::Arr single = zv::Arr::create(1);
			single.push(zv::Ref(receivedType.raw()));
			receivedTypes = zv::Val(std::move(single));
		}
		zval *ownTypes = this->types();
		if (UNEXPECTED(ownTypes == NULL)) return zv::Val();
		zv::Val ownTypesCopy = zv::Val::copyOf(zv::Ref(ownTypes));
		zv::Arr remainingReceivedTypes = zv::Arr::create(0);
		for (zv::ArrayEntry received : zv::ArrRef(receivedTypes.raw())) {
			zval *receivedInnerType = received.value().deref().raw();
			if (zv::Ref(receivedInnerType).instanceOf(pt_ce_never_type)) {
				remainingReceivedTypes.push(zv::Ref(receivedInnerType));
				continue;
			}
			bool absorbed = false;
			for (zv::ArrayEntry entry : zv::ArrRef(ownTypesCopy.raw())) {
				zend_object *type = entry.value().deref().asObject();
				zend_long isSuperType = pt_type_call_result_trinary(type, PT_LC("issupertypeof"), 1, receivedInnerType);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType != PT_TRI_YES) continue;
				zv::Val inferred = pt_type_call(type, PT_LC("infertemplatetypes"), 1, receivedInnerType);
				if (UNEXPECTED(inferred.isUndef())) return zv::Val();
				types = mapUnion(std::move(types), inferred.raw());
				if (UNEXPECTED(types.isUndef())) return zv::Val();
				absorbed = true;
				break;
			}
			if (!absorbed) {
				remainingReceivedTypes.push(zv::Ref(receivedInnerType));
			}
		}
		uint32_t remaining = zend_hash_num_elements(remainingReceivedTypes.table());
		if (remaining == 0) {
			zv::Val absorbed = getAbsorbedTemplateTypes(types.raw());
			if (UNEXPECTED(absorbed.isUndef())) return zv::Val();
			return mapUnion(std::move(types), absorbed.raw());
		}
		if (remaining != zv::ArrRef(receivedTypes.raw()).size()) {
			receivedType = combinatorUnion(remainingReceivedTypes.table());
			if (UNEXPECTED(receivedType.isUndef())) return zv::Val();
		}

		for (zv::ArrayEntry entry : zv::ArrRef(ownTypesCopy.raw())) {
			zend_object *type = entry.value().deref().asObject();
			zv::Val naked = getNakedTemplateType(entry.value().deref().raw());
			if (UNEXPECTED(naked.isUndef())) return zv::Val();
			if (!zv::Ref(naked.raw()).isNull()) continue;
			zv::Val inferred = pt_type_call(type, PT_LC("infertemplatetypes"), 1, receivedType.raw());
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			types = mapUnion(std::move(types), inferred.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}

		int empty = pt_type_call_is_true(Z_OBJ_P(types.raw()), PT_LC("isempty"), 0, NULL);
		if (UNEXPECTED(empty < 0)) return zv::Val();
		if (empty == 0) return types;

		for (zv::ArrayEntry entry : zv::ArrRef(ownTypesCopy.raw())) {
			zv::Val inferred = pt_type_call(entry.value().deref().asObject(), PT_LC("infertemplatetypes"), 1, receivedType.raw());
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			types = mapUnion(std::move(types), inferred.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}

		return types;
	}

	/* the TemplateType a member is, or wraps as a GenericClassStringType's
	 * generic type; null otherwise; UNDEF = pending exception */
	static zv::Val getNakedTemplateType(zval *type)
	{
		bool isTemplate;
		if (UNEXPECTED(!isInstance(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) return zv::Val::copyOf(zv::Ref(type));
		if (zv::Ref(type).instanceOf(pt_ce_generic_class_string_type)) {
			zv::Val genericType = callType(Z_OBJ_P(type), PT_LC("getgenerictype"), 0, NULL);
			if (UNEXPECTED(genericType.isUndef())) return zv::Val();
			if (UNEXPECTED(!isInstance(genericType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (isTemplate) return genericType;
		}
		return zv::Val::null();
	}

	/* the naked template members the absorption left without a type, as
	 * AbsorbedTemplateArgumentTypes in a TemplateTypeMap (the empty map when
	 * there are none); UNDEF = pending exception */
	zv::Val getAbsorbedTemplateTypes(zval *inferred) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr absorbed = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val templateType = getNakedTemplateType(entry.value().deref().raw());
			if (UNEXPECTED(templateType.isUndef())) return zv::Val();
			if (zv::Ref(templateType.raw()).isNull()) continue;
			zv::Val name = pt_type_call(Z_OBJ_P(templateType.raw()), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
				zend_type_error("phpstan_turbo: getName() must return string");
				return zv::Val();
			}
			int hasType = pt_type_call_is_true(Z_OBJ_P(inferred), PT_LC("hastype"), 1, name.raw());
			if (UNEXPECTED(hasType < 0)) return zv::Val();
			if (hasType == 1) continue;
			zval argumentRaw;
			if (UNEXPECTED(!pt_absorbed_template_argument_type_new(&argumentRaw))) return zv::Val();
			zv::Val argument = zv::Val::adopt(argumentRaw);
			absorbed.set(zv::Ref(name.raw()).asString(), std::move(argument));
		}
		if (zend_hash_num_elements(absorbed.table()) == 0) return pt_type_template_type_map_empty();
		return pt_type_template_type_map_new(absorbed.raw());
	}

	/* the union of $templateType->inferTemplateTypes() over every member */
	zv::Val inferTemplateTypesOn(zval *templateType) const
	{
		zv::Val types = pt_type_template_type_map_empty();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zval *ownTypes = this->types();
		if (UNEXPECTED(ownTypes == NULL)) return zv::Val();
		zv::Val ownTypesCopy = zv::Val::copyOf(zv::Ref(ownTypes));
		for (zv::ArrayEntry entry : zv::ArrRef(ownTypesCopy.raw())) {
			zv::Val inferred = pt_type_call(Z_OBJ_P(templateType), PT_LC("infertemplatetypes"), 1, entry.value().deref().raw());
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			types = mapUnion(std::move(types), inferred.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}
		return types;
	}

	/* the concatenation of every member's references */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		return concatOf(UnionMemberOp::call(PT_LC("getreferencedtemplatetypes"), 1, positionVariance));
	}

	/* TypeCombinator::union() of $cb over every member when any changed,
	 * $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			zval arg;
			ZVAL_COPY_VALUE(&arg, type);
			zval newType;
			if (UNEXPECTED(!pt_call_type_fci(fci, fcc, 1, &arg, &newType))) return zv::Val();
			if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(type)) {
				changed = true;
			}
			newTypes.push(zv::Val::adopt(newType));
		}
		if (changed) return combinatorUnion(newTypes.table());
		return thisValue();
	}

	/* each member paired with the flattened members of $right it is a
	 * supertype of (each right member consumed once), $cb applied to the
	 * pairs; the union when any changed, $this otherwise; UNDEF = pending
	 * exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val rightTypes = pt_type_utils_flatten_types(right);
		if (UNEXPECTED(rightTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(rightTypes.raw()).isArray())) {
			zend_type_error("phpstan_turbo: TypeUtils::flattenTypes() must return array");
			return zv::Val();
		}
		uint32_t rightCount = zv::ArrRef(rightTypes.raw()).size();
		zval **rights = (zval **) safe_emalloc(rightCount > 0 ? rightCount : 1, sizeof(zval *), 0);
		bool *used = (bool *) ecalloc(rightCount > 0 ? rightCount : 1, sizeof(bool));
		uint32_t n = 0;
		for (zv::ArrayEntry entry : zv::ArrRef(rightTypes.raw())) {
			rights[n++] = entry.value().deref().raw();
		}

		zv::Val result;
		zval *types = this->types();
		if (types != NULL) {
			zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
			zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
			bool changed = false;
			bool ok = true;
			for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
				zval *innerType = entry.value().deref().raw();
				zv::Arr candidates = zv::Arr::create(0);
				for (uint32_t j = 0; j < n; j++) {
					if (used[j]) continue;
					zend_long isSuperType = pt_type_call_result_trinary(Z_OBJ_P(innerType), PT_LC("issupertypeof"), 1, rights[j]);
					if (UNEXPECTED(isSuperType < 0)) {
						ok = false;
						break;
					}
					if (isSuperType != PT_TRI_YES) continue;
					candidates.push(zv::Ref(rights[j]));
					used[j] = true;
				}
				if (!ok) break;
				if (zend_hash_num_elements(candidates.table()) == 0) {
					newTypes.push(zv::Ref(innerType));
					continue;
				}
				zv::Val candidate = combinatorUnion(candidates.table());
				if (UNEXPECTED(candidate.isUndef())) {
					ok = false;
					break;
				}
				zv::Args args{innerType, candidate.raw()};
				zval newType;
				if (UNEXPECTED(!pt_call_type_fci(fci, fcc, 2, args, &newType))) {
					ok = false;
					break;
				}
				if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(innerType)) {
					changed = true;
				}
				newTypes.push(zv::Val::adopt(newType));
			}
			if (ok) {
				result = changed ? combinatorUnion(newTypes.table()) : thisValue();
			}
		}
		efree(rights);
		efree(used);
		return result;
	}

	/* tryRemove(): over the finite set when it is complete (a value or a
	 * union of values removed in one pass), else TypeCombinator::remove()
	 * per member with the results' union members flattened; null when
	 * nothing changed; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val finiteTypeSet = thisGetFiniteTypeSet();
		if (UNEXPECTED(finiteTypeSet.isUndef())) return zv::Val();
		if (!zv::Ref(finiteTypeSet.raw()).isNull()) {
			zend_object *set = Z_OBJ_P(finiteTypeSet.raw());
			int complete = pt_type_call_is_true(set, PT_LC("iscomplete"), 0, NULL);
			if (UNEXPECTED(complete < 0)) return zv::Val();
			if (complete == 1) {
				zv::Val key = finiteKey(typeToRemove);
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				if (zv::Ref(key.raw()).isString()) {
					int has = pt_type_call_is_true(set, PT_LC("has"), 1, key.raw());
					if (UNEXPECTED(has < 0)) return zv::Val();
					if (has == 0) return zv::Val::null();
					zv::Val members = pt_type_call_array(set, PT_LC("getmembers"), 0, NULL);
					if (UNEXPECTED(members.isUndef())) return zv::Val();
					zv::Arr remainingTypes = zv::Arr::create(0);
					for (zv::ArrayEntry entry : zv::ArrRef(members.raw())) {
						zend_string *memberKey = entry.stringKeyOrNull();
						if (memberKey != NULL && zend_string_equals(memberKey, zv::Ref(key.raw()).asString())) continue;
						remainingTypes.push(entry.value());
					}
					return unionOrSingle(std::move(remainingTypes), false);
				}

				if (zv::Ref(typeToRemove).instanceOf(pt_ce_union_type)) {
					zv::Val innerTypesToRemove = getTypesOf(Z_OBJ_P(typeToRemove));
					if (UNEXPECTED(innerTypesToRemove.isUndef())) return zv::Val();
					zv::ScratchTable keysToRemove(zv::ArrRef(innerTypesToRemove.raw()).size());
					bool keyed = true;
					for (zv::ArrayEntry entry : zv::ArrRef(innerTypesToRemove.raw())) {
						zv::Val innerKey = finiteKey(entry.value().deref().raw());
						if (UNEXPECTED(innerKey.isUndef())) return zv::Val();
						if (!zv::Ref(innerKey.raw()).isString()) {
							keyed = false;
							break;
						}
						zend_hash_add_empty_element(keysToRemove.table(), zv::Ref(innerKey.raw()).asString());
					}
					if (keyed) {
						zv::Val members = pt_type_call_array(set, PT_LC("getmembers"), 0, NULL);
						if (UNEXPECTED(members.isUndef())) return zv::Val();
						zv::Arr remainingTypes = zv::Arr::create(0);
						bool removedAny = false;
						for (zv::ArrayEntry entry : zv::ArrRef(members.raw())) {
							zend_string *memberKey = entry.stringKeyOrNull();
							if (memberKey != NULL && zend_hash_exists(keysToRemove.table(), memberKey)) {
								removedAny = true;
								continue;
							}
							remainingTypes.push(entry.value());
						}
						if (!removedAny) return zv::Val::null();
						return unionOrSingle(std::move(remainingTypes), true);
					}
				}
			}
		}

		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr innerTypes = zv::Arr::create(0);
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *innerType = entry.value().deref().raw();
			zv::Val removed = combinator2(PT_LC("remove"), innerType, typeToRemove);
			if (UNEXPECTED(removed.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(removed.raw()).isObject())) {
				zend_type_error("phpstan_turbo: TypeCombinator::remove() must return %s", ptcls::type);
				return zv::Val();
			}
			int equal = pt_type_call_is_true(Z_OBJ_P(removed.raw()), PT_LC("equals"), 1, innerType);
			if (UNEXPECTED(equal < 0)) return zv::Val();
			if (equal == 0) {
				changed = true;
			}
			if (zv::Ref(removed.raw()).instanceOf(pt_ce_never_type)) continue;
			if (zv::Ref(removed.raw()).instanceOf(pt_ce_union_type)) {
				bool isTemplate;
				if (UNEXPECTED(!isInstance(removed.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
				if (!isTemplate) {
					zv::Val removedTypes = getTypesOf(Z_OBJ_P(removed.raw()));
					if (UNEXPECTED(removedTypes.isUndef())) return zv::Val();
					for (zv::ArrayEntry removedEntry : zv::ArrRef(removedTypes.raw())) {
						innerTypes.push(removedEntry.value());
					}
					continue;
				}
			}
			innerTypes.push(std::move(removed));
		}
		if (!changed) return zv::Val::null();
		return unionOrSingle(std::move(innerTypes), true);
	}

	zv::Val exponentiate(zval *exponent) const { return unionTypes(UnionMemberOp::call(PT_LC("exponentiate"), 1, exponent)); }

	/* $this->finiteTypes ??= the members' finite types uniqued by their
	 * value key (their cache description without one), [] beyond
	 * InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT; UNDEF = pending
	 * exception */
	zv::Val getFiniteTypes() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::finiteTypes);
		if (Z_TYPE_P(slot) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(slot));
		zv::Val types = notBenevolentPickFromTypes(UnionMemberOp::call(PT_LC("getfinitetypes")));
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr uniquedTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
		zv::Val cacheLevel;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val key = finiteKey(type);
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			if (!zv::Ref(key.raw()).isString()) {
				if (cacheLevel.isUndef()) {
					cacheLevel = verbosityLevel(PT_VERBOSITY_LEVEL_CACHE);
					if (UNEXPECTED(cacheLevel.isUndef())) return zv::Val();
				}
				key = describeOf(type, cacheLevel.raw());
				if (UNEXPECTED(key.isUndef())) return zv::Val();
			}
			uniquedTypes.set(zv::Ref(key.raw()).asString(), zv::Val::copyOf(zv::Ref(type)));
		}
		zv::Val result;
		if ((zend_long) zend_hash_num_elements(uniquedTypes.table()) > PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT) {
			result = zv::Val(zv::Arr::empty());
		} else {
			zv::Arr values = zv::Arr::create(zend_hash_num_elements(uniquedTypes.table()));
			for (zv::ArrayEntry entry : uniquedTypes.arrRef()) {
				values.push(entry.value());
			}
			result = zv::Val(std::move(values));
		}
		slot = OBJ_PROP_NUM(self, slots::finiteTypes);
		zv::Ref(slot).assign(zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* new UnionTypeNode(array_map(toPhpDocNode, $this->getSortedTypes())) */
	zv::Val toPhpDocNode() const
	{
		zv::Val sorted = thisGetSortedTypes();
		if (UNEXPECTED(sorted.isUndef())) return zv::Val();
		zv::Arr nodes = zv::Arr::create(zv::ArrRef(sorted.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(sorted.raw())) {
			zv::Val node = pt_type_call(entry.value().deref().asObject(), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			nodes.push(std::move(node));
		}
		return pt_type_new(PT_CLASS_UNION_TYPE_NODE, 1, nodes.raw());
	}

	/* whether any member has one; -1 = pending exception */
	int hasTemplateOrLateResolvableType() const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			int has = pt_type_call_is_true(entry.value().deref().asObject(), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
			if (UNEXPECTED(has < 0)) return -1;
			if (has == 0) continue;
			return 1;
		}
		return 0;
	}

	/* the three protected methods: this class's own body when the object's
	 * class has not overridden them, BenevolentUnionType's when it did,
	 * anything else through the method with the op as a callable */

	/* TrinaryLogic::lazyExtremeIdentity($this->types, $getResult); -1 =
	 * pending exception */
	zend_long unionResults(const UnionMemberOp &op) const
	{
		if (EXPECTED(own(PT_LC("unionresults"), utUnionResults))) return unionResultsImpl(op);
		if (pt_type_method_is(self, PT_LC("unionresults"), pt_union_benevolent_union_results_handler())) {
			return trinaryOf(pt_union_benevolent_union_results(self, op));
		}
		zv::Val callback = pt_union_op_callback(op);
		if (UNEXPECTED(callback.isUndef())) return -1;
		return pt_type_call_trinary(self, PT_LC("unionresults"), 1, callback.raw());
	}

	/* the same, never the benevolent override (a private helper of the twin) */
	zend_long notBenevolentUnionResults(const UnionMemberOp &op) const { return unionResultsImpl(op); }

	/* TypeCombinator::union() of $getType over every member when any
	 * changed, $this otherwise; UNDEF = pending exception */
	zv::Val unionTypes(const UnionMemberOp &op) const
	{
		if (EXPECTED(own(PT_LC("uniontypes"), utUnionTypes))) return unionTypesImpl(op);
		if (pt_type_method_is(self, PT_LC("uniontypes"), pt_union_benevolent_union_types_handler())) return pt_union_benevolent_union_types(self, op);
		zv::Val callback = pt_union_op_callback(op);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return callType(self, PT_LC("uniontypes"), 1, callback.raw());
	}

	/* the members' values concatenated, [] as soon as one member has none;
	 * UNDEF = pending exception */
	zv::Val pickFromTypes(const UnionMemberOp &op, const UnionCriteria &criteria) const
	{
		if (EXPECTED(own(PT_LC("pickfromtypes"), utPickFromTypes))) return pickFromTypesImpl(op);
		if (pt_type_method_is(self, PT_LC("pickfromtypes"), pt_union_benevolent_pick_from_types_handler())) {
			return pt_union_benevolent_pick_from_types(self, op, criteria);
		}
		zv::Val callback = pt_union_op_callback(op);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Val criteriaCallback = pt_union_criteria_callback(criteria);
		if (UNEXPECTED(criteriaCallback.isUndef())) return zv::Val();
		zv::Args args{callback.raw(), criteriaCallback.raw()};
		return pt_type_call_array(self, PT_LC("pickfromtypes"), 2, args);
	}

	zv::Val notBenevolentPickFromTypes(const UnionMemberOp &op) const { return pickFromTypesImpl(op); }

	zend_long unionResultsImpl(const UnionMemberOp &op) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return -1;
		if (UNEXPECTED(zend_hash_num_elements(Z_ARRVAL_P(types)) == 0)) {
			throwShouldNotHappen(NULL);
			return -1;
		}
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zend_long last = -2;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zend_long result = trinaryOf(pt_union_apply_op(op, entry.value().deref().raw()));
			if (UNEXPECTED(result < 0)) return -1;
			if (last == -2) {
				last = result;
				continue;
			}
			if (last == result) continue;
			return PT_TRI_MAYBE;
		}
		return last;
	}

	zv::Val unionTypesImpl(const UnionMemberOp &op) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val newType = applyTypeOp(op, type);
			if (UNEXPECTED(newType.isUndef())) return zv::Val();
			if (Z_OBJ_P(newType.raw()) != Z_OBJ_P(type)) {
				changed = true;
			}
			newTypes.push(std::move(newType));
		}
		if (!changed) return thisValue();
		return combinatorUnion(newTypes.table());
	}

	zv::Val pickFromTypesImpl(const UnionMemberOp &op) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr values = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val innerValues = applyArrayOp(op, entry.value().deref().raw());
			if (UNEXPECTED(innerValues.isUndef())) return zv::Val();
			if (zend_hash_num_elements(zv::ArrRef(innerValues.raw()).table()) == 0) return zv::Val(zv::Arr::empty());
			for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
				values.push(value.value());
			}
		}
		return zv::Val(std::move(values));
	}

	/* $this->accepts($type, $strictTypes) through the object's class entry */
	zv::Val thisAccepts(zval *type, bool strictTypes) const
	{
		if (EXPECTED(own(PT_LC("accepts"), utAccepts))) return accepts(type, strictTypes);
		zv::Args args{type, strictTypes};
		return pt_type_op(self, PT_OP_ACCEPTS, 2, args);
	}

	/* the op applied to one member, the result checked to be a Type / an
	 * array as the twin's closures declare; UNDEF = pending exception */
	static zv::Val applyTypeOp(const UnionMemberOp &op, zval *type)
	{
		zv::Val result = pt_union_apply_op(op, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::%s() must return %s, %s returned", ZSTR_VAL(Z_OBJCE_P(type)->name), op.lcname, ptcls::type, zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}

	static zv::Val applyArrayOp(const UnionMemberOp &op, zval *type)
	{
		zv::Val result = pt_union_apply_op(op, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::%s() must return array, %s returned", ZSTR_VAL(Z_OBJCE_P(type)->name), op.lcname, zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}

private:
	zend_object *self;

	/* finiteTypeSetContainedIn()'s "the identity maps cannot settle it" */
	static constexpr zend_long CONTAINMENT_UNSETTLED = -2;

	/* exactly a UnionType, none of its methods overridden, or the method
	 * of that (lowercase) name still this class's own handler */
	bool own(const char *lcname, size_t len, zif_handler handler) const
	{
		return self->ce == pt_ce_union_type || pt_type_method_is(self, lcname, len, handler);
	}

	zv::Val thisValue() const { return pt_this_value(self); }

	/* FiniteTypeSet::key($type): a string or null; UNDEF = pending exception */
	static zv::Val finiteKey(zval *type)
	{
		zv::Val key = pt_type_finite_type_set_key(type);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(key.raw()).isString() && !zv::Ref(key.raw()).isNull())) {
			zend_type_error("phpstan_turbo: FiniteTypeSet::key() must return ?string");
			return zv::Val();
		}
		return key;
	}

	/* whether $otherType holds every member of this one: a PT_TRI_* value,
	 * CONTAINMENT_UNSETTLED when the identity maps alone cannot tell, -1 =
	 * pending exception */
	zend_long finiteTypeSetContainedIn(zval *otherType, bool yesOnly) const
	{
		zv::Val finiteTypeSet = thisGetFiniteTypeSet();
		if (UNEXPECTED(finiteTypeSet.isUndef())) return -1;
		if (zv::Ref(finiteTypeSet.raw()).isNull()) return CONTAINMENT_UNSETTLED;
		zend_object *set = Z_OBJ_P(finiteTypeSet.raw());
		int complete = pt_type_call_is_true(set, PT_LC("iscomplete"), 0, NULL);
		if (UNEXPECTED(complete < 0)) return -1;
		if (complete == 0) return CONTAINMENT_UNSETTLED;

		bool otherIsUnion = zv::Ref(otherType).instanceOf(pt_ce_union_type);
		if (otherIsUnion) {
			bool isTemplate;
			if (UNEXPECTED(!isInstance(otherType, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return -1;
			otherIsUnion = !isTemplate;
		}
		if (!otherIsUnion) {
			zv::Val key = finiteKey(otherType);
			if (UNEXPECTED(key.isUndef())) return -1;
			if (!zv::Ref(key.raw()).isString()) return CONTAINMENT_UNSETTLED;
			zend_long containment = pt_type_call_trinary(set, PT_LC("containedinkey"), 1, key.raw());
			if (UNEXPECTED(containment < 0)) return -1;
			if (containment != PT_TRI_YES && yesOnly) return CONTAINMENT_UNSETTLED;
			return containment;
		}

		zv::Val otherSet = finiteTypeSetOf(Z_OBJ_P(otherType));
		if (UNEXPECTED(otherSet.isUndef())) return -1;
		if (zv::Ref(otherSet.raw()).isNull()) return CONTAINMENT_UNSETTLED;
		zend_long containment = pt_type_call_trinary(set, PT_LC("containedin"), 1, otherSet.raw());
		if (UNEXPECTED(containment < 0)) return -1;
		if (containment != PT_TRI_YES) {
			if (yesOnly) return CONTAINMENT_UNSETTLED;
			int otherComplete = pt_type_call_is_true(Z_OBJ_P(otherSet.raw()), PT_LC("iscomplete"), 0, NULL);
			if (UNEXPECTED(otherComplete < 0)) return -1;
			if (otherComplete == 0) return CONTAINMENT_UNSETTLED;
		}
		return containment;
	}

	/* array_map(fn (Type $innerType) => $receiver->method($innerType, ...$rest), $this->types) */
	zv::Val mapReversed(const char *lcname, size_t len, zend_object *receiver, uint32_t restCount, zval *rest) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr results = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zval args[2];
			ZVAL_COPY_VALUE(&args[0], entry.value().deref().raw());
			for (uint32_t i = 0; i < restCount; i++) {
				ZVAL_COPY_VALUE(&args[1 + i], &rest[i]);
			}
			zv::Val result = pt_type_call(receiver, lcname, len, 1 + restCount, args);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			results.push(std::move(result));
		}
		return zv::Val(std::move(results));
	}

	/* the concatenation of an array-valued op over every member */
	zv::Val concatOf(const UnionMemberOp &op) const
	{
		zval *types = this->types();
		if (UNEXPECTED(types == NULL)) return zv::Val();
		zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
		zv::Arr values = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
			zv::Val innerValues = applyArrayOp(op, entry.value().deref().raw());
			if (UNEXPECTED(innerValues.isUndef())) return zv::Val();
			for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
				values.push(value.value());
			}
		}
		return zv::Val(std::move(values));
	}

	/* array_values(array_unique($strings)) */
	static zv::Val uniqueStrings(zv::ArrRef strings)
	{
		zv::ScratchTable seen(strings.size());
		zv::Arr values = zv::Arr::create(strings.size());
		for (zv::ArrayEntry entry : strings) {
			zv::Ref value = entry.value().deref();
			zend_string *tmp;
			zend_string *str = zval_get_tmp_string(value.raw(), &tmp);
			if (UNEXPECTED(EG(exception))) {
				zend_tmp_string_release(tmp);
				return zv::Val();
			}
			bool first = zend_hash_add_empty_element(seen.table(), str) != NULL;
			zend_tmp_string_release(tmp);
			if (!first) continue;
			values.push(value);
		}
		return zv::Val(std::move(values));
	}

	/* new AcceptsResult($result->result, $reasons) ($reasons consumed) */
	static zv::Val withReasons(zval *result, zv::Arr reasons)
	{
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *trinary;
		if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_accepts_result)) {
			trinary = OBJ_PROP_NUM(Z_OBJ_P(result), PT_RESULT_PROP_RESULT);
		} else {
			trinary = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("result"), 0, &rv);
			if (UNEXPECTED(trinary == NULL || EG(exception))) return zv::Val();
		}
		zval created;
		zval reasonsRaw = reasons.take();
		bool ok = pt_accepts_result_create(&created, trinary, &reasonsRaw);
		zval_ptr_dtor(&rv);
		if (UNEXPECTED(!ok)) return zv::Val();
		return zv::Val::adopt(created);
	}

	/* the PT_TRI_* value of an AcceptsResult-valued zv::Val; -1 = pending
	 * exception */
	[[nodiscard]] static zend_long acceptsTrinary(zv::Val result)
	{
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* $type->describe($level), a string; UNDEF = pending exception */
	static zv::Val describeOf(zval *type, zval *level)
	{
		zv::Val description = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}
		return description;
	}

	/* $a->union($b) on two TemplateTypeMaps ($a consumed) */
	static zv::Val mapUnion(zv::Val a, zval *b)
	{
		if (UNEXPECTED(!zv::Ref(a.raw()).isObject())) {
			zend_type_error("phpstan_turbo: inferTemplateTypes() must return TemplateTypeMap");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(a.raw()), PT_LC("union"), 1, b);
	}

	/* the memoized isNull()/isCallable(): the slot when set, else the
	 * computed value stored as the TrinaryLogic singleton; -1 = pending
	 * exception */
	[[nodiscard]] zend_long memoizedTrinary(uint32_t slotIndex, const UnionMemberOp &op, bool notBenevolent) const
	{
		zval *slot = OBJ_PROP_NUM(self, slotIndex);
		if (Z_TYPE_P(slot) == IS_OBJECT) return pt_type_trinary_value(slot);
		zend_long value = notBenevolent ? notBenevolentUnionResults(op) : unionResults(op);
		if (UNEXPECTED(value < 0)) return -1;
		slot = OBJ_PROP_NUM(self, slotIndex);
		zv::Ref(slot).assign(pt_type_trinary(value));
		return value;
	}

	/* new BooleanType() for maybe, new ConstantBooleanType(yes) otherwise
	 * — TrinaryLogic::toBooleanType(); UNDEF = pending exception */
	static zv::Val booleanTypeOf(zend_long value)
	{
		if (UNEXPECTED(value < 0)) return zv::Val();
		zval result;
		if (value == PT_TRI_MAYBE) {
			if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		} else if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value == PT_TRI_YES))) {
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	/* tryRemove()'s tail: never for no remaining member (when allowed), the
	 * single one as is, new UnionType($remaining) otherwise ($remaining
	 * consumed) */
	static zv::Val unionOrSingle(zv::Arr remaining, bool neverWhenEmpty)
	{
		uint32_t count = zend_hash_num_elements(remaining.table());
		if (count == 0 && neverWhenEmpty) return neverType();
		if (count == 1) return zv::Val::copyOf(remaining.arrRef().findIndex(0));
		return create(zv::Val(std::move(remaining)));
	}

	/* the constructor's ShouldNotHappenException: 'Cannot create <class>
	 * with: <the members described at the value level>' */
	static void throwCannotCreate(zval *types)
	{
		zv::Val value = verbosityLevel(PT_VERBOSITY_LEVEL_VALUE);
		if (UNEXPECTED(value.isUndef())) return;
		smart_str message = {NULL, 0};
		smart_str_appendl(&message, "Cannot create ", 14);
		smart_str_append(&message, pt_ce_union_type->name);
		smart_str_appendl(&message, " with: ", 7);
		bool first = true;
		for (zv::ArrayEntry entry : zv::ArrRef(types)) {
			zval *type = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				smart_str_free(&message);
				zend_type_error("phpstan_turbo: %s::__construct(): every member must be a %s, %s given", ZSTR_VAL(pt_ce_union_type->name), ptcls::type, zend_zval_value_name(type));
				return;
			}
			zv::Val description = describeOf(type, value.raw());
			if (UNEXPECTED(description.isUndef())) {
				smart_str_free(&message);
				return;
			}
			if (!first) {
				smart_str_appendl(&message, ", ", 2);
			}
			first = false;
			smart_str_append(&message, zv::Ref(description.raw()).asString());
		}
		smart_str_0(&message);
		zv::Val messageZv = zv::Val::adoptString(message.s);
		throwShouldNotHappen(messageZv.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::UnionCriteria;
using phpstanturbo::UnionMemberOp;
using phpstanturbo::UnionType;

/* {{{ shared with BenevolentUnionType.cpp and the other ports (TypeTraits.h) */

zv::Val pt_union_apply_op(const UnionMemberOp &op, zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: a union member must be a %s, %s given", ptcls::type, zend_zval_value_name(type));
		return zv::Val();
	}
	switch (op.kind) {
		case UnionMemberOp::Call:
			return pt_type_call(Z_OBJ_P(type), op.lcname, op.len, op.argc, op.argv);
		case UnionMemberOp::Reversed: {
			/* $argv[0]->method($type, ...$argv[1..]) */
			if (UNEXPECTED(op.argc == 0 || Z_TYPE_P(&op.argv[0]) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: the reversed member call needs an object receiver");
				return zv::Val();
			}
			ALLOCA_FLAG(use_heap);
			zval *args = (zval *) do_alloca(sizeof(zval) * op.argc, use_heap);
			ZVAL_COPY_VALUE(&args[0], type);
			for (uint32_t i = 1; i < op.argc; i++) {
				ZVAL_COPY_VALUE(&args[i], &op.argv[i]);
			}
			zv::Val result = pt_type_call(Z_OBJ_P(&op.argv[0]), op.lcname, op.len, op.argc, args);
			free_alloca(args, use_heap);
			return result;
		}
		case UnionMemberOp::LooseCompare: {
			/* $type->looseCompare($otherType, $phpVersion)->toTrinaryLogic() */
			zv::Val boolean = pt_type_call(Z_OBJ_P(type), op.lcname, op.len, op.argc, op.argv);
			if (UNEXPECTED(boolean.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(boolean.raw()).isObject())) {
				zend_type_error("phpstan_turbo: looseCompare() must return %s", ZSTR_VAL(pt_ce_boolean_type->name));
				return zv::Val();
			}
			return pt_type_call(Z_OBJ_P(boolean.raw()), PT_LC("totrinarylogic"), 0, NULL);
		}
		case UnionMemberOp::ToArrayKeyKeepingStrings:
		default:
			/* $type instanceof StringType ? $type : $type->toArrayKey() */
			if (instanceof_function(Z_OBJCE_P(type), pt_ce_string_type)) return zv::Val::copyOf(zv::Ref(type));
			return pt_type_op(Z_OBJ_P(type), PT_OP_TO_ARRAY_KEY, 0, NULL);
	}
}

bool pt_union_apply_criteria(const UnionCriteria &criteria, zval *type, bool &out)
{
	if (criteria.lcname == NULL) {
		out = false;
		return true;
	}
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: a union member must be a %s, %s given", ptcls::type, zend_zval_value_name(type));
		return false;
	}
	zend_long value = pt_type_call_trinary(Z_OBJ_P(type), criteria.lcname, criteria.len, 0, NULL);
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* the op as a PHP callable: state0 = [kind, lcname], state1 = the argument
 * array (null for none); the body rebuilds the op and applies it */
static void unionOpCallbackBody(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc != 1 || Z_TYPE_P(state0) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: the union member callback expects one type");
		return;
	}
	zval *kind = zend_hash_index_find(Z_ARRVAL_P(state0), 0);
	zval *lcname = zend_hash_index_find(Z_ARRVAL_P(state0), 1);
	if (UNEXPECTED(kind == NULL || lcname == NULL || Z_TYPE_P(kind) != IS_LONG || Z_TYPE_P(lcname) != IS_STRING)) {
		zend_type_error("phpstan_turbo: the union member callback holder is malformed");
		return;
	}
	UnionMemberOp op = { (UnionMemberOp::Kind) Z_LVAL_P(kind), Z_STRVAL_P(lcname), Z_STRLEN_P(lcname), 0, NULL };
	zv::Val result;
	if (Z_TYPE_P(state1) == IS_ARRAY) {
		phpstanturbo::SpreadArgs spread(Z_ARRVAL_P(state1));
		op.argc = spread.count;
		op.argv = spread.argv;
		result = pt_union_apply_op(op, &argv[0]);
	} else {
		result = pt_union_apply_op(op, &argv[0]);
	}
	if (UNEXPECTED(result.isUndef())) return;
	result.intoReturnValue(return_value);
}

zv::Val pt_union_op_callback(const UnionMemberOp &op)
{
	zv::Arr state0 = zv::Arr::create(2);
	state0.push(zv::Val::integer((zend_long) op.kind));
	state0.push(zv::Val::string(op.lcname, op.len));
	zv::Val state1;
	if (op.argc > 0) {
		zv::Arr args = zv::Arr::create(op.argc);
		for (uint32_t i = 0; i < op.argc; i++) {
			args.push(zv::Ref(&op.argv[i]));
		}
		state1 = zv::Val(std::move(args));
	} else {
		state1 = zv::Val::null();
	}
	return pt_type_native_callback(unionOpCallbackBody, state0.raw(), state1.raw());
}

static void unionCriteriaCallbackBody(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
{
	(void) state1;
	if (UNEXPECTED(argc != 1)) {
		zend_type_error("phpstan_turbo: the union criteria callback expects one type");
		return;
	}
	UnionCriteria criteria = { NULL, 0 };
	if (Z_TYPE_P(state0) == IS_STRING) {
		criteria.lcname = Z_STRVAL_P(state0);
		criteria.len = Z_STRLEN_P(state0);
	}
	bool out;
	if (UNEXPECTED(!pt_union_apply_criteria(criteria, &argv[0], out))) return;
	ZVAL_BOOL(return_value, out);
}

zv::Val pt_union_criteria_callback(const UnionCriteria &criteria)
{
	zv::Val state0 = criteria.lcname != NULL ? zv::Val::string(criteria.lcname, criteria.len) : zv::Val::null();
	return pt_type_native_callback(unionCriteriaCallbackBody, state0.raw(), NULL);
}

zval *pt_union_type_types(zend_object *object)
{
	return UnionType::typesOf(object);
}

zv::Val pt_union_type_get_types(zend_object *object)
{
	return UnionType::getTypesOf(object);
}

bool pt_union_type_construct(zend_object *self, zval *types, bool normalized)
{
	return UnionType(self).construct(types, normalized);
}

zv::Val pt_union_type_filter_types(zend_object *self, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
{
	return UnionType(self).filterTypes(fci, fcc);
}

zv::Val pt_union_type_try_remove(zend_object *self, zval *typeToRemove)
{
	return UnionType(self).tryRemove(typeToRemove);
}

zv::Val pt_union_type_describe(zend_object *self, zval *level)
{
	return UnionType(self).describe(level);
}

zv::Val pt_union_type_traverse_simultaneously(zend_object *self, zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
{
	return UnionType(self).traverseSimultaneously(right, fci, fcc);
}

bool pt_union_type_new(zval *out, zval *types, bool normalized)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
		zend_type_error("%s::__construct(): Argument #1 ($types) must be of type array, %s given", ZSTR_VAL(pt_ce_union_type->name), zend_zval_value_name(types));
		return false;
	}
	return pt_val_into(UnionType::create(zv::Val::copyOf(zv::Ref(types)), normalized), out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS UnionType(Z_OBJ_P(ZEND_THIS))

/* (): TrinaryLogic — the unionResults() family */
static void pt_ut_trinary0(INTERNAL_FUNCTION_PARAMETERS, zend_long (UnionType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)());
}

/* (string $name): TrinaryLogic */
static void pt_ut_trinary_string(INTERNAL_FUNCTION_PARAMETERS, zend_long (UnionType::*method)(zval *) const)
{
	zend_string *name;
	if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(&nameZv));
}

/* (Type $type, PhpVersion $phpVersion): TrinaryLogic */
static void pt_ut_trinary_type_version(INTERNAL_FUNCTION_PARAMETERS, zend_long (UnionType::*method)(zval *, zval *) const)
{
	zval *type, *phpVersion;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, type, phpVersion)) RETURN_THROWS();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(type, phpVersion));
}

/* (): Type / (): array — the unionTypes() and pickFromTypes() families */
static void pt_ut_value0(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (object $arg): Type */
static void pt_ut_value_object(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)(zval *) const)
{
	zval *arg;
	if (!zp::parse<zp::Obj>(execute_data, arg)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(arg));
}

/* (object $a, object $b): Type */
static void pt_ut_value_two_objects(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)(zval *, zval *) const)
{
	zval *a, *b;
	if (!zp::parse<zp::Obj, zp::Obj>(execute_data, a, b)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(a, b));
}

/* (object $a, object $b, object $c): Type */
static void pt_ut_value_three_objects(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)(zval *, zval *, zval *) const)
{
	zval *a, *b, *c;
	if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, a, b, c)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(a, b, c));
}

/* (callable $cb): Type — the callable handed on to the members as is */
static void pt_ut_value_callable(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)(zval *) const)
{
	zval *cb;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	cb = ZEND_CALL_ARG(execute_data, 1);
	PT_RETURN_VAL((PT_THIS.*method)(cb));
}

/* getProperty() & co.: (string $name, ClassMemberAccessAnswerer $scope) →
 * the transformed member of the prototype, through the object's class */
static void pt_ut_transformed_member(INTERNAL_FUNCTION_PARAMETERS, const char *prototypeLcname, size_t prototypeLen, bool isMethod)
{
	zval *name, *scope;
	if (!zp::parse<zp::Zval, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	PT_RETURN_VAL(pt_type_transformed_member(Z_OBJ_P(ZEND_THIS), prototypeLcname, prototypeLen, isMethod, name, scope));
}

/* getUnresolved*Prototype(): (string $name, ClassMemberAccessAnswerer $scope) */
static void pt_ut_unresolved_prototype(INTERNAL_FUNCTION_PARAMETERS, zv::Val (UnionType::*method)(zval *, zval *) const)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL((PT_THIS.*method)(&nameZv, scope));
}

static void ZEND_FASTCALL utGetTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getTypes());
}

static void ZEND_FASTCALL utGetFiniteTypeSet(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getFiniteTypeSet());
}

static void ZEND_FASTCALL utGetSortedTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(PT_THIS.getSortedTypes());
}

static void ZEND_FASTCALL utAccepts(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	bool strictTypes;
	if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.accepts(type, strictTypes));
}

static void ZEND_FASTCALL utDescribe(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *level;
	if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
	PT_RETURN_VAL(PT_THIS.describe(level));
}

static void ZEND_FASTCALL utIsEnum(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isEnum);
}

static void ZEND_FASTCALL utIsInteger(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isInteger);
}

static void ZEND_FASTCALL utGetEnumCases(INTERNAL_FUNCTION_PARAMETERS)
{
	pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getEnumCases);
}

/* the three protected methods called with a PHP callable (a subclass, or
 * a PHP caller): the callable wrapped as a Call op is not enough — the
 * callable is arbitrary — so the loops run over pt_call_fci here */
static void ZEND_FASTCALL utUnionResults(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	/* TrinaryLogic::lazyExtremeIdentity($this->types, $getResult) */
	zval *types = PT_THIS.types();
	if (UNEXPECTED(types == NULL)) RETURN_THROWS();
	if (UNEXPECTED(zend_hash_num_elements(Z_ARRVAL_P(types)) == 0)) {
		pt_throw_should_not_happen();
		RETURN_THROWS();
	}
	zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
	zend_long last = -2;
	for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
		zval arg;
		ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
		zval resultRaw;
		if (UNEXPECTED(!pt_call_fci(&fci, &fcc, 1, &arg, &resultRaw))) RETURN_THROWS();
		zv::Val result = zv::Val::adopt(resultRaw);
		zend_long value = pt_type_trinary_value(result.raw());
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		if (last == -2) {
			last = value;
			continue;
		}
		if (last == value) continue;
		RETURN_COPY(pt_trinary_singleton(PT_TRI_MAYBE));
	}
	RETURN_COPY(pt_trinary_singleton(last));
}

static void ZEND_FASTCALL utUnionTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	zval *types = PT_THIS.types();
	if (UNEXPECTED(types == NULL)) RETURN_THROWS();
	zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
	zv::Arr newTypes = zv::Arr::create(zv::ArrRef(typesCopy.raw()).size());
	bool changed = false;
	for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
		zval *type = entry.value().deref().raw();
		zval arg;
		ZVAL_COPY_VALUE(&arg, type);
		zval newType;
		if (UNEXPECTED(!pt_call_fci(&fci, &fcc, 1, &arg, &newType))) RETURN_THROWS();
		if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(type)) {
			changed = true;
		}
		newTypes.push(zv::Val::adopt(newType));
	}
	if (!changed) {
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	}
	PT_RETURN_VAL(phpstanturbo::combinatorUnion(newTypes.table()));
}

static void ZEND_FASTCALL utPickFromTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci, criteriaFci;
	zend_fcall_info_cache fcc, criteriaFcc;
	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_FUNC(fci, fcc)
		Z_PARAM_FUNC(criteriaFci, criteriaFcc)
	ZEND_PARSE_PARAMETERS_END();
	zval *types = PT_THIS.types();
	if (UNEXPECTED(types == NULL)) RETURN_THROWS();
	zv::Val typesCopy = zv::Val::copyOf(zv::Ref(types));
	zv::Arr values = zv::Arr::create(0);
	for (zv::ArrayEntry entry : zv::ArrRef(typesCopy.raw())) {
		zval arg;
		ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
		zval innerValuesRaw;
		if (UNEXPECTED(!pt_call_fci(&fci, &fcc, 1, &arg, &innerValuesRaw))) RETURN_THROWS();
		zv::Val innerValues = zv::Val::adopt(innerValuesRaw);
		if (UNEXPECTED(!zv::Ref(innerValues.raw()).isArray())) {
			zend_type_error("phpstan_turbo: the pickFromTypes() callback must return array");
			RETURN_THROWS();
		}
		if (zend_hash_num_elements(zv::ArrRef(innerValues.raw()).table()) == 0) {
			RETURN_EMPTY_ARRAY();
		}
		for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
			values.push(value.value());
		}
	}
	PT_RETURN_VAL(zv::Val(std::move(values)));
}

/* the persistent, immutable `public const EQUAL_UNION_CLASSES = [
 * DateTimeInterface::class => [DateTimeImmutable::class, DateTime::class],
 * Throwable::class => [Error::class, Exception::class]]`, built once — the
 * class constant declared at every activation references it */
static HashTable *pt_ut_persistent_list(const char *const *values, uint32_t count)
{
	HashTable *ht = (HashTable *) pemalloc(sizeof(HashTable), 1);
	zend_hash_init(ht, count, NULL, NULL, 1);
	for (uint32_t i = 0; i < count; i++) {
		zval value;
		ZVAL_INTERNED_STR(&value, zend_string_init_interned(values[i], strlen(values[i]), 1));
		zend_hash_next_index_insert_new(ht, &value);
	}
	GC_ADD_FLAGS(ht, IS_ARRAY_IMMUTABLE);
	GC_SET_REFCOUNT(ht, 2);
	return ht;
}

static void pt_ut_equal_union_classes(zval *out)
{
	static HashTable *table = nullptr;
	if (table == nullptr) {
		static const char *const dateTime[] = { "DateTimeImmutable", "DateTime" };
		static const char *const throwable[] = { "Error", "Exception" };
		table = (HashTable *) pemalloc(sizeof(HashTable), 1);
		zend_hash_init(table, 2, NULL, NULL, 1);
		zval value;
		ZVAL_ARR(&value, pt_ut_persistent_list(dateTime, 2));
		Z_TYPE_INFO(value) = IS_ARRAY;
		zend_hash_add_new(table, zend_string_init_interned(PT_LC("DateTimeInterface"), 1), &value);
		ZVAL_ARR(&value, pt_ut_persistent_list(throwable, 2));
		Z_TYPE_INFO(value) = IS_ARRAY;
		zend_hash_add_new(table, zend_string_init_interned(PT_LC("Throwable"), 1), &value);
		GC_ADD_FLAGS(table, IS_ARRAY_IMMUTABLE);
		GC_SET_REFCOUNT(table, 2);
	}
	ZVAL_ARR(out, table);
	Z_TYPE_INFO_P(out) = IS_ARRAY;
}

PT_MINIT_REGISTRATION(pt_register_union_type)
{
	reg::Class cls("PHPStan\\Type\\UnionType");
	ptdecl::UnionType::declareClass(cls);
	cls.classConstantValue("EQUAL_UNION_CLASSES", pt_ut_equal_union_classes);
	/* the slots PT_UT_PROP_*: the six memo properties first, the promoted
	 * $types and $normalized after them */
	cls.privateTypedPropertyDefaultNull("sortedTypesCache", MAY_BE_ARRAY);
	cls.privateTypedArrayPropertyDefaultEmpty("cachedDescriptions");
	cls.privateTypedClassPropertyDefaultNull("finiteTypeSet", "PHPStan\\Type\\FiniteTypeSet", MAY_BE_FALSE);
	cls.privateTypedPropertyDefaultNull("finiteTypes", MAY_BE_ARRAY);
	cls.privateTypedClassPropertyDefaultNull("isNull", ptcls::trinaryLogic);
	cls.privateTypedClassPropertyDefaultNull("isCallable", ptcls::trinaryLogic);
	cls.privateTypedProperty("types", MAY_BE_ARRAY);
	cls.privateTypedProperty("normalized", MAY_BE_BOOL);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		bool normalized = false;
		if (!zp::parse<zp::Arr, zp::Opt<zp::Bool>>(execute_data, types, normalized)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(types, normalized))) RETURN_THROWS();
	});

	cls.method(sigs::getTypes, utGetTypes);
	cls.op<PT_OP_GET_TYPES, &UnionType::getTypes>();

	cls.method(sigs::filterTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.filterTypes(&fci, &fcc));
	});

	cls.method<&UnionType::isNormalized>(sigs::isNormalized);

	cls.method(sigs::getFiniteTypeSet, utGetFiniteTypeSet);
	cls.method(sigs::getSortedTypes, utGetSortedTypes);

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getReferencedClasses);
	});
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &UnionType::getReferencedClasses>();
	cls.method(sigs::getObjectClassNames, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getObjectClassNames);
	});
	cls.op<PT_OP_GET_OBJECT_CLASS_NAMES, &UnionType::getObjectClassNames>();
	cls.method(sigs::getObjectClassReflections, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getObjectClassReflections);
	});
	cls.op<PT_OP_GET_OBJECT_CLASS_REFLECTIONS, &UnionType::getObjectClassReflections>();
	cls.method(sigs::getArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getArrays);
	});
	cls.method(sigs::getConstantArrays, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getConstantArrays);
	});
	cls.op<PT_OP_GET_CONSTANT_ARRAYS, &UnionType::getConstantArrays>();
	cls.method(sigs::getConstantStrings, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getConstantStrings);
	});

	cls.method(sigs::accepts, utAccepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return UnionType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &UnionType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &UnionType::isSubTypeOf>();

	cls.method<&UnionType::isAcceptedBy, zp::Obj, zp::Bool>(sigs::isAcceptedBy);

	cls.method<&UnionType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &UnionType::equals>();

	cls.method(sigs::describe, utDescribe);
	cls.op<PT_OP_DESCRIBE, &UnionType::describe>();

	cls.method(sigs::getTemplateType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *ancestorClassName, *templateTypeName;
		if (!zp::parse<zp::Str, zp::Str>(execute_data, ancestorClassName, templateTypeName)) RETURN_THROWS();
		zval a, b;
		ZVAL_STR(&a, ancestorClassName);
		ZVAL_STR(&b, templateTypeName);
		PT_RETURN_VAL(PT_THIS.getTemplateType(&a, &b));
	});

	cls.method(sigs::isObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isObject);
	});
	cls.method(sigs::getClassStringType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getClassStringType);
	});
	cls.method(sigs::isEnum, utIsEnum);
	cls.method(sigs::canAccessProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::canAccessProperties);
	});
	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::hasProperty);
	});
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getUnresolvedPropertyPrototype);
	});
	cls.method(sigs::hasInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::hasInstanceProperty);
	});
	cls.op<PT_OP_HAS_INSTANCE_PROPERTY, &UnionType::hasInstanceProperty>();
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedinstancepropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getUnresolvedInstancePropertyPrototype);
	});
	cls.op<PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE, &UnionType::getUnresolvedInstancePropertyPrototype>();
	cls.method(sigs::hasStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::hasStaticProperty);
	});
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedstaticpropertyprototype"), false);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getUnresolvedStaticPropertyPrototype);
	});
	cls.method(sigs::canCallMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::canCallMethods);
	});
	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::hasMethod);
	});
	cls.op<PT_OP_HAS_METHOD, &UnionType::hasMethod>();
	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_transformed_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_LC("getunresolvedmethodprototype"), true);
	});
	cls.method(sigs::getUnresolvedMethodPrototype, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_unresolved_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getUnresolvedMethodPrototype);
	});
	cls.op<PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE, &UnionType::getUnresolvedMethodPrototype>();
	cls.method(sigs::canAccessConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::canAccessConstants);
	});
	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_string(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::hasConstant);
	});
	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(PT_THIS.getConstant(&nameZv));
	});

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isIterable);
	});
	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isIterableAtLeastOnce);
	});
	cls.op<PT_OP_IS_ITERABLE_AT_LEAST_ONCE, &UnionType::isIterableAtLeastOnce>();
	cls.method(sigs::getArraySize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getArraySize);
	});
	cls.method(sigs::getIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableKeyType);
	});
	cls.op<PT_OP_GET_ITERABLE_KEY_TYPE, &UnionType::getIterableKeyType>();
	cls.method(sigs::getFirstIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableKeyType);
	});
	cls.method(sigs::getLastIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableKeyType);
	});
	cls.method(sigs::getIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableValueType);
	});
	cls.op<PT_OP_GET_ITERABLE_VALUE_TYPE, &UnionType::getIterableValueType>();
	cls.method(sigs::getFirstIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableValueType);
	});
	cls.method(sigs::getLastIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getIterableValueType);
	});

	cls.method(sigs::isArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isArray);
	});
	cls.op<PT_OP_IS_ARRAY, &UnionType::isArray>();
	cls.method(sigs::isConstantArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isConstantArray);
	});
	cls.op<PT_OP_IS_CONSTANT_ARRAY, &UnionType::isConstantArray>();
	cls.method(sigs::isOversizedArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isOversizedArray);
	});
	cls.method(sigs::isList, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isList);
	});
	cls.op<PT_OP_IS_LIST, &UnionType::isList>();
	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isString);
	});
	cls.op<PT_OP_IS_STRING, &UnionType::isString>();
	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isNumericString);
	});
	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isDecimalIntegerString);
	});
	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isNonEmptyString);
	});
	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isNonFalsyString);
	});
	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isLiteralString);
	});
	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isLowercaseString);
	});
	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isUppercaseString);
	});
	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isClassString);
	});
	cls.method(sigs::getClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getClassStringObjectType);
	});
	cls.method(sigs::getObjectTypeOrClassStringObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getObjectTypeOrClassStringObjectType);
	});
	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isVoid);
	});
	cls.op<PT_OP_IS_VOID, &UnionType::isVoid>();
	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isScalar);
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::looseCompare);
	});

	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isOffsetAccessible);
	});
	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isOffsetAccessLegal);
	});
	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});
	cls.op<PT_OP_HAS_OFFSET_VALUE_TYPE, &UnionType::hasOffsetValueType>();
	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getOffsetValueType);
	});
	cls.op<PT_OP_GET_OFFSET_VALUE_TYPE, &UnionType::getOffsetValueType>();
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType, unionValues));
	});
	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::setExistingOffsetValueType);
	});
	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::unsetOffset);
	});
	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getKeysArrayFiltered);
	});
	cls.method(sigs::getKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getKeysArray);
	});
	cls.method(sigs::getValuesArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getValuesArray);
	});
	cls.method(sigs::chunkArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_two_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::chunkArray);
	});
	cls.method(sigs::fillKeysArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::fillKeysArray);
	});
	cls.method(sigs::flipArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::flipArray);
	});
	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::intersectKeyArray);
	});
	cls.method(sigs::popArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::popArray);
	});
	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::reverseArray);
	});
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *needleType, *strict = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, needleType, strict)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.searchArray(needleType, strict));
	});
	cls.method(sigs::shiftArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::shiftArray);
	});
	cls.method(sigs::shuffleArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::shuffleArray);
	});
	cls.method(sigs::sliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_three_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::sliceArray);
	});
	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_three_objects(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::spliceArray);
	});
	cls.method(sigs::truncateListToSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::truncateListToSize);
	});
	cls.method(sigs::makeListMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::makeListMaybe);
	});
	cls.method(sigs::mapValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::mapValueType);
	});
	cls.method(sigs::mapKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_callable(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::mapKeyType);
	});
	cls.method(sigs::makeAllArrayKeysOptional, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::makeAllArrayKeysOptional);
	});
	cls.method(sigs::changeKeyCaseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long caseArg = 0;
		bool caseIsNull = true;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(caseArg, caseIsNull)
		ZEND_PARSE_PARAMETERS_END();
		zval caseZv;
		if (caseIsNull) {
			ZVAL_NULL(&caseZv);
		} else {
			ZVAL_LONG(&caseZv, caseArg);
		}
		PT_RETURN_VAL(PT_THIS.changeKeyCaseArray(&caseZv));
	});
	cls.method(sigs::filterArrayRemovingFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::filterArrayRemovingFalsey);
	});

	cls.method(sigs::getEnumCases, utGetEnumCases);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getEnumCaseObject);
	});
	cls.op<PT_OP_GET_ENUM_CASE_OBJECT, &UnionType::getEnumCaseObject>();

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isCallable);
	});
	cls.op<PT_OP_IS_CALLABLE, &UnionType::isCallable>();
	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getCallableParametersAcceptors);
	});
	cls.method(sigs::isCloneable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isCloneable);
	});

	cls.method(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isSmallerThan);
	});
	cls.method(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isSmallerThanOrEqual);
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isNull);
	});
	cls.op<PT_OP_IS_NULL, &UnionType::isNull>();
	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isConstantValue);
	});
	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isConstantScalarValue);
	});
	cls.op<PT_OP_IS_CONSTANT_SCALAR_VALUE, &UnionType::isConstantScalarValue>();
	cls.method(sigs::getConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getConstantScalarTypes);
	});
	cls.method(sigs::getConstantScalarValues, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getConstantScalarValues);
	});
	cls.op<PT_OP_GET_CONSTANT_SCALAR_VALUES, &UnionType::getConstantScalarValues>();
	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isTrue);
	});
	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isFalse);
	});
	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isBoolean);
	});
	cls.op<PT_OP_IS_BOOLEAN, &UnionType::isBoolean>();
	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isFloat);
	});
	cls.op<PT_OP_IS_FLOAT, &UnionType::isFloat>();
	cls.method(sigs::isInteger, utIsInteger);
	cls.op<PT_OP_IS_INTEGER, &UnionType::isInteger>();

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getSmallerType);
	});
	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getSmallerOrEqualType);
	});
	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getGreaterType);
	});
	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getGreaterOrEqualType);
	});
	cls.method(sigs::isGreaterThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isGreaterThan);
	});
	cls.method(sigs::isGreaterThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_trinary_type_version(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::isGreaterThanOrEqual);
	});

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toBoolean);
	});
	cls.method(sigs::toNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toNumber);
	});
	cls.method(sigs::toBitwiseNotType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toBitwiseNotType);
	});
	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toGetClassResultType);
	});
	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toClassConstantType);
	});
	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toObjectTypeForInstanceofCheck);
	});
	cls.method<&UnionType::toObjectTypeForIsACheck, zp::Obj, zp::Bool, zp::Bool>(sigs::toObjectTypeForIsACheck);
	cls.method(sigs::toAbsoluteNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toAbsoluteNumber);
	});
	cls.method(sigs::toString, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toString);
	});
	cls.method(sigs::toInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toInteger);
	});
	cls.method(sigs::toFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toFloat);
	});
	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toArray);
	});
	cls.method(sigs::toArrayKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toArrayKey);
	});
	cls.op<PT_OP_TO_ARRAY_KEY, &UnionType::toArrayKey>();
	cls.method<&UnionType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::inferTemplateTypes);
	});
	cls.method(sigs::inferTemplateTypesOn, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::inferTemplateTypesOn);
	});
	cls.method(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getReferencedTemplateTypes);
	});
	cls.op<PT_OP_GET_REFERENCED_TEMPLATE_TYPES, &UnionType::getReferencedTemplateTypes>();

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<UnionType>(self, argv); });
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::tryRemove);
	});
	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value_object(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::exponentiate);
	});
	cls.method(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::getFiniteTypes);
	});

	cls.method(sigs::unionResults, utUnionResults);
	cls.method(sigs::unionTypes, utUnionTypes);
	cls.method(sigs::pickFromTypes, utPickFromTypes);

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ut_value0(INTERNAL_FUNCTION_PARAM_PASSTHRU, &UnionType::toPhpDocNode);
	});
	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		int has = PT_THIS.hasTemplateOrLateResolvableType();
		if (UNEXPECTED(has < 0)) RETURN_THROWS();
		RETURN_BOOL(has == 1);
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { int has = UnionType(self).hasTemplateOrLateResolvableType(); return has < 0 ? zv::Val() : zv::Val::boolean(has == 1); });

	/* the trait: the class body above wins over every name it declares */
	ptdecl::UnionType::registerTraits(cls);

	cls.shadow(&pt_ce_union_type);
}

/* }}} */
