"""SHA-256 hashing helpers (harness §6: evidence hashing, prompt-bundle hash)."""

from __future__ import annotations

import hashlib
from pathlib import Path

# sha256 of the empty byte string; used as a known-vector check in tests.
KNOWN_EMPTY_SHA256 = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"


def sha256_bytes(data: bytes) -> str:
    """Hex SHA-256 of *data*."""
    return hashlib.sha256(data).hexdigest()


def sha256_text(text: str) -> str:
    """Hex SHA-256 of *text* encoded as UTF-8."""
    return sha256_bytes(text.encode("utf-8"))


def sha256_file(path: str | Path) -> str:
    """Hex SHA-256 of a file's contents, streamed in fixed-size chunks."""
    digest = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()
