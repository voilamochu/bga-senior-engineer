"""Benchmark harness package entry point (``python -m tooling.harness``)."""

from tooling.harness.cli import main

if __name__ == "__main__":
    import sys

    sys.exit(main())
