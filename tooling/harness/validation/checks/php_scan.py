"""Basic PHP source scanning for the NOT-02 and V9 checks (MS-06).

The checks consume the submission's changed ``Game.php`` (the frozen
diff bundle) and need three mechanical extractions:

- method definitions (brace-balanced method bodies),
- notification dispatch calls (``notifyAllPlayers``/``notifyPlayer``
  and the modern ``notify->all``/``notify->player`` API) with their
  payload arrays,
- payload keys of a notification payload.

The scanner is deliberately *basic* (MVB-017: "basic V9 hidden-info
scan"): it handles single- and double-quoted string literals and ``//``,
``#``, and ``/* */`` comments, and masks their contents so structural
scanning never miscounts braces inside strings.  Exotic PHP literals
(heredocs, nowdocs, escaped-quote type strings) are out of scope; the
fixtures and the reference codebase's call sites use plain literals.

Scanning is deterministic: identical input text produces identical
results.
"""

from __future__ import annotations

import re

_NOTIFY_CALL = re.compile(
    r"\b(?:notifyAllPlayers|notifyPlayer|notify\s*->\s*(?:all|player))\s*\("
)
_FUNCTION_DEF = re.compile(r"\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(")
_PAYLOAD_KEY = re.compile(r"""['"]([A-Za-z_][A-Za-z0-9_]*)['"]\s*=>""")
_VARIABLE = re.compile(r"\$[A-Za-z_][A-Za-z0-9_]*")
_WHITESPACE = re.compile(r"\s+")


def mask_text(text: str) -> str:
    """Return *text* with comments and string contents replaced by spaces.

    Quote characters are preserved (so string positions remain
    discoverable) and newlines are preserved (so line numbers in the
    masked text match the original).  Backslash escapes inside strings
    are consumed without emitting their escaped character.
    """
    chars = list(text)
    i = 0
    n = len(text)
    while i < n:
        c = text[i]
        if c == "'" or c == '"':
            quote = c
            i += 1
            while i < n:
                if text[i] == "\\" and i + 1 < n:
                    chars[i] = " "
                    chars[i + 1] = " "
                    i += 2
                    continue
                if text[i] == quote:
                    i += 1
                    break
                if text[i] != "\n":
                    chars[i] = " "
                i += 1
        elif text.startswith("//", i) or text.startswith("#", i):
            end = text.find("\n", i)
            end = n if end == -1 else end
            for j in range(i, end):
                chars[j] = " "
            i = end
        elif text.startswith("/*", i):
            end = text.find("*/", i + 2)
            end = n if end == -1 else end + 2
            for j in range(i, end):
                if text[j] != "\n":
                    chars[j] = " "
            i = end
        else:
            i += 1
    return "".join(chars)


def strip_comments(text: str) -> str:
    """Return *text* with comments replaced by spaces, strings intact.

    Unlike :func:`mask_text`, string literals are preserved verbatim —
    this form is used for content comparison (payload normalization)
    where key names and literal values are meaningful.
    """
    chars = list(text)
    i = 0
    n = len(text)
    while i < n:
        c = text[i]
        if c == "'" or c == '"':
            i = _skip_string(text, i)
        elif text.startswith("//", i) or text.startswith("#", i):
            end = text.find("\n", i)
            end = n if end == -1 else end
            for j in range(i, end):
                chars[j] = " "
            i = end
        elif text.startswith("/*", i):
            end = text.find("*/", i + 2)
            end = n if end == -1 else end + 2
            for j in range(i, end):
                if text[j] != "\n":
                    chars[j] = " "
            i = end
        else:
            i += 1
    return "".join(chars)


def _skip_string(text: str, i: int) -> int:
    """Position just after the string literal starting at ``text[i]``."""
    quote = text[i]
    i += 1
    while i < len(text):
        if text[i] == "\\" and i + 1 < len(text):
            i += 2
            continue
        if text[i] == quote:
            return i + 1
        i += 1
    return len(text)


def _balanced_span(text: str, start: int, open_char: str, close_char: str) -> int | None:
    """Position of the closing bracket matching ``text[start]`` (or None).

    Nested ``()``, ``[]``, and ``{}`` are counted.  *text* must already
    be masked so string/comment contents do not contain bracket chars.
    """
    pairs = {"(": ")", "[": "]", "{": "}"}
    close_of = {")": "(", "]": "[", "}": "{"}
    if text[start] != open_char:
        return None
    depth = 0
    i = start
    n = len(text)
    while i < n:
        c = text[i]
        if c in pairs:
            depth += 1
        elif c in close_of:
            depth -= 1
            if depth == 0:
                return i
        i += 1
    return None


def iter_methods(text: str) -> list[dict]:
    """Extract method definitions from PHP class code.

    Returns a list of ``{"name", "start_line", "body", "body_start",
    "body_end"}`` where *body* is the original (unmasked) method body
    without the outer braces and ``body_start``/``body_end`` are the
    absolute character offsets of the body (the content between the
    braces).  Methods are returned in source order.
    """
    masked = mask_text(text)
    methods: list[dict] = []
    for match in _FUNCTION_DEF.finditer(masked):
        open_idx = match.start() + match.group(0).rfind("(")
        close_idx = _balanced_span(masked, open_idx, "(", ")")
        if close_idx is None:
            continue
        brace_idx = masked.find("{", close_idx)
        if brace_idx == -1:
            continue
        end_idx = _balanced_span(masked, brace_idx, "{", "}")
        if end_idx is None:
            continue
        methods.append(
            {
                "name": match.group(1),
                "start_line": text.count("\n", 0, match.start()) + 1,
                "body": text[brace_idx + 1 : end_idx],
                "body_start": brace_idx + 1,
                "body_end": end_idx,
            }
        )
    return methods


def _payload_span(masked: str, after_quote: int) -> tuple[int, int] | None:
    """Balanced span of the payload array of a notify call.

    Scans *masked* from just after the notification type string for the
    payload expression: a ``[...]`` array or an ``array(...)`` call
    (whitespace-insensitive), and returns ``(start, end)`` of the
    payload including its delimiters, or None when no payload follows.
    """
    search = masked[after_quote:]
    bracket = re.search(r"\[", search)
    array_call = re.search(r"\barray\s*\(", search)
    candidates = []
    if bracket is not None:
        candidates.append((bracket.start() + after_quote, "[", "]"))
    if array_call is not None:
        candidates.append((array_call.end() - 1 + after_quote, "(", ")"))
    if not candidates:
        return None
    start, open_char, close_char = min(candidates, key=lambda c: c[0])
    end = _balanced_span(masked, start, open_char, close_char)
    if end is None:
        return None
    return start, end


def notify_calls(text: str) -> list[dict]:
    """Extract notification dispatch calls from PHP code.

    Returns a list of ``{"type", "message", "payload", "line", "method",
    "api"}`` in source order.  *type* is the first string literal
    argument, *payload* is the original text of the payload array (or
    None), *line* is the 1-based line of the call, and *method* is the
    name of the enclosing method (or None).
    """
    masked = mask_text(text)
    methods = iter_methods(text)
    calls: list[dict] = []
    for match in _NOTIFY_CALL.finditer(masked):
        api = match.group(0).strip().rstrip("(").strip()
        open_quote = masked.find("'", match.end())
        if open_quote == -1:
            continue
        close_quote = masked.find("'", open_quote + 1)
        if close_quote == -1:
            continue
        ntype = text[open_quote + 1 : close_quote]
        position = match.start()
        payload = None
        span = _payload_span(masked, close_quote + 1)
        if span is not None:
            payload = text[span[0] : span[1] + 1]
        method_name = _enclosing_method(methods, position)
        calls.append(
            {
                "type": ntype,
                "message": _extract_message(masked, text, close_quote + 1),
                "payload": payload,
                "line": text.count("\n", 0, position) + 1,
                "method": method_name,
                "api": api,
            }
        )
    return calls


def _extract_message(masked: str, text: str, after: int) -> str:
    """Content of the message argument (2nd) of a notify call.

    Handles a plain string literal and a ``clienttranslate('...')``
    wrapper; returns "" when the argument cannot be located.
    """
    comma = masked.find(",", after)
    if comma == -1:
        return ""
    pos = comma + 1
    while pos < len(masked) and masked[pos] in " \t\r\n":
        pos += 1
    match = re.match(r"clienttranslate\s*\(", masked[pos:])
    if match is not None:
        pos += match.end()
        while pos < len(masked) and masked[pos] in " \t\r\n":
            pos += 1
    if pos >= len(masked) or masked[pos] not in ("'", '"'):
        return ""
    close_quote = masked.find(masked[pos], pos + 1)
    if close_quote == -1:
        return ""
    return text[pos + 1 : close_quote]


def _enclosing_method(methods: list[dict], position: int) -> str | None:
    """Name of the narrowest method whose body contains *position*, or None."""
    candidates = [
        method
        for method in methods
        if method["body_start"] <= position <= method["body_end"]
    ]
    if not candidates:
        return None
    return max(candidates, key=lambda method: method["body_start"])["name"]


def payload_keys(payload_text: str) -> list[str]:
    """Ordered payload keys of a PHP array literal (e.g. ``'key' => v``)."""
    if not payload_text:
        return []
    return [match.group(1) for match in _PAYLOAD_KEY.finditer(payload_text)]


def normalize_payload(payload_text: str) -> str:
    """Normalize a payload for block-similarity comparison.

    Comments are removed, variable references are replaced by a
    placeholder, and whitespace is collapsed; string literals (payload
    keys and literal values) are kept verbatim.  Two payloads are
    considered duplicated construction when their normalized forms are
    identical.
    """
    if not payload_text:
        return ""
    without_comments = strip_comments(payload_text)
    normalized = _VARIABLE.sub("$X", without_comments)
    return _WHITESPACE.sub(" ", normalized).strip()
