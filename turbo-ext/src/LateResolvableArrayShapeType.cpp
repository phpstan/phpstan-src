/*
 * PHPStanTurbo\LateResolvableArrayShapeType — native implementation of
 * PHPStan\Type\LateResolvableArrayShapeType.
 *
 * State is the twin's three promoted constructor properties — `private
 * array $items` (a list of [keyType|null, valueType, optional] triples),
 * `private ?array $unsealed` (a [keyType|null, valueType] pair) and
 * `private string $kind` (an ArrayShapeNode::KIND_* value) — in slots 0-2;
 * LateResolvableTypeTrait's `private ?Type $result` follows them, declared
 * by the shared registrar in TypeTraits.cpp that also supplies the trait's
 * forwards (the class body's own
 * getObjectClassNames()/getObjectClassReflections() win over the trait's,
 * as in PHP); NonGeneralizableTypeTrait's generalize() comes from its
 * registrar.
 *
 * The constructor is private as the twin's is — create() is the factory,
 * and the native code instantiates directly. The class is final, so the
 * private helpers and the trait's resolve() are direct C++ calls; the one
 * $this-call the twin makes (describe() printing $this->toPhpDocNode())
 * goes through the object's class entry. Another instance's private slots
 * are read directly, as the twin does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/LateResolvableArrayShapeType.h"

namespace slots = ptdecl::LateResolvableArrayShapeType::slot;
namespace sigs = ptdecl::LateResolvableArrayShapeType::sig;

zend_class_entry *pt_ce_late_resolvable_array_shape_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\LateResolvableArrayShapeType. State lives in the
 * PHP object's slots. */
class LateResolvableArrayShapeType
{
public:
	explicit LateResolvableArrayShapeType(zend_object *self) : self(self) {}

	/* __construct(private array $items, private ?array $unsealed, private
	 * string $kind); every argument borrowed, $unsealed NULL for null */
	void construct(zval *items, zval *unsealed, zend_string *kind)
	{
		writeSlot(slots::items, items);
		zval null;
		ZVAL_NULL(&null);
		writeSlot(slots::unsealed, unsealed != NULL ? unsealed : &null);
		zval kindZv;
		ZVAL_STR(&kindZv, kind);
		writeSlot(slots::kind, &kindZv);
	}

	/* new self($items, $unsealed, $kind) — the private constructor, run
	 * directly; UNDEF = pending exception */
	static zv::Val createRaw(zval *items, zval *unsealed, zend_string *kind)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_late_resolvable_array_shape_type) != SUCCESS)) return zv::Val();
		LateResolvableArrayShapeType(Z_OBJ(object)).construct(items, unsealed, kind);
		return zv::Val::adopt(object);
	}

	/* create(): the shape resolved right away when it can be, the
	 * late-resolvable shape otherwise; UNDEF = pending exception */
	static zv::Val create(zval *items, zval *unsealed, zend_string *kind)
	{
		zv::Val created = createRaw(items, unsealed, kind);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		LateResolvableArrayShapeType shape(Z_OBJ_P(created.raw()));
		bool resolvable;
		if (UNEXPECTED(!shape.isResolvable(resolvable))) return zv::Val();
		if (resolvable) return pt_type_late_resolvable_resolve(Z_OBJ_P(created.raw()), pt_ce_late_resolvable_array_shape_type);
		return created;
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *items() const { return slot(self, slots::items, "items"); }
	zval *unsealed() const { return slot(self, slots::unsealed, "unsealed"); }
	zval *kind() const { return slot(self, slots::kind, "kind"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_late_resolvable_array_shape_type, name); }

	/* array_merge() of every key's, value's and the unsealed pair's
	 * getReferencedClasses() */
	zv::Val getReferencedClasses() const { return mergedOfParts(PT_LC("getreferencedclasses"), 0, NULL); }

	/* the same for getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return mergedOfParts(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self of the same kind, with pairwise equal items
	 * (optionality, key presence, key and value) and equal unsealed
	 * pairs; false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_late_resolvable_array_shape_type)) {
			out = false;
			return true;
		}
		zval *k = kind();
		zval *theirKind = k != NULL ? slot(Z_OBJ_P(type), slots::kind, "kind") : NULL;
		if (UNEXPECTED(theirKind == NULL)) return false;
		zval *its = items();
		zval *theirItems = its != NULL ? slot(Z_OBJ_P(type), slots::items, "items") : NULL;
		if (UNEXPECTED(theirItems == NULL)) return false;
		if (!zend_string_equals(Z_STR_P(k), Z_STR_P(theirKind)) || zend_hash_num_elements(Z_ARRVAL_P(its)) != zend_hash_num_elements(Z_ARRVAL_P(theirItems))) {
			out = false;
			return true;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return false;
			zval *theirItem = zend_hash_index_find(Z_ARRVAL_P(theirItems), entry.indexKey());
			if (UNEXPECTED(theirItem == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: the items of %s are not a list", ZSTR_VAL(pt_ce_late_resolvable_array_shape_type->name));
				return false;
			}
			zval *otherKeyType, *otherValueType;
			bool otherOptional;
			if (UNEXPECTED(!itemParts(theirItem, otherKeyType, otherValueType, otherOptional))) return false;
			if (optional != otherOptional || (keyType == NULL) != (otherKeyType == NULL)) {
				out = false;
				return true;
			}
			if (keyType != NULL && otherKeyType != NULL) {
				bool keysEqual;
				if (UNEXPECTED(!typesEqual(keyType, otherKeyType, keysEqual))) return false;
				if (!keysEqual) {
					out = false;
					return true;
				}
			}
			bool valuesEqual;
			if (UNEXPECTED(!typesEqual(valueType, otherValueType, valuesEqual))) return false;
			if (!valuesEqual) {
				out = false;
				return true;
			}
		}
		zval *u = unsealed();
		zval *theirUnsealed = u != NULL ? slot(Z_OBJ_P(type), slots::unsealed, "unsealed") : NULL;
		if (UNEXPECTED(theirUnsealed == NULL)) return false;
		bool mineNull = Z_TYPE_P(u) == IS_NULL;
		bool theirsNull = Z_TYPE_P(theirUnsealed) == IS_NULL;
		if (mineNull != theirsNull) {
			out = false;
			return true;
		}
		if (!mineNull) {
			zval *unsealedKeyType, *unsealedValueType, *otherUnsealedKeyType, *otherUnsealedValueType;
			if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType) || !unsealedParts(theirUnsealed, otherUnsealedKeyType, otherUnsealedValueType))) {
				return false;
			}
			if ((unsealedKeyType == NULL) != (otherUnsealedKeyType == NULL)) {
				out = false;
				return true;
			}
			if (unsealedKeyType != NULL && otherUnsealedKeyType != NULL) {
				bool keysEqual;
				if (UNEXPECTED(!typesEqual(unsealedKeyType, otherUnsealedKeyType, keysEqual))) return false;
				if (!keysEqual) {
					out = false;
					return true;
				}
			}
			return typesEqual(unsealedValueType, otherUnsealedValueType, out);
		}
		out = true;
		return true;
	}

	/* (new Printer())->print($this->toPhpDocNode()) — a shape that can be
	 * resolved never stays late-resolvable, see create() */
	zv::Val describe() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_callable_print_php_doc_node(&selfZv);
	}

	/* false as soon as an explicit key or the unsealed key contains a
	 * template type, true otherwise; false = pending exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *its = items();
		if (UNEXPECTED(its == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return false;
			if (keyType == NULL) continue;
			bool contains;
			if (UNEXPECTED(!pt_type_utils_contains_template_type(keyType, contains))) return false;
			if (contains) {
				out = false;
				return true;
			}
		}
		zval *u = unsealed();
		if (UNEXPECTED(u == NULL)) return false;
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType))) return false;
			if (unsealedKeyType != NULL) {
				bool contains;
				if (UNEXPECTED(!pt_type_utils_contains_template_type(unsealedKeyType, contains))) return false;
				if (contains) {
					out = false;
					return true;
				}
			}
		}
		out = true;
		return true;
	}

	/* the shape built over a ConstantArrayTypeBuilder (without array
	 * degradation): every item at its array-key type (an ErrorType key
	 * returned as is), the unsealed extras at the finite keys not already
	 * explicit or merged as a general unsealed part, then the list and
	 * non-empty accessories of the kind intersected in; UNDEF = pending
	 * exception */
	zv::Val getResult() const
	{
		zval *its = items();
		if (UNEXPECTED(its == NULL)) return zv::Val();
		zv::Val builder = pt_constant_array_type_builder_create_empty();
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(builder.raw()).isObject())) {
			zend_type_error("phpstan_turbo: ConstantArrayTypeBuilder::createEmpty() must return an object");
			return zv::Val();
		}
		zend_object *builderObject = Z_OBJ_P(builder.raw());
		zv::Val disabled = pt_type_call(builderObject, PT_LC("disablearraydegradation"), 0, NULL);
		if (UNEXPECTED(disabled.isUndef())) return zv::Val();

		zv::Arr explicitKeyValues = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return zv::Val();
			zv::Val arrayKeyType;
			zval nullKey;
			ZVAL_NULL(&nullKey);
			zval *offsetType = &nullKey;
			if (keyType != NULL) {
				arrayKeyType = toArrayKeyType(keyType);
				if (UNEXPECTED(arrayKeyType.isUndef())) return zv::Val();
				if (instanceof_function(Z_OBJCE_P(arrayKeyType.raw()), pt_ce_error_type)) return arrayKeyType;
				zv::Val keyValues = callArray(arrayKeyType.raw(), PT_LC("getconstantscalarvalues"));
				if (UNEXPECTED(keyValues.isUndef())) return zv::Val();
				for (zv::ArrayEntry keyValue : zv::ArrRef(keyValues.raw())) {
					explicitKeyValues.push(keyValue.value().deref());
				}
				offsetType = arrayKeyType.raw();
			}
			zv::Args args{offsetType, valueType, optional};
			zv::Val set = pt_type_call(builderObject, PT_LC("setoffsetvaluetype"), 3, args);
			if (UNEXPECTED(set.isUndef())) return zv::Val();
		}

		bool isList;
		if (UNEXPECTED(!kindIsList(isList))) return zv::Val();

		zval *u = unsealed();
		if (UNEXPECTED(u == NULL)) return zv::Val();
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKeyTypeWritten, *unsealedValueType;
			if (UNEXPECTED(!unsealedParts(u, unsealedKeyTypeWritten, unsealedValueType))) return zv::Val();
			zv::Val writtenOrImplicit = getUnsealedKeyType();
			if (UNEXPECTED(writtenOrImplicit.isUndef())) return zv::Val();
			zv::Val unsealedKeyType = toArrayKeyType(writtenOrImplicit.raw());
			if (UNEXPECTED(unsealedKeyType.isUndef())) return zv::Val();
			zv::Val unsealedKeyFiniteTypes = callArray(unsealedKeyType.raw(), PT_LC("getfinitetypes"));
			if (UNEXPECTED(unsealedKeyFiniteTypes.isUndef())) return zv::Val();
			if (zend_hash_num_elements(Z_ARRVAL_P(unsealedKeyFiniteTypes.raw())) > 0) {
				for (zv::ArrayEntry entry : zv::ArrRef(unsealedKeyFiniteTypes.raw())) {
					zval *unsealedKeyFiniteType = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(unsealedKeyFiniteType) != IS_OBJECT)) {
						zend_type_error("phpstan_turbo: getFiniteTypes() must return a list of Type");
						return zv::Val();
					}
					/* explicit keys own their slot — the unsealed extras
					 * describe entries at keys NOT in the explicit set */
					zv::Val finiteKeyValues = callArray(unsealedKeyFiniteType, PT_LC("getconstantscalarvalues"));
					if (UNEXPECTED(finiteKeyValues.isUndef())) return zv::Val();
					if (zend_hash_num_elements(Z_ARRVAL_P(finiteKeyValues.raw())) == 1) {
						zval *first = zend_hash_index_find(Z_ARRVAL_P(finiteKeyValues.raw()), 0);
						if (first != NULL && inArrayStrict(first, explicitKeyValues.arrRef())) continue;
					}
					zv::Args args{unsealedKeyFiniteType, unsealedValueType, true};
					zv::Val set = pt_type_call(builderObject, PT_LC("setoffsetvaluetype"), 3, args);
					if (UNEXPECTED(set.isUndef())) return zv::Val();
				}
			} else {
				zv::Args args{unsealedKeyType.raw(), unsealedValueType};
				zv::Val merged = pt_type_call(builderObject, PT_LC("mergeunsealed"), 2, args);
				if (UNEXPECTED(merged.isUndef())) return zv::Val();
			}
		}

		zv::Val arrayType = pt_type_call(builderObject, PT_LC("getarray"), 0, NULL);
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();

		bool isNonEmpty;
		if (UNEXPECTED(!kindIsNonEmpty(isNonEmpty))) return zv::Val();
		if (!isList && !isNonEmpty) return arrayType;
		/* TypeCombinator::intersect($arrayType, ...$accessories) */
		zv::Arr args = zv::Arr::create(3);
		args.push(zv::Ref(arrayType.raw()));
		if (isList) {
			zval listRaw;
			if (UNEXPECTED(!pt_accessory_array_list_type_new(&listRaw))) return zv::Val();
			args.push(zv::Val::adopt(listRaw));
		}
		if (isNonEmpty) {
			zval nonEmptyRaw;
			if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmptyRaw))) return zv::Val();
			args.push(zv::Val::adopt(nonEmptyRaw));
		}
		return pt_type_combinator_call_spread(PT_LC("intersect"), args.table());
	}

	/* the callback over every key and value (and the unsealed pair), a
	 * fresh shape through create() when any changed, $this otherwise;
	 * UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		return traverseWith(NULL, fci, fcc);
	}

	/* $this for a $right of another class or of another item count, else
	 * traverse() with $right's counterparts as the callback's second
	 * arguments (a key without a counterpart is kept) */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_late_resolvable_array_shape_type)) return thisValue();
		zval *its = items();
		zval *theirItems = its != NULL ? slot(Z_OBJ_P(right), slots::items, "items") : NULL;
		if (UNEXPECTED(theirItems == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(its)) != zend_hash_num_elements(Z_ARRVAL_P(theirItems))) return thisValue();
		return traverseWith(Z_OBJ_P(right), fci, fcc);
	}

	/* ArrayShapeNode::createSealed()/createUnsealed() over the items' nodes
	 * (a key as its name node), the unsealed pair as an
	 * ArrayShapeUnsealedTypeNode unless it is the implicit `mixed` */
	zv::Val toPhpDocNode() const
	{
		zval *its = items();
		if (UNEXPECTED(its == NULL)) return zv::Val();
		zv::Arr itemNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(its)));
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return zv::Val();
			zv::Val keyName;
			if (keyType != NULL) {
				keyName = keyNameNode(keyType);
				if (UNEXPECTED(keyName.isUndef())) return zv::Val();
			} else {
				keyName = zv::Val::null();
			}
			zv::Val valueNode = pt_type_call(Z_OBJ_P(valueType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
			zv::Args args{keyName.raw(), optional, valueNode.raw()};
			zv::Val itemNode = pt_type_new(PT_CLASS_ARRAY_SHAPE_ITEM_NODE, 3, args);
			if (UNEXPECTED(itemNode.isUndef())) return zv::Val();
			itemNodes.push(std::move(itemNode));
		}
		zval *k = kind();
		zval *u = k != NULL ? unsealed() : NULL;
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zval args[3];
		ZVAL_COPY_VALUE(&args[0], itemNodes.raw());
		if (Z_TYPE_P(u) == IS_NULL) {
			ZVAL_COPY_VALUE(&args[1], k);
			return pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createsealed"), 2, args);
		}
		zval *unsealedKeyType, *unsealedValueType;
		if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType))) return zv::Val();
		ZVAL_COPY_VALUE(&args[2], k);
		if (unsealedKeyType == NULL) {
			bool implicitMixed;
			if (UNEXPECTED(!isImplicitMixed(unsealedValueType, implicitMixed))) return zv::Val();
			if (implicitMixed) {
				ZVAL_NULL(&args[1]);
				return pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createunsealed"), 3, args);
			}
		}
		zv::Val valueNode = pt_type_call(Z_OBJ_P(unsealedValueType), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(valueNode.isUndef())) return zv::Val();
		zv::Val keyNode;
		if (unsealedKeyType != NULL) {
			keyNode = pt_type_call(Z_OBJ_P(unsealedKeyType), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(keyNode.isUndef())) return zv::Val();
		} else {
			keyNode = zv::Val::null();
		}
		zv::Args unsealedArgs{valueNode.raw(), keyNode.raw()};
		zv::Val unsealedNode = pt_type_new(PT_CLASS_ARRAY_SHAPE_UNSEALED_TYPE_NODE, 2, unsealedArgs);
		if (UNEXPECTED(unsealedNode.isUndef())) return zv::Val();
		ZVAL_COPY_VALUE(&args[1], unsealedNode.raw());
		return pt_type_call_static(PT_CLASS_ARRAY_SHAPE_NODE, PT_LC("createunsealed"), 3, args);
	}

	/* the type of the key a shape key creates: an ErrorType as is, a
	 * template type resolved to its bound first, then its array-key type —
	 * or an ErrorType naming the type when it cannot be one; UNDEF =
	 * pending exception */
	zv::Val toArrayKeyType(zval *keyType) const
	{
		if (instanceof_function(Z_OBJCE_P(keyType), pt_ce_error_type)) return zv::Val::copyOf(zv::Ref(keyType));
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(keyType, contains))) return zv::Val();
		zv::Val resolved;
		if (contains) {
			resolved = pt_type_template_type_helper_resolve_to_bounds(keyType);
			if (UNEXPECTED(resolved.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(resolved.raw()).isObject())) {
				zend_type_error("phpstan_turbo: TemplateTypeHelper::resolveToBounds() must return %s", ptcls::type);
				return zv::Val();
			}
			keyType = resolved.raw();
		}
		zv::Val arrayKeyType = pt_type_op(Z_OBJ_P(keyType), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(arrayKeyType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(arrayKeyType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toArrayKey() must return %s", ptcls::type);
			return zv::Val();
		}
		if (instanceof_function(Z_OBJCE_P(arrayKeyType.raw()), pt_ce_error_type)) {
			zv::Val level = pt_type_verbosity_level(PT_VERBOSITY_LEVEL_TYPE_ONLY);
			if (UNEXPECTED(level.isUndef())) return zv::Val();
			zv::Val description = pt_type_op(Z_OBJ_P(keyType), PT_OP_DESCRIBE, 1, level.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
				zend_type_error("phpstan_turbo: describe() must return a string");
				return zv::Val();
			}
			zend_string *reason = zend_strpprintf(0, "Type %s cannot be used as an array shape key.", ZSTR_VAL(Z_STR_P(description.raw())));
			zval errorRaw;
			bool created = pt_error_type_new(&errorRaw, reason);
			zend_string_release(reason);
			if (UNEXPECTED(!created)) return zv::Val();
			return zv::Val::adopt(errorRaw);
		}
		return arrayKeyType;
	}

	/* the unsealed key type as written down, or the implicit one: int<0,
	 * max> for a list kind, the array-key type of int|string otherwise;
	 * UNDEF = pending exception (a ShouldNotHappenException without an
	 * unsealed part) */
	zv::Val getUnsealedKeyType() const
	{
		zval *u = unsealed();
		if (UNEXPECTED(u == NULL)) return zv::Val();
		if (Z_TYPE_P(u) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval *unsealedKeyType, *unsealedValueType;
		if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType))) return zv::Val();
		if (unsealedKeyType != NULL) return zv::Val::copyOf(zv::Ref(unsealedKeyType));
		bool isList;
		if (UNEXPECTED(!kindIsList(isList))) return zv::Val();
		if (isList) {
			zval zero;
			ZVAL_LONG(&zero, 0);
			return pt_integer_range_create_all_greater_than_or_equal_to(&zero);
		}
		/* (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey() */
		zval integerRaw, stringRaw;
		if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
		zv::Val integer = zv::Val::adopt(integerRaw);
		if (UNEXPECTED(!pt_string_type_new(&stringRaw))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringRaw);
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(integer));
		types.push(std::move(string));
		zv::Val benevolent = pt_union_benevolent_of(std::move(types));
		if (UNEXPECTED(benevolent.isUndef())) return zv::Val();
		return pt_type_op(Z_OBJ_P(benevolent.raw()), PT_OP_TO_ARRAY_KEY, 0, NULL);
	}

	/* $type instanceof MixedType && !$type->isExplicitMixed() &&
	 * $type->getSubtractedType() === null; false = pending exception */
	[[nodiscard]] static bool isImplicitMixed(zval *type, bool &out)
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_mixed_type)) {
			out = false;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		if (zend_is_true(explicitMixed.raw())) {
			out = false;
			return true;
		}
		zv::Val subtractedType = pt_type_call(Z_OBJ_P(type), PT_LC("getsubtractedtype"), 0, NULL);
		if (UNEXPECTED(subtractedType.isUndef())) return false;
		out = Z_TYPE_P(subtractedType.raw()) == IS_NULL;
		return true;
	}

	/* the key's PHPDoc node as a shape key name: an identifier node as is,
	 * a const type node's string as an identifier when it is a valid one,
	 * else its integer/string/const-fetch expression, anything else as an
	 * identifier of the key's precise description; UNDEF = pending
	 * exception */
	static zv::Val keyNameNode(zval *keyType)
	{
		zv::Val node = pt_type_call(Z_OBJ_P(keyType), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(node.isUndef())) return zv::Val();
		bool is;
		if (UNEXPECTED(!pt_type_instanceof(node.raw(), PT_CLASS_IDENTIFIER_TYPE_NODE, is))) return zv::Val();
		if (is) return node;
		if (UNEXPECTED(!pt_type_instanceof(node.raw(), PT_CLASS_CONST_TYPE_NODE, is))) return zv::Val();
		if (is) {
			zv::Val constExpr = readProperty(node.raw(), PT_LC("constExpr"));
			if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
			bool isString;
			if (UNEXPECTED(!pt_type_instanceof(constExpr.raw(), PT_CLASS_CONST_EXPR_STRING_NODE, isString))) return zv::Val();
			if (isString) {
				zv::Val value = readProperty(constExpr.raw(), PT_LC("value"));
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(value.raw()).isString())) {
					zend_type_error("phpstan_turbo: ConstExprStringNode::$value must be a string");
					return zv::Val();
				}
				bool valid;
				if (UNEXPECTED(!pt_constant_array_type_is_valid_identifier(Z_STR_P(value.raw()), valid))) return zv::Val();
				if (valid) return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, value.raw());
			}
			bool isInteger, isConstFetch;
			if (UNEXPECTED(!pt_type_instanceof(constExpr.raw(), PT_CLASS_CONST_EXPR_INTEGER_NODE, isInteger) || !pt_type_instanceof(constExpr.raw(), PT_CLASS_CONST_FETCH_NODE, isConstFetch))) {
				return zv::Val();
			}
			if (isInteger || isString || isConstFetch) return constExpr;
		}
		zv::Val level = pt_type_verbosity_level(PT_VERBOSITY_LEVEL_PRECISE);
		if (UNEXPECTED(level.isUndef())) return zv::Val();
		zv::Val description = pt_type_op(Z_OBJ_P(keyType), PT_OP_DESCRIBE, 1, level.raw());
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, description.raw());
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* one item's [$keyType, $valueType, $optional] (the key NULL for null);
	 * false with a TypeError pending for anything but the twin's shape */
	static bool itemParts(zval *item, zval *&keyType, zval *&valueType, bool &optional)
	{
		if (UNEXPECTED(Z_TYPE_P(item) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: an array shape item must be a [keyType, valueType, optional] array, %s given", zend_zval_value_name(item));
			return false;
		}
		HashTable *parts = Z_ARRVAL_P(item);
		zval *key = zend_hash_index_find(parts, 0);
		zval *value = zend_hash_index_find(parts, 1);
		zval *optionalZv = zend_hash_index_find(parts, 2);
		if (key != NULL) {
			ZVAL_DEREF(key);
		}
		if (value != NULL) {
			ZVAL_DEREF(value);
		}
		if (UNEXPECTED(key == NULL || (Z_TYPE_P(key) != IS_NULL && Z_TYPE_P(key) != IS_OBJECT) || value == NULL || Z_TYPE_P(value) != IS_OBJECT || optionalZv == NULL)) {
			zend_type_error("phpstan_turbo: an array shape item must be a [keyType, valueType, optional] array");
			return false;
		}
		keyType = Z_TYPE_P(key) == IS_NULL ? NULL : key;
		valueType = value;
		optional = zend_is_true(optionalZv);
		return true;
	}

	/* the unsealed [$keyType, $valueType] (the key NULL for null); false
	 * with a TypeError pending for anything but the twin's shape */
	static bool unsealedParts(zval *unsealed, zval *&keyType, zval *&valueType)
	{
		if (UNEXPECTED(Z_TYPE_P(unsealed) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: the unsealed part of an array shape must be a [keyType, valueType] array, %s given", zend_zval_value_name(unsealed));
			return false;
		}
		HashTable *parts = Z_ARRVAL_P(unsealed);
		zval *key = zend_hash_index_find(parts, 0);
		zval *value = zend_hash_index_find(parts, 1);
		if (key != NULL) {
			ZVAL_DEREF(key);
		}
		if (value != NULL) {
			ZVAL_DEREF(value);
		}
		if (UNEXPECTED(key == NULL || (Z_TYPE_P(key) != IS_NULL && Z_TYPE_P(key) != IS_OBJECT) || value == NULL || Z_TYPE_P(value) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: the unsealed part of an array shape must be a [keyType, valueType] array");
			return false;
		}
		keyType = Z_TYPE_P(key) == IS_NULL ? NULL : key;
		valueType = value;
		return true;
	}

	/* [$keyType, $valueType, $optional] / [$keyType, $valueType] as the
	 * twin's lists (a NULL key as null) */
	static zv::Val triple(zval *keyType, zval *valueType, bool optional)
	{
		zv::Arr item = zv::Arr::create(3);
		item.push(keyType != NULL ? zv::Val::copyOf(zv::Ref(keyType)) : zv::Val::null());
		item.push(zv::Ref(valueType));
		item.push(zv::Val::boolean(optional));
		return zv::Val(std::move(item));
	}

	static zv::Val pair(zval *keyType, zval *valueType)
	{
		zv::Arr item = zv::Arr::create(2);
		item.push(keyType != NULL ? zv::Val::copyOf(zv::Ref(keyType)) : zv::Val::null());
		item.push(zv::Ref(valueType));
		return zv::Val(std::move(item));
	}

	/* $a->equals($b); false = pending exception */
	[[nodiscard]] static bool typesEqual(zval *a, zval *b, bool &out)
	{
		return pt_type_op_bool(Z_OBJ_P(a), PT_OP_EQUALS, 1, b, out);
	}

	/* $type->method(), checked to return an array; UNDEF = pending exception */
	static zv::Val callArray(zval *type, const char *lcname, size_t len)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(type), lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s() must return an array", lcname);
			return zv::Val();
		}
		return result;
	}

	/* in_array($needle, $haystack, true) */
	static bool inArrayStrict(zval *needle, zv::ArrRef haystack)
	{
		for (zv::ArrayEntry entry : haystack) {
			if (zend_is_identical(needle, entry.value().deref().raw())) return true;
		}
		return false;
	}

	/* $object->$name (a public property); UNDEF = pending exception */
	static zv::Val readProperty(zval *object, const char *name, size_t len)
	{
		if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: cannot read property %s of a non-object", name);
			return zv::Val();
		}
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
		if (UNEXPECTED(value == NULL || EG(exception))) return zv::Val();
		zv::Val copy = zv::Val::copyOf(zv::Ref(value));
		if (value == &rv) {
			zval_ptr_dtor(&rv);
		}
		return copy;
	}

	/* $this->kind === ArrayShapeNode::<constant>; false = pending exception */
	[[nodiscard]] bool kindIs(const char *constantName, bool &out) const
	{
		zval *k = kind();
		if (UNEXPECTED(k == NULL)) return false;
		zend_class_entry *ce = pt_class(PT_CLASS_ARRAY_SHAPE_NODE);
		if (UNEXPECTED(ce == NULL)) return false;
		zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, constantName, strlen(constantName));
		if (UNEXPECTED(constant == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), constantName);
			return false;
		}
		if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return false;
		out = Z_TYPE(constant->value) == IS_STRING && zend_string_equals(Z_STR(constant->value), Z_STR_P(k));
		return true;
	}

	/* in_array($this->kind, [ArrayShapeNode::KIND_LIST, ArrayShapeNode::KIND_NON_EMPTY_LIST], true) */
	bool kindIsList(bool &out) const
	{
		bool is;
		if (UNEXPECTED(!kindIs("KIND_LIST", is))) return false;
		if (is) {
			out = true;
			return true;
		}
		return kindIs("KIND_NON_EMPTY_LIST", out);
	}

	/* in_array($this->kind, [ArrayShapeNode::KIND_NON_EMPTY_ARRAY, ArrayShapeNode::KIND_NON_EMPTY_LIST], true) */
	bool kindIsNonEmpty(bool &out) const
	{
		bool is;
		if (UNEXPECTED(!kindIs("KIND_NON_EMPTY_ARRAY", is))) return false;
		if (is) {
			out = true;
			return true;
		}
		return kindIs("KIND_NON_EMPTY_LIST", out);
	}

	/* array_merge() of every key's, value's and the unsealed pair's
	 * method(...$args); UNDEF = pending exception */
	zv::Val mergedOfParts(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *its = items();
		if (UNEXPECTED(its == NULL)) return zv::Val();
		zv::Arr merged = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return zv::Val();
			if (keyType != NULL && UNEXPECTED(!mergeInto(merged, keyType, lcname, len, argc, argv))) return zv::Val();
			if (UNEXPECTED(!mergeInto(merged, valueType, lcname, len, argc, argv))) return zv::Val();
		}
		zval *u = unsealed();
		if (UNEXPECTED(u == NULL)) return zv::Val();
		if (Z_TYPE_P(u) != IS_NULL) {
			zval *unsealedKeyType, *unsealedValueType;
			if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType))) return zv::Val();
			if (unsealedKeyType != NULL && UNEXPECTED(!mergeInto(merged, unsealedKeyType, lcname, len, argc, argv))) return zv::Val();
			if (UNEXPECTED(!mergeInto(merged, unsealedValueType, lcname, len, argc, argv))) return zv::Val();
		}
		return zv::Val(std::move(merged));
	}

	/* $into = array_merge($into, $type->method(...$args)); false = pending exception */
	[[nodiscard]] static bool mergeInto(zv::Arr &into, zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val part = pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
		if (UNEXPECTED(part.isUndef())) return false;
		return pt_callable_array_merge_into(into, part.raw());
	}

	/* traverse() / traverseSimultaneously() ($right NULL for the former):
	 * the callback over every key (kept when either side has none) and
	 * value, and the unsealed pair (when both sides have one); self::create()
	 * over the results when any changed */
	zv::Val traverseWith(zend_object *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *its = items();
		if (UNEXPECTED(its == NULL)) return zv::Val();
		zval *theirItems = NULL;
		if (right != NULL) {
			theirItems = slot(right, slots::items, "items");
			if (UNEXPECTED(theirItems == NULL)) return zv::Val();
		}
		bool stillOriginal = true;
		zv::Arr newItems = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(its)));
		for (zv::ArrayEntry entry : zv::ArrRef(its)) {
			zval *keyType, *valueType;
			bool optional;
			if (UNEXPECTED(!itemParts(entry.value().deref().raw(), keyType, valueType, optional))) return zv::Val();
			zval *rightKeyType = NULL, *rightValueType = NULL;
			if (right != NULL) {
				zval *theirItem = zend_hash_index_find(Z_ARRVAL_P(theirItems), entry.indexKey());
				if (UNEXPECTED(theirItem == NULL)) {
					zend_throw_error(NULL, "phpstan_turbo: the items of %s are not a list", ZSTR_VAL(pt_ce_late_resolvable_array_shape_type->name));
					return zv::Val();
				}
				bool rightOptional;
				if (UNEXPECTED(!itemParts(theirItem, rightKeyType, rightValueType, rightOptional))) return zv::Val();
			}
			zv::Val newKeyType;
			if (keyType != NULL && (right == NULL || rightKeyType != NULL)) {
				newKeyType = pt_type_traverse_call(fci, fcc, keyType, rightKeyType);
				if (UNEXPECTED(newKeyType.isUndef())) return zv::Val();
			} else if (keyType != NULL) {
				newKeyType = zv::Val::copyOf(zv::Ref(keyType));
			} else {
				newKeyType = zv::Val::null();
			}
			zv::Val newValueType = pt_type_traverse_call(fci, fcc, valueType, rightValueType);
			if (UNEXPECTED(newValueType.isUndef())) return zv::Val();
			if ((keyType == NULL) != newKeyType.isNull() || (keyType != NULL && !pt_type_same_object(keyType, newKeyType.raw())) || !pt_type_same_object(valueType, newValueType.raw())) {
				stillOriginal = false;
			}
			newItems.push(triple(newKeyType.isNull() ? NULL : newKeyType.raw(), newValueType.raw(), optional));
		}
		zval *u = unsealed();
		if (UNEXPECTED(u == NULL)) return zv::Val();
		zv::Val newUnsealed = zv::Val::copyOf(zv::Ref(u));
		zval *theirUnsealed = NULL;
		if (right != NULL) {
			theirUnsealed = slot(right, slots::unsealed, "unsealed");
			if (UNEXPECTED(theirUnsealed == NULL)) return zv::Val();
		}
		if (Z_TYPE_P(u) != IS_NULL && (right == NULL || Z_TYPE_P(theirUnsealed) != IS_NULL)) {
			zval *unsealedKeyType, *unsealedValueType, *rightUnsealedKeyType = NULL, *rightUnsealedValueType = NULL;
			if (UNEXPECTED(!unsealedParts(u, unsealedKeyType, unsealedValueType))) return zv::Val();
			if (right != NULL && UNEXPECTED(!unsealedParts(theirUnsealed, rightUnsealedKeyType, rightUnsealedValueType))) return zv::Val();
			zv::Val newUnsealedKeyType;
			if (unsealedKeyType != NULL && (right == NULL || rightUnsealedKeyType != NULL)) {
				newUnsealedKeyType = pt_type_traverse_call(fci, fcc, unsealedKeyType, rightUnsealedKeyType);
				if (UNEXPECTED(newUnsealedKeyType.isUndef())) return zv::Val();
			} else if (unsealedKeyType != NULL) {
				newUnsealedKeyType = zv::Val::copyOf(zv::Ref(unsealedKeyType));
			} else {
				newUnsealedKeyType = zv::Val::null();
			}
			zv::Val newUnsealedValueType = pt_type_traverse_call(fci, fcc, unsealedValueType, rightUnsealedValueType);
			if (UNEXPECTED(newUnsealedValueType.isUndef())) return zv::Val();
			if ((unsealedKeyType == NULL) != newUnsealedKeyType.isNull() || (unsealedKeyType != NULL && !pt_type_same_object(unsealedKeyType, newUnsealedKeyType.raw())) || !pt_type_same_object(unsealedValueType, newUnsealedValueType.raw())) {
				stillOriginal = false;
				newUnsealed = pair(newUnsealedKeyType.isNull() ? NULL : newUnsealedKeyType.raw(), newUnsealedValueType.raw());
			}
		}
		if (stillOriginal) return thisValue();
		zval *k = kind();
		if (UNEXPECTED(k == NULL)) return zv::Val();
		return create(newItems.raw(), newUnsealed.isNull() ? NULL : newUnsealed.raw(), Z_STR_P(k));
	}
};

} // namespace phpstanturbo

using phpstanturbo::LateResolvableArrayShapeType;

bool pt_late_resolvable_array_shape_type_create(zval *out, zval *items, zval *unsealed, zend_string *kind)
{
	return pt_val_into(LateResolvableArrayShapeType::create(items, unsealed, kind), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS LateResolvableArrayShapeType(Z_OBJ_P(ZEND_THIS))

static void ZEND_FASTCALL lrasEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

void pt_register_late_resolvable_array_shape_type()
{
	reg::Class cls("PHPStan\\Type\\LateResolvableArrayShapeType");
	ptdecl::LateResolvableArrayShapeType::declareClass(cls);
	/* the slots must stay in this order (PT_LRAS_PROP_*); the trait
	 * registrar declares $result after them */
	ptdecl::LateResolvableArrayShapeType::declareProperties(cls);

	cls.method<&LateResolvableArrayShapeType::construct, zp::Arr, zp::ArrOrNull, zp::Str>(sigs::__construct);

	cls.method<&LateResolvableArrayShapeType::create, zp::Arr, zp::ArrOrNull, zp::Str>(sigs::create);

	cls.method<&LateResolvableArrayShapeType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.method(sigs::getObjectClassNames, lrasEmptyArray0);
	cls.method(sigs::getObjectClassReflections, lrasEmptyArray0);

	cls.method<&LateResolvableArrayShapeType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&LateResolvableArrayShapeType::equals, zp::Obj>(sigs::equals);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.describe());
	});

	cls.method<&LateResolvableArrayShapeType::isResolvable>(sigs::isResolvable);

	cls.method<&LateResolvableArrayShapeType::getResult>(sigs::getResult);

	cls.method<&LateResolvableArrayShapeType::toArrayKeyType, zp::Obj>(sigs::toArrayKeyType);

	cls.method<&LateResolvableArrayShapeType::getUnsealedKeyType>(sigs::getUnsealedKeyType);

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

	cls.method<&LateResolvableArrayShapeType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::isImplicitMixed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		bool implicitMixed;
		if (UNEXPECTED(!LateResolvableArrayShapeType::isImplicitMixed(type, implicitMixed))) RETURN_THROWS();
		RETURN_BOOL(implicitMixed);
	});

	cls.method<&LateResolvableArrayShapeType::keyNameNode, zp::Obj>(sigs::keyNameNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::LateResolvableArrayShapeType::registerTraits(cls);

	cls.shadow(&pt_ce_late_resolvable_array_shape_type);
}

/* }}} */
