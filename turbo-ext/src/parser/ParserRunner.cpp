/*
 * PHPStanTurbo\ParserRunner — native LALR engine for php-parser 5.8.0.
 * phpstanturbo::ParserEngine mirrors PhpParser\ParserAbstract::parse()/doParse()
 * exactly; the generated reduce actions live in ParserRunnerActions*.cpp, the
 * ported semantic helpers in ParserRunnerHelpers.cpp. PHP twin:
 * PHPStan\Parser\ParserRunner.
 *
 * The parsing tables are read once per process from the first Php8 parser
 * object seen (they are generated data on the object); node classes resolve
 * relative to the parser CE's namespace so the scoped phar works unchanged.
 */

#include "ParserEngine.h"
#include "ParserRunnerActionsSplit.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
extern "C" {
#include <ext/spl/spl_exceptions.h>
#include <zend_language_parser.h> /* T_COMMENT / T_DOC_COMMENT / T_WHITESPACE ids */
}
#pragma GCC diagnostic pop

namespace phpstanturbo {

/* {{{ process-wide caches (NTS, CLI process lifetime) */

static Tables g_tables;
static bool g_tablesReady = false;
static char *g_nsPrefix = NULL; /* malloc'd */
static size_t g_nsPrefixLen = 0;

static HashTable g_classRegistry; /* alias => NodeClassInfo* (malloc'd) */
static bool g_registryReady = false;

static zend_class_entry *g_errorCe = NULL;

static zend_string *g_key_startLine, *g_key_startTokenPos, *g_key_startFilePos,
	*g_key_endLine, *g_key_endTokenPos, *g_key_endFilePos, *g_key_comments;
static bool g_keysReady = false;

static void initAttributeKeys(void)
{
	if (g_keysReady) {
		return;
	}
	g_key_startLine = zend_string_init("startLine", sizeof("startLine") - 1, 1);
	g_key_startTokenPos = zend_string_init("startTokenPos", sizeof("startTokenPos") - 1, 1);
	g_key_startFilePos = zend_string_init("startFilePos", sizeof("startFilePos") - 1, 1);
	g_key_endLine = zend_string_init("endLine", sizeof("endLine") - 1, 1);
	g_key_endTokenPos = zend_string_init("endTokenPos", sizeof("endTokenPos") - 1, 1);
	g_key_endFilePos = zend_string_init("endFilePos", sizeof("endFilePos") - 1, 1);
	g_key_comments = zend_string_init("comments", sizeof("comments") - 1, 1);
	g_keysReady = true;
}

/* }}} */

/* {{{ lifecycle */

ParserEngine::ParserEngine(zval *parserObj, zval *errorHandler)
	: parserObj(parserObj), errorHandler(errorHandler)
{
	tables = &g_tables;
	semValue = zv::Val::null();
	zend_hash_init(&createdArrays, 8, NULL, ZVAL_PTR_DTOR, 0);
	zend_hash_init(&parenthesizedArrowFns, 8, NULL, NULL, 0);
}

ParserEngine::~ParserEngine()
{
	if (semStack != NULL) {
		for (int i = 0; i < stackCap; i++) {
			if (!Z_ISUNDEF(semStack[i])) {
				zval_ptr_dtor(&semStack[i]);
			}
		}
		efree(semStack);
		efree(stateStack);
		efree(tokenStartStack);
		efree(tokenEndStack);
	}
	zend_hash_destroy(&createdArrays);
	zend_hash_destroy(&parenthesizedArrowFns);
	if (tokens != NULL) {
		efree(tokens);
	}
}

/* }}} */

/* {{{ reduce dispatch */

bool ParserEngine::reduce(int rule, int stackPos)
{
	if (rule < PN_REDUCE_SPLIT_1) {
		return reduceRange1(rule, stackPos);
	}
	if (rule < PN_REDUCE_SPLIT_2) {
		return reduceRange2(rule, stackPos);
	}
	return reduceRange3(rule, stackPos);
}

/* }}} */

/* {{{ small value helpers */

zv::Val ParserEngine::substr(zv::Ref str, zend_long offset)
{
	zend_string *s = str.asString();
	zend_long slen = (zend_long) ZSTR_LEN(s);
	if (offset < 0) {
		offset += slen;
		if (offset < 0) {
			offset = 0;
		}
	}
	if (offset > slen) {
		offset = slen;
	}
	zval z;
	ZVAL_STRINGL(&z, ZSTR_VAL(s) + offset, (size_t) (slen - offset));
	return zv::Val::adopt(z);
}

/* }}} */

/* {{{ attributes */

zv::Arr ParserEngine::getAttributes(int tokenStartPos, int tokenEndPos)
{
	initAttributeKeys();
	const Token *startToken = &tokens[tokenStartPos];
	const Token *afterEndToken = &tokens[tokenEndPos + 1];
	zv::Arr attrs = zv::Arr::create(6);
	HashTable *ht = attrs.table();
	zval v;
	ZVAL_LONG(&v, startToken->line);
	zend_hash_add_new(ht, g_key_startLine, &v);
	ZVAL_LONG(&v, tokenStartPos);
	zend_hash_add_new(ht, g_key_startTokenPos, &v);
	ZVAL_LONG(&v, startToken->pos);
	zend_hash_add_new(ht, g_key_startFilePos, &v);
	ZVAL_LONG(&v, afterEndToken->line);
	zend_hash_add_new(ht, g_key_endLine, &v);
	ZVAL_LONG(&v, tokenEndPos);
	zend_hash_add_new(ht, g_key_endTokenPos, &v);
	ZVAL_LONG(&v, afterEndToken->pos - 1);
	zend_hash_add_new(ht, g_key_endFilePos, &v);
	return attrs;
}

zv::Arr ParserEngine::getAttributesAt(int stackPos)
{
	return getAttributes(tokenStartStack[stackPos], tokenEndStack[stackPos]);
}

zv::Arr ParserEngine::getAttributesForToken(int tokenPos)
{
	if (tokenPos < numTokens - 1) {
		return getAttributes(tokenPos, tokenPos);
	}
	initAttributeKeys();
	const Token *token = &tokens[tokenPos];
	zv::Arr attrs = zv::Arr::create(6);
	HashTable *ht = attrs.table();
	zval v;
	ZVAL_LONG(&v, token->line);
	zend_hash_add_new(ht, g_key_startLine, &v);
	ZVAL_LONG(&v, tokenPos);
	zend_hash_add_new(ht, g_key_startTokenPos, &v);
	ZVAL_LONG(&v, token->pos);
	zend_hash_add_new(ht, g_key_startFilePos, &v);
	ZVAL_LONG(&v, token->line);
	zend_hash_add_new(ht, g_key_endLine, &v);
	ZVAL_LONG(&v, tokenPos);
	zend_hash_add_new(ht, g_key_endTokenPos, &v);
	ZVAL_LONG(&v, token->pos);
	zend_hash_add_new(ht, g_key_endFilePos, &v);
	return attrs;
}

/* }}} */

/* {{{ node property access */

zv::Ref ParserEngine::prop(zv::Ref node, const char *name)
{
	return zv::ObjRef(node.raw()).prop(name, strlen(name));
}

void ParserEngine::propWrite(zv::Ref node, const char *name, zv::Val value)
{
	zv::Ref slot = prop(node, name);
	if (slot.raw() == NULL) {
		return; /* the Val releases the value */
	}
	zval_ptr_dtor(slot.raw());
	zval v = value.take();
	ZVAL_COPY_VALUE(slot.raw(), &v);
}

zv::Arr ParserEngine::getNodeAttributes(zv::Ref node)
{
	zv::Ref attrs = prop(node, "attributes");
	if (attrs.raw() == NULL || !attrs.isArray()) {
		return zv::Arr::create(0);
	}
	zv::Arr copy;
	ZVAL_COPY(copy.raw(), attrs.raw());
	return copy;
}

void ParserEngine::setNodeAttribute(zv::Ref node, const char *key, zv::Val value)
{
	zv::Ref attrs = prop(node, "attributes");
	if (attrs.raw() == NULL || !attrs.isArray()) {
		return; /* the Val releases the value */
	}
	SEPARATE_ARRAY(attrs.raw());
	zval v = value.take();
	zend_hash_str_update(Z_ARRVAL_P(attrs.raw()), key, strlen(key), &v);
}

zv::Ref ParserEngine::itemAt(zv::Ref array, zend_ulong index)
{
	return zv::Ref(zend_hash_index_find(Z_ARRVAL_P(array.raw()), index));
}

/* }}} */

/* {{{ class resolution + node creation */

static zend_class_entry *lookupClassPrefixed(const char *relative, size_t relativeLen)
{
	size_t len = g_nsPrefixLen + relativeLen;
	char *name = (char *) emalloc(len + 1);
	memcpy(name, g_nsPrefix, g_nsPrefixLen);
	memcpy(name + g_nsPrefixLen, relative, relativeLen);
	name[len] = '\0';
	zend_string *zname = zend_string_init(name, len, 0);
	efree(name);
	zend_class_entry *ce = zend_lookup_class(zname);
	zend_string_release(zname);
	return ce;
}

NodeClassInfo *ParserEngine::resolveNodeClass(const char *alias, bool useCtor)
{
	if (!g_registryReady) {
		zend_hash_init(&g_classRegistry, 64, NULL, NULL, 1);
		g_registryReady = true;
	}
	size_t aliasLen = strlen(alias);
	zval *cached = zend_hash_str_find(&g_classRegistry, alias, aliasLen);
	NodeClassInfo *cls;
	if (cached != NULL) {
		cls = (NodeClassInfo *) Z_PTR_P(cached);
	} else {
		/* try PhpParser\Node\<alias>, then PhpParser\<alias> */
		zend_class_entry *ce = NULL;
		{
			const char *nodePrefix = "PhpParser\\Node\\";
			size_t rl = strlen(nodePrefix) + aliasLen;
			char *rel = (char *) emalloc(rl + 1);
			memcpy(rel, nodePrefix, strlen(nodePrefix));
			memcpy(rel + strlen(nodePrefix), alias, aliasLen + 1);
			ce = lookupClassPrefixed(rel, rl);
			efree(rel);
		}
		if (ce == NULL) {
			const char *plainPrefix = "PhpParser\\";
			size_t rl = strlen(plainPrefix) + aliasLen;
			char *rel = (char *) emalloc(rl + 1);
			memcpy(rel, plainPrefix, strlen(plainPrefix));
			memcpy(rel + strlen(plainPrefix), alias, aliasLen + 1);
			ce = lookupClassPrefixed(rel, rl);
			efree(rel);
		}
		if (ce == NULL) {
			return NULL;
		}

		cls = (NodeClassInfo *) malloc(sizeof(NodeClassInfo));
		memset(cls, 0, sizeof(*cls));
		cls->ce = ce;
		cls->attrsSlot = UINT32_MAX;

		int32_t attrsOffset = pt_instance_prop_offset(ce, "attributes", sizeof("attributes") - 1);
		if (attrsOffset >= 0) {
			cls->attrsSlot = (uint32_t) attrsOffset;
		}

		zval ptr;
		ZVAL_PTR(&ptr, cls);
		zend_hash_str_add(&g_classRegistry, alias, aliasLen, &ptr);
	}

	if (!useCtor && cls->planState == NodeClassInfo::PLAN_NONE) {
		/* derive the property-write plan from the constructor's parameter
		 * names — lazily, so a useCtor=true resolve (isInstanceOf) seeing the
		 * class first cannot deny later slot-write callers the plan */
		cls->planState = NodeClassInfo::PLAN_FAILED;
		zend_function *ctor = cls->ce->constructor;
		if (ctor != NULL && ctor->type == ZEND_USER_FUNCTION && cls->attrsSlot != UINT32_MAX) {
			uint32_t numArgs = ctor->op_array.num_args;
			int nprops = 0;
			bool ok = true;
			for (uint32_t i = 0; i < numArgs; i++) {
				zend_string *argName = ctor->op_array.arg_info[i].name;
				if (zend_string_equals_literal(argName, "attributes")) {
					continue;
				}
				if (nprops >= 16) {
					ok = false;
					break;
				}
				int32_t off = pt_instance_prop_offset(cls->ce, ZSTR_VAL(argName), ZSTR_LEN(argName));
				if (off < 0) {
					ok = false;
					break;
				}
				cls->propSlots[nprops++] = off;
			}
			if (ok) {
				cls->numProps = nprops;
				cls->planState = NodeClassInfo::PLAN_OK;
			}
		}
	}

	return cls;
}

zv::Val ParserEngine::createNode(const char *alias, bool useCtor, zv::Val attributes, int nprops, zval **props)
{
	NodeClassInfo *cls = resolveNodeClass(alias, useCtor);
	if (cls == NULL) {
		fatalError("phpstan_turbo: unknown node class", zv::Arr::empty());
		return zv::Val();
	}

	zval attrs = attributes.take();
	zval node;

	if (!useCtor && cls->planState == NodeClassInfo::PLAN_OK && nprops == cls->numProps) {
		object_init_ex(&node, cls->ce);
		zend_object *zobj = Z_OBJ(node);
		for (int i = 0; i < nprops; i++) {
			zval *slot = OBJ_PROP(zobj, (uint32_t) cls->propSlots[i]);
			zval_ptr_dtor(slot);
			if (props[i] == NULL) {
				ZVAL_NULL(slot);
			} else {
				ZVAL_COPY(slot, props[i]);
			}
		}
		zval *attrsSlot = OBJ_PROP(zobj, cls->attrsSlot);
		zval_ptr_dtor(attrsSlot);
		ZVAL_COPY_VALUE(attrsSlot, &attrs);
		return zv::Val::adopt(node);
	}

	/* exact-semantics path: call the PHP constructor */
	object_init_ex(&node, cls->ce);
	zval args[16];
	uint32_t argc = 0;
	for (int i = 0; i < nprops; i++) {
		if (props[i] == NULL) {
			ZVAL_NULL(&args[argc++]);
		} else {
			ZVAL_COPY_VALUE(&args[argc++], props[i]);
		}
	}
	ZVAL_COPY_VALUE(&args[argc++], &attrs);
	zend_call_known_function(cls->ce->constructor, Z_OBJ(node), cls->ce, NULL, argc, args, NULL);
	zval_ptr_dtor(&attrs);
	if (EG(exception) != NULL) {
		abortForPendingException();
		zval_ptr_dtor(&node);
		return zv::Val();
	}
	return zv::Val::adopt(node);
}

/*
 * Mirrors doParse()'s catch (Error $e): a pending PhpParser\Error becomes the
 * aborted/abortErrorMsg channel (so it reaches the error handler with a
 * startLine), any other pending exception just marks the parse aborted and
 * propagates. Callers that invoke PHP code which may throw PhpParser\Error
 * (node constructors, String_::parseEscapeSequences) must route through this.
 */
void ParserEngine::abortForPendingException()
{
	zend_object *ex = EG(exception);
	if (g_errorCe != NULL && instanceof_function(ex->ce, g_errorCe)) {
		zval rvMsg, rvAttrs;
		ZVAL_UNDEF(&rvMsg);
		ZVAL_UNDEF(&rvAttrs);
		zend_function *getMsg = pt_find_method(ex->ce, "getrawmessage", sizeof("getrawmessage") - 1);
		zend_function *getAttrs = pt_find_method(ex->ce, "getattributes", sizeof("getattributes") - 1);
		zend_object *exKeepAlive = ex;
		GC_ADDREF(exKeepAlive);
		zend_clear_exception();
		if (getMsg != NULL) {
			zend_call_known_function(getMsg, exKeepAlive, exKeepAlive->ce, &rvMsg, 0, NULL, NULL);
		}
		if (getAttrs != NULL) {
			zend_call_known_function(getAttrs, exKeepAlive, exKeepAlive->ce, &rvAttrs, 0, NULL, NULL);
		}
		OBJ_RELEASE(exKeepAlive);
		aborted = true;
		abortErrorMsg = zv::Val::adopt(rvMsg);
		abortErrorAttrs = zv::Val::adopt(rvAttrs);
	} else {
		aborted = true; /* propagate the pending exception */
	}
}

bool ParserEngine::isInstanceOf(zv::Ref value, const char *alias)
{
	if (!value.isObject()) {
		return false;
	}
	NodeClassInfo *cls = resolveNodeClass(alias, true);
	if (cls == NULL) {
		return false;
	}
	return instanceof_function(Z_OBJCE_P(value.raw()), cls->ce);
}

/* }}} */

/* {{{ errors */

static zval makeErrorObject(zend_string *msg, zval *attrsBorrowed)
{
	zval error;
	object_init_ex(&error, g_errorCe);
	zval args[2];
	ZVAL_STR(&args[0], msg);
	ZVAL_COPY_VALUE(&args[1], attrsBorrowed);
	zend_call_known_function(g_errorCe->constructor, Z_OBJ(error), g_errorCe, NULL, 2, args, NULL);
	return error;
}

void ParserEngine::emitError(zend_string *msg, zv::Val attributes)
{
	zval error = makeErrorObject(msg, attributes.raw());
	attributes.release();
	if (EG(exception) != NULL) {
		aborted = true;
		zval_ptr_dtor(&error);
		return;
	}
	zend_object *handler = Z_OBJ_P(errorHandler);
	zend_function *fn = pt_find_method(handler->ce, "handleerror", sizeof("handleerror") - 1);
	if (fn != NULL) {
		zend_call_known_function(fn, handler, handler->ce, NULL, 1, &error, NULL);
	}
	zval_ptr_dtor(&error);
	if (EG(exception) != NULL) {
		/* a Throwing handler throws the Error: abort and let it propagate */
		aborted = true;
		abortErrorMsg.release(); /* pending zend exception, not a caught Error */
	}
}

void ParserEngine::emitError(const char *msg, zv::Val attributes)
{
	zend_string *zmsg = zend_string_init(msg, strlen(msg), 0);
	emitError(zmsg, std::move(attributes));
	zend_string_release(zmsg);
}

void ParserEngine::fatalError(const char *msg, zv::Val attributes)
{
	if (aborted) {
		return; /* the Val releases the attributes */
	}
	aborted = true;
	zval m;
	ZVAL_STRING(&m, msg);
	abortErrorMsg = zv::Val::adopt(m);
	abortErrorAttrs = std::move(attributes);
}

/* }}} */

/* {{{ per-parse object-set trackers */

void ParserEngine::createdArraysAdd(zv::Ref arrayNode)
{
	zval copy;
	ZVAL_COPY(&copy, arrayNode.raw());
	zend_hash_index_update(&createdArrays, Z_OBJ_HANDLE_P(arrayNode.raw()), &copy);
}

void ParserEngine::createdArraysRemove(zv::Ref arrayNode)
{
	zend_hash_index_del(&createdArrays, Z_OBJ_HANDLE_P(arrayNode.raw()));
}

void ParserEngine::parenthesizedArrowFunctionsAdd(zv::Ref expr)
{
	zval null;
	ZVAL_NULL(&null);
	zend_hash_index_update(&parenthesizedArrowFns, Z_OBJ_HANDLE_P(expr.raw()), &null);
}

/* }}} */

/* {{{ table extraction */

static bool readIntProp(zval *obj, const char *name, int *out)
{
	zv::Ref slot = zv::ObjRef(obj).prop(name, strlen(name));
	if (slot.raw() == NULL || !slot.isLong()) {
		return false;
	}
	*out = (int) slot.asLong();
	return true;
}

/* copies a packed-or-sparse int array property into a malloc'd dense int array;
 * negative keys shift the whole array so the entry for key k sits at k + *outBias
 * (php-parser assigns compat-token ids from -1 downward on hosts whose tokenizer
 * lacks them — see defineCompatibilityTokens()). Callers reading 0-based grammar
 * tables pass outBias = NULL; a negative key then fails the read (delegate to the
 * PHP twin) instead of silently shifting the table's indexing. */
static bool readIntArrayProp(zval *obj, const char *name, int **out, int *outSize, int *outBias)
{
	zv::Ref slot = zv::ObjRef(obj).prop(name, strlen(name));
	if (slot.raw() == NULL || !slot.isArray()) {
		return false;
	}
	HashTable *ht = slot.asArrayTable();
	zend_long maxKey = -1;
	zend_long minKey = 0;
	zend_ulong idx;
	zend_string *strKey;
	zval *v;
	ZEND_HASH_FOREACH_KEY_VAL(ht, idx, strKey, v) {
		(void) v;
		if (strKey != NULL) {
			return false;
		}
		zend_long key = (zend_long) idx;
		if (key > maxKey) {
			maxKey = key;
		}
		if (key < minKey) {
			minKey = key;
		}
	} ZEND_HASH_FOREACH_END();
	if (minKey < 0 && outBias == NULL) {
		return false;
	}
	int bias = (int) -minKey;
	int size = (int) (maxKey - minKey) + 1;
	if (zend_hash_num_elements(ht) == 0) {
		size = 0;
		bias = 0;
	}
	int *arr = (int *) malloc(sizeof(int) * (size_t) (size > 0 ? size : 1));
	for (int i = 0; i < size; i++) {
		arr[i] = -1;
	}
	ZEND_HASH_FOREACH_NUM_KEY_VAL(ht, idx, v) {
		if (Z_TYPE_P(v) == IS_LONG) {
			arr[(zend_long) idx + bias] = (int) Z_LVAL_P(v);
		}
	} ZEND_HASH_FOREACH_END();
	*out = arr;
	*outSize = size;
	if (outBias != NULL) {
		*outBias = bias;
	}
	return true;
}

static bool extractTables(zval *parserObj)
{
	zend_class_entry *ce = Z_OBJCE_P(parserObj);

	/* namespace prefix for scoped-phar-safe class resolution */
	static const char suffix[] = "PhpParser\\Parser\\Php8";
	size_t suffixLen = sizeof(suffix) - 1;
	zend_string *ceName = ce->name;
	if (ZSTR_LEN(ceName) < suffixLen
		|| memcmp(ZSTR_VAL(ceName) + ZSTR_LEN(ceName) - suffixLen, suffix, suffixLen) != 0) {
		return false;
	}
	g_nsPrefixLen = ZSTR_LEN(ceName) - suffixLen;
	g_nsPrefix = (char *) malloc(g_nsPrefixLen + 1);
	memcpy(g_nsPrefix, ZSTR_VAL(ceName), g_nsPrefixLen);
	g_nsPrefix[g_nsPrefixLen] = '\0';

	Tables *t = &g_tables;
	memset(t, 0, sizeof(*t));
	t->parserCe = ce;

	bool ok = readIntProp(parserObj, "tokenToSymbolMapSize", &t->tokenToSymbolMapSize)
		&& readIntProp(parserObj, "actionTableSize", &t->actionTableSize)
		&& readIntProp(parserObj, "gotoTableSize", &t->gotoTableSize)
		&& readIntProp(parserObj, "invalidSymbol", &t->invalidSymbol)
		&& readIntProp(parserObj, "errorSymbol", &t->errorSymbol)
		&& readIntProp(parserObj, "defaultAction", &t->defaultAction)
		&& readIntProp(parserObj, "unexpectedTokenRule", &t->unexpectedTokenRule)
		&& readIntProp(parserObj, "YY2TBLSTATE", &t->YY2TBLSTATE)
		&& readIntProp(parserObj, "numNonLeafStates", &t->numNonLeafStates);
	int unusedSize;
	ok = ok
		&& readIntArrayProp(parserObj, "phpTokenToSymbol", &t->phpTokenToSymbol, &t->phpTokenToSymbolSize, &t->phpTokenToSymbolBias)
		&& readIntArrayProp(parserObj, "actionBase", &t->actionBase, &t->actionBaseSize, NULL)
		&& readIntArrayProp(parserObj, "action", &t->action, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "actionCheck", &t->actionCheck, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "actionDefault", &t->actionDefault, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "gotoBase", &t->gotoBase, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "goto", &t->gotoTable, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "gotoCheck", &t->gotoCheck, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "gotoDefault", &t->gotoDefault, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "ruleToNonTerminal", &t->ruleToNonTerminal, &unusedSize, NULL)
		&& readIntArrayProp(parserObj, "ruleToLength", &t->ruleToLength, &t->numRules, NULL);
	if (!ok) {
		return false;
	}

	/* dropTokens: bool array indexed by php token id */
	{
		zv::Ref slot = zv::ObjRef(parserObj).prop("dropTokens", sizeof("dropTokens") - 1);
		if (slot.raw() == NULL || !slot.isArray()) {
			return false;
		}
		int size = t->phpTokenToSymbolSize > 1024 ? t->phpTokenToSymbolSize : 1024;
		t->dropTokens = (bool *) malloc(sizeof(bool) * (size_t) size);
		t->dropTokensSize = size;
		memset(t->dropTokens, 0, sizeof(bool) * (size_t) size);
		zend_ulong idx;
		zval *v;
		ZEND_HASH_FOREACH_NUM_KEY_VAL(slot.asArrayTable(), idx, v) {
			(void) v;
			if ((zend_long) idx >= 0 && (zend_long) idx < size) {
				t->dropTokens[idx] = true;
			}
		} ZEND_HASH_FOREACH_END();
	}

	/* symbolToName: persistent copies for error messages */
	{
		zv::Ref slot = zv::ObjRef(parserObj).prop("symbolToName", sizeof("symbolToName") - 1);
		if (slot.raw() == NULL || !slot.isArray()) {
			return false;
		}
		HashTable *ht = slot.asArrayTable();
		int size = (int) zend_hash_num_elements(ht);
		t->symbolToName = (zend_string **) malloc(sizeof(zend_string *) * (size_t) size);
		t->symbolToNameSize = size;
		for (int i = 0; i < size; i++) {
			t->symbolToName[i] = NULL;
		}
		zend_ulong idx;
		zval *v;
		ZEND_HASH_FOREACH_NUM_KEY_VAL(ht, idx, v) {
			if ((int) idx < size && Z_TYPE_P(v) == IS_STRING) {
				t->symbolToName[idx] = zend_string_dup(Z_STR_P(v), 1);
			}
		} ZEND_HASH_FOREACH_END();
	}

	/* PhpParser\Error CE for emitError */
	{
		const char *rel = "PhpParser\\Error";
		zend_class_entry *errorCe = lookupClassPrefixed(rel, strlen(rel));
		if (errorCe == NULL) {
			return false;
		}
		g_errorCe = errorCe;
	}

	return true;
}

bool ParserEngine::prepareTables(zval *parserObj)
{
	if (!g_tablesReady) {
		if (!extractTables(parserObj)) {
			/* remember the failure by leaving g_tablesReady false; delegate */
			return false;
		}
		g_tablesReady = true;
	}
	return Z_OBJCE_P(parserObj) == g_tables.parserCe;
}

/* }}} */

/* {{{ the LALR loop (ParserAbstract::doParse) */

#define PN_SYMBOL_NONE (-1)

void ParserEngine::growStacks(int needed)
{
	if (needed < stackCap) {
		return;
	}
	int newCap = stackCap < 8 ? 16 : stackCap * 2;
	while (newCap <= needed) {
		newCap *= 2;
	}
	semStack = (zval *) erealloc(semStack, sizeof(zval) * (size_t) newCap);
	stateStack = (int *) erealloc(stateStack, sizeof(int) * (size_t) newCap);
	tokenStartStack = (int *) erealloc(tokenStartStack, sizeof(int) * (size_t) newCap);
	tokenEndStack = (int *) erealloc(tokenEndStack, sizeof(int) * (size_t) newCap);
	for (int i = stackCap; i < newCap; i++) {
		ZVAL_UNDEF(&semStack[i]);
		stateStack[i] = 0;
		tokenStartStack[i] = 0;
		tokenEndStack[i] = 0;
	}
	stackCap = newCap;
}

void ParserEngine::writeSlot(int pos, zval owned)
{
	growStacks(pos);
	zval *slot = &semStack[pos];
	if (!Z_ISUNDEF_P(slot)) {
		zval_ptr_dtor(slot);
	}
	ZVAL_COPY_VALUE(slot, &owned);
}

zend_string *ParserEngine::getErrorMessage(int symbol, int state)
{
	const Tables *t = tables;
	smart_str msg = {};
	smart_str_appends(&msg, "Syntax error, unexpected ");
	if (symbol >= 0 && symbol < t->symbolToNameSize && t->symbolToName[symbol] != NULL) {
		smart_str_append(&msg, t->symbolToName[symbol]);
	}

	/* expected tokens (capped at 4, else omitted) */
	zend_string *expected[4];
	int numExpected = 0;
	bool tooMany = false;
	int base = t->actionBase[state];
	for (int sym = 0; sym < t->symbolToNameSize; sym++) {
		int idx = base + sym;
		bool found = (idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == sym);
		if (!found && state < t->YY2TBLSTATE) {
			idx = t->actionBase[state + t->numNonLeafStates] + sym;
			found = (idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == sym);
		}
		if (!found) {
			continue;
		}
		if (t->action[idx] != t->unexpectedTokenRule && t->action[idx] != t->defaultAction && sym != t->errorSymbol) {
			if (numExpected == 4) {
				tooMany = true;
				break;
			}
			expected[numExpected++] = t->symbolToName[sym];
		}
	}
	if (!tooMany && numExpected > 0) {
		smart_str_appends(&msg, ", expecting ");
		for (int i = 0; i < numExpected; i++) {
			if (i > 0) {
				smart_str_appends(&msg, " or ");
			}
			if (expected[i] != NULL) {
				smart_str_append(&msg, expected[i]);
			}
		}
	}
	smart_str_0(&msg);
	return msg.s;
}

/* returns the owned result zval (array of stmts) or UNDEF for null */
zv::Val ParserEngine::doParse()
{
	const Tables *t = tables;

	int symbol = PN_SYMBOL_NONE;
	zend_string *tokenText = NULL; /* borrowed from tokens */
	tokenPos = -1;
	errorState = 0;

	int state = 0;
	int stackPos = 0;
	growStacks(8);
	stateStack[0] = 0;
	tokenEndStack[0] = 0;

	for (;;) {
		int rule;
		if (t->actionBase[state] == 0) {
			rule = t->actionDefault[state];
		} else {
			if (symbol == PN_SYMBOL_NONE) {
				int tokenId;
				do {
					tokenPos++;
					tokenId = tokens[tokenPos].id;
					/* negative ids are php-parser compat tokens (the
					 * emulative lexer polyfills newer-PHP tokens on an
					 * older host) — never dropped; bound by dropTokensSize,
					 * not phpTokenToSymbolSize: T_BAD_CHARACTER (id 411 on
					 * 8.5) sits above the grammar's symbol map and must
					 * still be dropped */
				} while (tokenId >= 0 && tokenId < t->dropTokensSize && t->dropTokens[tokenId]);

				tokenText = tokens[tokenPos].text;
				int mapIdx = tokenId + t->phpTokenToSymbolBias;
				if (mapIdx < 0 || mapIdx >= t->phpTokenToSymbolSize || t->phpTokenToSymbol[mapIdx] < 0) {
					zend_throw_exception_ex(spl_ce_RangeException, 0,
						"The lexer returned an invalid token (id=%d, value=%s)",
						tokenId, ZSTR_VAL(tokenText));
					return zv::Val();
				}
				symbol = t->phpTokenToSymbol[mapIdx];
			}

			int idx = t->actionBase[state] + symbol;
			int action = 0;
			bool haveAction = false;
			if ((idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == symbol)) {
				haveAction = true;
			} else if (state < t->YY2TBLSTATE) {
				idx = t->actionBase[state + t->numNonLeafStates] + symbol;
				if (idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == symbol) {
					haveAction = true;
				}
			}
			if (haveAction) {
				action = t->action[idx];
			}
			if (haveAction && action != t->defaultAction) {
				if (action > 0) {
					/* shift */
					++stackPos;
					growStacks(stackPos);
					state = action;
					stateStack[stackPos] = state;
					zval tokZv;
					ZVAL_STR_COPY(&tokZv, tokenText);
					writeSlot(stackPos, tokZv);
					tokenStartStack[stackPos] = tokenPos;
					tokenEndStack[stackPos] = tokenPos;
					symbol = PN_SYMBOL_NONE;

					if (errorState != 0) {
						--errorState;
					}

					if (action < t->numNonLeafStates) {
						continue;
					}
					rule = action - t->numNonLeafStates;
				} else {
					rule = -action;
				}
			} else {
				rule = t->actionDefault[state];
			}
		}

		for (;;) {
			if (rule == 0) {
				/* accept */
				return zv::Val::copyOf(semValue.ref());
			}
			if (rule != t->unexpectedTokenRule) {
				/* reduce */
				int ruleLength = t->ruleToLength[rule];
				bool handled = reduce(rule, stackPos);
				if (!handled && ruleLength > 0) {
					semValue = zv::Ref(&semStack[stackPos - ruleLength + 1]);
				}
				if (aborted) {
					if (!abortErrorMsg.isUndef()) {
						/* mirror of the PHP catch (Error $e) block */
						zval attrs;
						if (abortErrorAttrs.isUndef() || Z_TYPE_P(abortErrorAttrs.raw()) != IS_ARRAY) {
							array_init(&attrs);
							abortErrorAttrs.release();
						} else {
							attrs = abortErrorAttrs.take();
						}
						if (zend_hash_find(Z_ARRVAL(attrs), g_key_startLine) == NULL) {
							zval line;
							ZVAL_LONG(&line, tokens[tokenPos].line);
							SEPARATE_ARRAY(&attrs);
							zend_hash_add(Z_ARRVAL(attrs), g_key_startLine, &line);
						}
						aborted = false;
						zend_string *msg = zend_string_copy(Z_STR_P(abortErrorMsg.raw()));
						abortErrorMsg.release();
						emitError(msg, zv::Val::adopt(attrs));
						zend_string_release(msg);
					}
					return zv::Val();
				}
				if (EG(exception) != NULL) {
					return zv::Val();
				}

				/* goto - shift nonterminal */
				int lastTokenEnd = tokenEndStack[stackPos];
				stackPos -= ruleLength;
				int nonTerminal = t->ruleToNonTerminal[rule];
				int idx = t->gotoBase[nonTerminal] + stateStack[stackPos];
				if (idx >= 0 && idx < t->gotoTableSize && t->gotoCheck[idx] == nonTerminal) {
					state = t->gotoTable[idx];
				} else {
					state = t->gotoDefault[nonTerminal];
				}

				++stackPos;
				growStacks(stackPos);
				stateStack[stackPos] = state;
				zval semCopy;
				ZVAL_COPY(&semCopy, semValue.raw());
				writeSlot(stackPos, semCopy);
				tokenEndStack[stackPos] = lastTokenEnd;
				if (ruleLength == 0) {
					tokenStartStack[stackPos] = tokenPos;
				}
			} else {
				/* error */
				bool discardSymbol = false;
				switch (errorState) {
					case 0: {
						zend_string *msg = getErrorMessage(symbol, state);
						emitError(msg, getAttributesForToken(tokenPos));
						zend_string_release(msg);
						if (aborted || EG(exception) != NULL) {
							return zv::Val();
						}
					}
					ZEND_FALLTHROUGH;
					case 1:
					case 2: {
						errorState = 3;

						for (;;) {
							int idx = t->actionBase[state] + t->errorSymbol;
							bool found = (idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == t->errorSymbol);
							if (!found && state < t->YY2TBLSTATE) {
								idx = t->actionBase[state + t->numNonLeafStates] + t->errorSymbol;
								found = (idx >= 0 && idx < t->actionTableSize && t->actionCheck[idx] == t->errorSymbol);
							}
							int action = found ? t->action[idx] : t->defaultAction;
							if (found && action != t->defaultAction) {
								/* uncovered an error-expecting state */
								++stackPos;
								growStacks(stackPos);
								state = action;
								stateStack[stackPos] = state;
								tokenStartStack[stackPos] = tokenPos;
								tokenEndStack[stackPos] = tokenEndStack[stackPos - 1];
								break;
							}
							if (stackPos <= 0) {
								return zv::Val();
							}
							state = stateStack[--stackPos];
						}
						break;
					}
					case 3:
						if (symbol == 0) {
							return zv::Val();
						}
						symbol = PN_SYMBOL_NONE;
						discardSymbol = true;
						break;
				}
				if (discardSymbol) {
					break; /* break 2 in PHP: leave the inner loop */
				}
			}

			if (state < t->numNonLeafStates) {
				break;
			}
			rule = state - t->numNonLeafStates;
		}
	}
}

/* }}} */

/* {{{ comment annotation (CommentAnnotatingVisitor port) */

static int countNewlines(zend_string *s)
{
	int n = 0;
	const char *p = ZSTR_VAL(s);
	const char *end = p + ZSTR_LEN(s);
	while ((p = (const char *) memchr(p, '\n', (size_t) (end - p))) != NULL) {
		n++;
		p++;
	}
	return n;
}

zv::Val ParserEngine::makeComment(const Token *tok, int tokenPos)
{
	bool isDoc = tok->id == T_DOC_COMMENT;
	NodeClassInfo *cls = resolveNodeClass(isDoc ? "Comment\\Doc" : "Comment", true);
	zval comment;
	object_init_ex(&comment, cls->ce);
	zval args[7];
	ZVAL_STR_COPY(&args[0], tok->text);
	ZVAL_LONG(&args[1], tok->line);
	ZVAL_LONG(&args[2], tok->pos);
	ZVAL_LONG(&args[3], tokenPos);
	ZVAL_LONG(&args[4], tok->line + countNewlines(tok->text));
	ZVAL_LONG(&args[5], tok->pos + (int) ZSTR_LEN(tok->text) - 1);
	ZVAL_LONG(&args[6], tokenPos);
	zend_call_known_function(cls->ce->constructor, Z_OBJ(comment), cls->ce, NULL, 7, args, NULL);
	zval_ptr_dtor(&args[0]);
	return zv::Val::adopt(comment);
}

/* returns false to skip children, sets st.stopped to end the traversal */
bool ParserEngine::commentEnterNode(CommentState &st, zend_object *node)
{
	if (st.index >= st.count) {
		st.stopped = true;
		return false;
	}
	int nextCommentPos = st.positions[st.index];

	pt_node_class_info *info = pt_node_class_info_for_object(node);
	if (info == NULL || info->attributes_offset < 0) {
		return true;
	}
	zval *attrs = OBJ_PROP(node, (uint32_t) info->attributes_offset);
	ZVAL_DEINDIRECT(attrs);
	if (Z_TYPE_P(attrs) != IS_ARRAY) {
		return true;
	}
	zval *startPosZv = zend_hash_find(Z_ARRVAL_P(attrs), g_key_startTokenPos);
	int pos = (startPosZv != NULL && Z_TYPE_P(startPosZv) == IS_LONG) ? (int) Z_LVAL_P(startPosZv) : -1;

	int oldPos = st.pos;
	st.pos = pos;
	if (nextCommentPos > oldPos && nextCommentPos < pos) {
		zval comments;
		array_init(&comments);
		int scanPos = pos;
		int collected = 0;
		while (--scanPos >= oldPos) {
			const Token *tok = &tokens[scanPos];
			if (tok->id == T_DOC_COMMENT || tok->id == T_COMMENT) {
				zval comment = makeComment(tok, scanPos).take();
				zend_hash_next_index_insert(Z_ARRVAL(comments), &comment);
				collected++;
				continue;
			}
			if (tok->id != T_WHITESPACE) {
				break;
			}
		}
		if (collected > 0) {
			/* array_reverse */
			zval reversed;
			array_init_size(&reversed, (uint32_t) collected);
			for (int i = collected - 1; i >= 0; i--) {
				zval *item = zend_hash_index_find(Z_ARRVAL(comments), (zend_ulong) i);
				Z_TRY_ADDREF_P(item);
				zend_hash_next_index_insert(Z_ARRVAL(reversed), item);
			}
			SEPARATE_ARRAY(attrs);
			zend_hash_update(Z_ARRVAL_P(attrs), g_key_comments, &reversed);
		}
		zval_ptr_dtor(&comments);

		do {
			st.index++;
		} while (st.index < st.count && st.positions[st.index] < st.pos);
		if (st.index >= st.count) {
			/* current() returning false is only detected on the NEXT enterNode in PHP */
			nextCommentPos = -1;
		} else {
			nextCommentPos = st.positions[st.index];
		}
	}

	zval *endPosZv = zend_hash_find(Z_ARRVAL_P(attrs), g_key_endTokenPos);
	int endPos = (endPosZv != NULL && Z_TYPE_P(endPosZv) == IS_LONG) ? (int) Z_LVAL_P(endPosZv) : -1;
	if (nextCommentPos > endPos) {
		st.pos = endPos;
		return false; /* DONT_TRAVERSE_CHILDREN */
	}
	return true;
}

void ParserEngine::commentWalkNode(CommentState &st, zend_object *node)
{
	if (st.stopped) {
		return;
	}
	if (!commentEnterNode(st, node)) {
		return;
	}
	pt_node_class_info *info = pt_node_class_info_for_object(node);
	if (info == NULL || !PT_HAS_SUBNODES(info)) {
		return;
	}
	for (uint32_t i = 0; i < info->subnode_count; i++) {
		if (st.stopped) {
			return;
		}
		zval *sub = OBJ_PROP(node, info->subnode_offsets[i]);
		ZVAL_DEINDIRECT(sub);
		if (Z_TYPE_P(sub) == IS_OBJECT) {
			commentWalkNode(st, Z_OBJ_P(sub));
		} else if (Z_TYPE_P(sub) == IS_ARRAY) {
			commentWalkArray(st, Z_ARRVAL_P(sub));
		}
	}
}

void ParserEngine::commentWalkArray(CommentState &st, HashTable *ht)
{
	zval *item;
	ZEND_HASH_FOREACH_VAL(ht, item) {
		if (st.stopped) {
			return;
		}
		ZVAL_DEREF(item);
		if (Z_TYPE_P(item) == IS_OBJECT) {
			commentWalkNode(st, Z_OBJ_P(item));
		} else if (Z_TYPE_P(item) == IS_ARRAY) {
			commentWalkArray(st, Z_ARRVAL_P(item));
		}
	} ZEND_HASH_FOREACH_END();
}

void ParserEngine::annotateComments(zv::Ref stmts)
{
	int numComments = 0;
	for (int i = 0; i < numTokens; i++) {
		if (tokens[i].id == T_COMMENT || tokens[i].id == T_DOC_COMMENT) {
			numComments++;
		}
	}
	if (numComments == 0) {
		return;
	}
	int *positions = (int *) emalloc(sizeof(int) * (size_t) numComments);
	int n = 0;
	for (int i = 0; i < numTokens; i++) {
		if (tokens[i].id == T_COMMENT || tokens[i].id == T_DOC_COMMENT) {
			positions[n++] = i;
		}
	}

	CommentState st;
	st.positions = positions;
	st.count = numComments;
	st.index = 0;
	st.pos = 0;
	st.stopped = false;
	commentWalkArray(st, Z_ARRVAL_P(stmts.raw()));
	efree(positions);
}

/* }}} */

/* {{{ parse entry */

/* the post-parse check over arrays that kept empty-element placeholders */
void ParserEngine::checkCreatedArrays()
{
	zval *arrayNode;
	ZEND_HASH_FOREACH_VAL(&createdArrays, arrayNode) {
		zv::Ref items = prop(zv::Ref(arrayNode), "items");
		if (items.raw() == NULL || !items.isArray()) {
			continue;
		}
		zval *item;
		ZEND_HASH_FOREACH_VAL(items.asArrayTable(), item) {
			ZVAL_DEREF(item);
			if (Z_TYPE_P(item) != IS_OBJECT) {
				continue;
			}
			zv::Ref value = prop(zv::Ref(item), "value");
			if (value.raw() != NULL && value.isObject() && isInstanceOf(value, "Expr\\Error")) {
				emitError("Cannot use empty array elements in arrays", getNodeAttributes(zv::Ref(item)));
			}
		} ZEND_HASH_FOREACH_END();
	} ZEND_HASH_FOREACH_END();
}

/* copies the PHP Token objects into the dense C token array */
bool ParserEngine::buildTokens()
{
	HashTable *ht = Z_ARRVAL_P(tokensZv);
	int num = (int) zend_hash_num_elements(ht);
	tokens = (Token *) emalloc(sizeof(Token) * (size_t) (num > 0 ? num : 1));
	numTokens = num;
	int i = 0;
	zval *tokZv;
	ZEND_HASH_FOREACH_VAL(ht, tokZv) {
		if (Z_TYPE_P(tokZv) != IS_OBJECT || i >= num) {
			return false;
		}
		zv::ObjRef tok(tokZv);
		zv::Ref idZv = tok.prop("id", sizeof("id") - 1);
		zv::Ref textZv = tok.prop("text", sizeof("text") - 1);
		zv::Ref lineZv = tok.prop("line", sizeof("line") - 1);
		zv::Ref posZv = tok.prop("pos", sizeof("pos") - 1);
		if (idZv.raw() == NULL || textZv.raw() == NULL || lineZv.raw() == NULL || posZv.raw() == NULL
			|| !idZv.isLong() || !textZv.isString() || !lineZv.isLong() || !posZv.isLong()) {
			return false;
		}
		tokens[i].id = (int) idZv.asLong();
		tokens[i].text = textZv.asString();
		tokens[i].line = (int) lineZv.asLong();
		tokens[i].pos = (int) posZv.asLong();
		i++;
	} ZEND_HASH_FOREACH_END();
	return true;
}

/* mirrors ParserAbstract::parse(); returns false when the caller must
 * delegate to the PHP implementation */
bool ParserEngine::parse(zval *code, zval *return_value)
{
	/* tokenize via the parser's own lexer (one boundary crossing) */
	zv::Ref lexer = zv::ObjRef(parserObj).prop("lexer", sizeof("lexer") - 1);
	if (lexer.raw() == NULL || !lexer.isObject()) {
		return false;
	}
	zend_function *tokenizeFn = pt_find_method(Z_OBJCE_P(lexer.raw()), "tokenize", sizeof("tokenize") - 1);
	if (tokenizeFn == NULL) {
		return false;
	}
	zval tokensLocal;
	zval args[2];
	ZVAL_COPY_VALUE(&args[0], code);
	ZVAL_COPY_VALUE(&args[1], errorHandler);
	zend_call_known_function(tokenizeFn, Z_OBJ_P(lexer.raw()), Z_OBJCE_P(lexer.raw()), &tokensLocal, 2, args, NULL);
	if (EG(exception) != NULL) {
		RETVAL_NULL();
		return true; /* exception propagates, mirroring PHP */
	}
	if (Z_TYPE(tokensLocal) != IS_ARRAY) {
		zval_ptr_dtor(&tokensLocal);
		return false;
	}
	zv::Val tokensOwned = zv::Val::adopt(tokensLocal);
	tokensZv = tokensOwned.raw();

	/* $this->tokens = ... (getTokens() support) */
	propWrite(zv::Ref(parserObj), "tokens", zv::Val::copyOf(tokensOwned.ref()));

	/* phpVersion id */
	{
		zv::Ref phpVersion = zv::ObjRef(parserObj).prop("phpVersion", sizeof("phpVersion") - 1);
		if (phpVersion.raw() != NULL && phpVersion.isObject()) {
			zv::Ref id = prop(phpVersion, "id");
			if (id.raw() != NULL && id.isLong()) {
				phpVersionId = id.asLong();
			}
		}
	}

	if (!buildTokens()) {
		return false;
	}

	initAttributeKeys();
	zv::Val result = doParse();

	if (EG(exception) == NULL) {
		checkCreatedArrays();
	}

	if (!result.isUndef() && EG(exception) == NULL) {
		annotateComments(result.ref());
	}

	if (result.isUndef()) {
		RETVAL_NULL();
	} else {
		result.intoReturnValue(return_value);
	}
	return true;
}

/* }}} */

} // namespace phpstanturbo

using phpstanturbo::ParserEngine;

/* {{{ engine ABI glue: class registration + fallback delegate */

#include "../reg.h"

void pt_register_parser_runner(void)
{
	reg::Class cls("PHPStanTurbo\\ParserRunner");

	cls.method("parse", reg::PublicStatic, 3, { reg::objectArg("parser"), reg::stringArg("sourceCode"), reg::objectArg("errorHandler") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parserObj, *code, *errorHandler;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(parserObj)
			Z_PARAM_ZVAL(code)
			Z_PARAM_OBJECT(errorHandler)
		ZEND_PARSE_PARAMETERS_END();

		if (Z_TYPE_P(code) == IS_STRING && ParserEngine::prepareTables(parserObj)) {
			ParserEngine engine(parserObj, errorHandler);
			if (engine.parse(code, return_value)) {
				return;
			}
		}

		/* fallback: delegate to the PHP implementation */
		zend_function *parseFn = pt_find_method(Z_OBJCE_P(parserObj), "parse", sizeof("parse") - 1);
		if (parseFn == NULL) {
			pt_throw_should_not_happen();
			RETURN_THROWS();
		}
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], code);
		ZVAL_COPY_VALUE(&args[1], errorHandler);
		zend_call_known_function(parseFn, Z_OBJ_P(parserObj), Z_OBJCE_P(parserObj), return_value, 2, args, NULL);
	});

	cls.register_();
}

/* }}} */
