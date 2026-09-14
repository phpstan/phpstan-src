/*
 * PHPStanTurbo\BenevolentUnionType — native implementation of
 * PHPStan\Type\BenevolentUnionType.
 *
 * Declared as PHPStan\Type\BenevolentUnionType itself at activation: a
 * child of the native UnionType (the PHP TemplateBenevolentUnionType
 * extends it in turn), not final, declaring only what the twin declares —
 * the constructor forwarding to the parent's, and the overrides that make
 * the union benevolent: unionResults() answering yes as soon as one member
 * does, unionTypes()/getOffsetValueType()/traverse()/traverseSimultaneously()/
 * tryRemove()/filterTypes() keeping the result benevolent, describe()
 * parenthesizing the parent's, pickFromTypes() consulting the criteria,
 * isAcceptedBy() or'ing the members, the template maps combined with
 * benevolentUnion(). Everything else is inherited from the parent's
 * class entry, as the PHP class inherits it.
 *
 * The logic lives in the BenevolentUnionType handle class below, mirroring
 * src/Type/BenevolentUnionType.php method for method; the parent:: calls go
 * to the pt_union_type_* bodies (UnionType.cpp), and the parent's own
 * $this-calls to unionResults()/unionTypes()/pickFromTypes() land here
 * through the handler identities this file exports (TypeTraits.h).
 * TypeUtils::toBenevolentUnion() is inlined natively
 * (pt_union_to_benevolent()) — three lines the twin calls on every result.
 */

#include "TypeTraits.h"
#include "generated/BenevolentUnionType.h"

namespace sigs = ptdecl::BenevolentUnionType::sig;

zend_class_entry *pt_ce_benevolent_union_type = nullptr;

static void ZEND_FASTCALL buUnionResults(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL buUnionTypes(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL buPickFromTypes(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* $object->method(...) that returns a Type: the result checked to be an
 * object (the engine's return check of the PHP twin); UNDEF = pending
 * exception */
static zv::Val callType(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	return pt_type_call_type(object, lcname, len, argc, argv);
}

/* TypeCombinator::union(...$types) over a PHP array of types */
static zv::Val combinatorUnion(HashTable *types)
{
	return pt_type_combinator_call_spread(PT_LC("union"), types);
}

/* Mirrors PHPStan\Type\BenevolentUnionType. State is the parent's. */
class BenevolentUnionType
{
public:
	explicit BenevolentUnionType(zend_object *self) : self(self) {}

	/* parent::__construct($types, $normalized) */
	bool construct(zval *types, bool normalized) { return pt_union_type_construct(self, types, normalized); }

	/* new BenevolentUnionType($types) ($types consumed); UNDEF = pending
	 * exception */
	static zv::Val create(zv::Val types, bool normalized = false)
	{
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_benevolent_union_type) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!BenevolentUnionType(Z_OBJ(object)).construct(types.raw(), normalized))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* TypeUtils::toBenevolentUnion($type): the type itself for a
	 * BenevolentUnionType, new BenevolentUnionType($type->getTypes()) for any
	 * other UnionType, the type otherwise; UNDEF = pending exception */
	static zv::Val toBenevolent(zval *type)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: TypeUtils::toBenevolentUnion(): Argument #1 ($type) must be of type %s, %s given", ptcls::type, zend_zval_value_name(type));
			return zv::Val();
		}
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_benevolent_union_type)) return zv::Val::copyOf(zv::Ref(type));
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_union_type)) return create(pt_union_type_get_types(Z_OBJ_P(type)));
		return zv::Val::copyOf(zv::Ref(type));
	}

	/* parent::filterTypes($filterCb) made benevolent when it came out as a
	 * plain union; UNDEF = pending exception */
	zv::Val filterTypes(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val result = pt_union_type_filter_types(self, fci, fcc);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (!zv::Ref(result.raw()).instanceOf(pt_ce_benevolent_union_type) && zv::Ref(result.raw()).instanceOf(pt_ce_union_type)) {
			return toBenevolent(result.raw());
		}
		return result;
	}

	/* parent::tryRemove($typeToRemove) made benevolent, null staying null */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zv::Val result = pt_union_type_try_remove(self, typeToRemove);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (zv::Ref(result.raw()).isNull()) return result;
		return toBenevolent(result.raw());
	}

	/* '(' . parent::describe($level) . ')' */
	zv::Val describe(zval *level) const
	{
		zv::Val inner = pt_union_type_describe(self, level);
		if (UNEXPECTED(inner.isUndef())) return zv::Val();
		smart_str description = {NULL, 0};
		smart_str_appendc(&description, '(');
		smart_str_append(&description, zv::Ref(inner.raw()).asString());
		smart_str_appendc(&description, ')');
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* the members' results with the ErrorTypes dropped: $this when nothing
	 * changed, an ErrorType when nothing is left, the benevolent union
	 * otherwise; UNDEF = pending exception */
	zv::Val unionTypes(const UnionMemberOp &op) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr resultTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val result = pt_union_apply_op(op, type);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
				zend_type_error("phpstan_turbo: %s::%s() must return %s, %s returned", ZSTR_VAL(Z_OBJCE_P(type)->name), op.lcname, ptcls::type, zend_zval_value_name(result.raw()));
				return zv::Val();
			}
			bool isError;
			if (UNEXPECTED(!pt_type_instanceof_ce(result.raw(), pt_ce_error_type, isError))) return zv::Val();
			if (isError) {
				changed = true;
				continue;
			}
			if (Z_OBJ_P(result.raw()) != Z_OBJ_P(type)) {
				changed = true;
			}
			resultTypes.push(std::move(result));
		}
		if (!changed) return thisValue();
		if (zend_hash_num_elements(resultTypes.table()) == 0) return pt_type_new_error_type();
		zv::Val unionType = combinatorUnion(resultTypes.table());
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return toBenevolent(unionType.raw());
	}

	/* the members' values concatenated, [] as soon as a member matching
	 * the criteria has none; UNDEF = pending exception */
	zv::Val pickFromTypes(const UnionMemberOp &op, const UnionCriteria &criteria) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr values = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val innerValues = pt_union_apply_op(op, type);
			if (UNEXPECTED(innerValues.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(innerValues.raw()).isArray())) {
				zend_type_error("phpstan_turbo: %s::%s() must return array, %s returned", ZSTR_VAL(Z_OBJCE_P(type)->name), op.lcname, zend_zval_value_name(innerValues.raw()));
				return zv::Val();
			}
			if (zend_hash_num_elements(zv::ArrRef(innerValues.raw()).table()) == 0) {
				bool matches;
				if (UNEXPECTED(!pt_union_apply_criteria(criteria, type, matches))) return zv::Val();
				if (matches) return zv::Val(zv::Arr::empty());
			}
			for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
				values.push(value.value());
			}
		}
		return zv::Val(std::move(values));
	}

	/* the benevolent union of the members' offset value types that are no
	 * ErrorType, an ErrorType when none is; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr valueTypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val valueType = callType(entry.value().deref().asObject(), PT_LC("getoffsetvaluetype"), 1, offsetType);
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			bool isError;
			if (UNEXPECTED(!pt_type_instanceof_ce(valueType.raw(), pt_ce_error_type, isError))) return zv::Val();
			if (isError) continue;
			valueTypes.push(std::move(valueType));
		}
		if (zend_hash_num_elements(valueTypes.table()) == 0) return pt_type_new_error_type();
		zv::Val unionType = combinatorUnion(valueTypes.table());
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return toBenevolent(unionType.raw());
	}

	/* TrinaryLogic::createNo()->lazyOr($this->getTypes(), $getResult): the
	 * first yes, else the or-fold; UNDEF = pending exception */
	zv::Val unionResults(const UnionMemberOp &op) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zend_long acc = PT_TRI_NO;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Val result = pt_union_apply_op(op, entry.value().deref().raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_long value = pt_type_trinary_value(result.raw());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return result;
			acc |= value;
		}
		return pt_type_trinary(acc);
	}

	/* no or'ed with $acceptingType->accepts() of every member */
	zv::Val isAcceptedBy(zval *acceptingType, bool strictTypes) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val result = pt_type_accepts_result(PT_TRI_NO);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zval strictZv = {};
		ZVAL_BOOL(&strictZv, strictTypes);
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zv::Args args{entry.value().deref().raw(), &strictZv};
			zv::Val accepts = pt_type_call(Z_OBJ_P(acceptingType), PT_LC("accepts"), 2, args);
			if (UNEXPECTED(accepts.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
				zend_type_error("phpstan_turbo: expected %s", ZSTR_VAL(pt_ce_accepts_result->name));
				return zv::Val();
			}
			result = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("or"), 1, accepts.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}
		return result;
	}

	/* the benevolent union of every member's inferTemplateTypes($receivedType) */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		return benevolentMapUnion(PT_LC("infertemplatetypes"), receivedType, false);
	}

	/* the benevolent union of $templateType->inferTemplateTypes() over every member */
	zv::Val inferTemplateTypesOn(zval *templateType) const
	{
		return benevolentMapUnion(PT_LC("infertemplatetypes"), templateType, true);
	}

	/* the benevolent union of $cb over every member when any changed, $this
	 * otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val types = thisGetTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr newTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
		bool changed = false;
		for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
			zval *type = entry.value().deref().raw();
			zval arg;
			ZVAL_COPY_VALUE(&arg, type);
			zval newType;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &newType))) return zv::Val();
			if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(type)) {
				changed = true;
			}
			newTypes.push(zv::Val::adopt(newType));
		}
		if (changed) {
			zv::Val unionType = combinatorUnion(newTypes.table());
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			return toBenevolent(unionType.raw());
		}
		return thisValue();
	}

	/* parent::traverseSimultaneously() made benevolent unless it is $this */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val newType = pt_union_type_traverse_simultaneously(self, right, fci, fcc);
		if (UNEXPECTED(newType.isUndef())) return zv::Val();
		if (Z_TYPE_P(newType.raw()) == IS_OBJECT && Z_OBJ_P(newType.raw()) == self) return newType;
		return toBenevolent(newType.raw());
	}

private:
	zend_object *self;

	/* $this->getTypes() through the object's class entry */
	zv::Val thisGetTypes() const { return pt_union_type_get_types(self); }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* TemplateTypeMap::createEmpty()->benevolentUnion(...) over the members:
	 * $type->method($argument), or $argument->method($type) when reversed */
	zv::Val benevolentMapUnion(const char *lcname, size_t len, zval *argument, bool reversed) const
	{
		zv::Val types = pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val members = thisGetTypes();
		if (UNEXPECTED(members.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(members.raw())) {
			zval *type = entry.value().deref().raw();
			zv::Val inferred = reversed ? pt_type_call(Z_OBJ_P(argument), lcname, len, 1, type) : pt_type_call(Z_OBJ_P(type), lcname, len, 1, argument);
			if (UNEXPECTED(inferred.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(types.raw()).isObject())) {
				zend_type_error("phpstan_turbo: inferTemplateTypes() must return TemplateTypeMap");
				return zv::Val();
			}
			types = pt_type_call(Z_OBJ_P(types.raw()), PT_LC("benevolentunion"), 1, inferred.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}
		return types;
	}
};

} // namespace phpstanturbo

using phpstanturbo::BenevolentUnionType;
using phpstanturbo::UnionCriteria;
using phpstanturbo::UnionMemberOp;

/* {{{ shared with UnionType.cpp and the other ports (TypeTraits.h) */

zv::Val pt_union_benevolent_union_results(zend_object *self, const UnionMemberOp &op)
{
	return BenevolentUnionType(self).unionResults(op);
}

zv::Val pt_union_benevolent_union_types(zend_object *self, const UnionMemberOp &op)
{
	return BenevolentUnionType(self).unionTypes(op);
}

zv::Val pt_union_benevolent_pick_from_types(zend_object *self, const UnionMemberOp &op, const UnionCriteria &criteria)
{
	return BenevolentUnionType(self).pickFromTypes(op, criteria);
}

zif_handler pt_union_benevolent_union_results_handler() { return buUnionResults; }
zif_handler pt_union_benevolent_union_types_handler() { return buUnionTypes; }
zif_handler pt_union_benevolent_pick_from_types_handler() { return buPickFromTypes; }

zv::Val pt_union_to_benevolent(zval *type)
{
	return BenevolentUnionType::toBenevolent(type);
}

zv::Val pt_union_benevolent_of(zv::Arr types)
{
	return BenevolentUnionType::create(zv::Val(std::move(types)));
}

bool pt_benevolent_union_type_new(zval *out, zval *types, bool normalized)
{
	if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
		zend_type_error("%s::__construct(): Argument #1 ($types) must be of type array, %s given", ZSTR_VAL(pt_ce_benevolent_union_type->name), zend_zval_value_name(types));
		return false;
	}
	return pt_val_into(BenevolentUnionType::create(zv::Val::copyOf(zv::Ref(types)), normalized), out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS BenevolentUnionType(Z_OBJ_P(ZEND_THIS))

/* the three protected methods called with a PHP callable (a subclass, or
 * a PHP caller): the loops run over pt_call_fci */
static void ZEND_FASTCALL buUnionResults(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	/* TrinaryLogic::createNo()->lazyOr($this->getTypes(), $getResult) */
	zv::Val types = pt_union_type_get_types(Z_OBJ_P(ZEND_THIS));
	if (UNEXPECTED(types.isUndef())) RETURN_THROWS();
	zend_long acc = PT_TRI_NO;
	for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
		zval arg;
		ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
		zval resultRaw;
		if (UNEXPECTED(!pt_call_fci(&fci, &fcc, 1, &arg, &resultRaw))) RETURN_THROWS();
		zv::Val result = zv::Val::adopt(resultRaw);
		zend_long value = pt_type_trinary_value(result.raw());
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		if (value == PT_TRI_YES) {
			result.intoReturnValue(return_value);
			return;
		}
		acc |= value;
	}
	RETURN_COPY(pt_trinary_singleton(acc));
}

static void ZEND_FASTCALL buUnionTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_FUNC(fci, fcc)
	ZEND_PARSE_PARAMETERS_END();
	zv::Val types = pt_union_type_get_types(Z_OBJ_P(ZEND_THIS));
	if (UNEXPECTED(types.isUndef())) RETURN_THROWS();
	zv::Arr resultTypes = zv::Arr::create(zv::ArrRef(types.raw()).size());
	bool changed = false;
	for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
		zval *type = entry.value().deref().raw();
		zval arg;
		ZVAL_COPY_VALUE(&arg, type);
		zval resultRaw;
		if (UNEXPECTED(!pt_call_fci(&fci, &fcc, 1, &arg, &resultRaw))) RETURN_THROWS();
		zv::Val result = zv::Val::adopt(resultRaw);
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof_ce(result.raw(), pt_ce_error_type, isError))) RETURN_THROWS();
		if (isError) {
			changed = true;
			continue;
		}
		if (Z_TYPE_P(result.raw()) != IS_OBJECT || Z_OBJ_P(result.raw()) != Z_OBJ_P(type)) {
			changed = true;
		}
		resultTypes.push(std::move(result));
	}
	if (!changed) {
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	}
	if (zend_hash_num_elements(resultTypes.table()) == 0) {
		PT_RETURN_VAL(pt_type_new_error_type());
	}
	zv::Val unionType = phpstanturbo::combinatorUnion(resultTypes.table());
	if (UNEXPECTED(unionType.isUndef())) RETURN_THROWS();
	PT_RETURN_VAL(BenevolentUnionType::toBenevolent(unionType.raw()));
}

static void ZEND_FASTCALL buPickFromTypes(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_fcall_info fci, criteriaFci;
	zend_fcall_info_cache fcc, criteriaFcc;
	ZEND_PARSE_PARAMETERS_START(2, 2)
		Z_PARAM_FUNC(fci, fcc)
		Z_PARAM_FUNC(criteriaFci, criteriaFcc)
	ZEND_PARSE_PARAMETERS_END();
	zv::Val types = pt_union_type_get_types(Z_OBJ_P(ZEND_THIS));
	if (UNEXPECTED(types.isUndef())) RETURN_THROWS();
	zv::Arr values = zv::Arr::create(0);
	for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
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
			zval matchesRaw;
			if (UNEXPECTED(!pt_call_fci(&criteriaFci, &criteriaFcc, 1, &arg, &matchesRaw))) RETURN_THROWS();
			bool matches = zend_is_true(&matchesRaw);
			zval_ptr_dtor(&matchesRaw);
			if (matches) {
				RETURN_EMPTY_ARRAY();
			}
		}
		for (zv::ArrayEntry value : zv::ArrRef(innerValues.raw())) {
			values.push(value.value());
		}
	}
	PT_RETURN_VAL(zv::Val(std::move(values)));
}

void pt_register_benevolent_union_type()
{
	reg::Class cls("PHPStan\\Type\\BenevolentUnionType");
	ptdecl::BenevolentUnionType::declareClass(cls);
	ptdecl::BenevolentUnionType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		bool normalized = false;
		if (!zp::parse<zp::Arr, zp::Opt<zp::Bool>>(execute_data, types, normalized)) RETURN_THROWS();
		if (UNEXPECTED(!PT_THIS.construct(types, normalized))) RETURN_THROWS();
	});

	cls.method(sigs::filterTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.filterTypes(&fci, &fcc));
	});

	cls.method<&BenevolentUnionType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&BenevolentUnionType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::unionTypes, buUnionTypes);
	cls.method(sigs::pickFromTypes, buPickFromTypes);

	cls.method<&BenevolentUnionType::getOffsetValueType, zp::Obj>(sigs::getOffsetValueType);

	cls.method(sigs::unionResults, buUnionResults);

	cls.method<&BenevolentUnionType::isAcceptedBy, zp::Obj, zp::Bool>(sigs::isAcceptedBy);

	cls.method<&BenevolentUnionType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&BenevolentUnionType::inferTemplateTypesOn, zp::Obj>(sigs::inferTemplateTypesOn);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

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

	cls.shadow(&pt_ce_benevolent_union_type);
}

/* }}} */
