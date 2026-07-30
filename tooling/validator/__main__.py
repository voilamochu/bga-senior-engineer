"""
Runtime Specification Validator — package entry point.

Loads the CLI implementation from ``src/``.
"""

from tooling.validator.src.__main__ import main

if __name__ == "__main__":
    import sys
    sys.exit(main())
