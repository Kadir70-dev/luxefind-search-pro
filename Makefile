.PHONY: lint test browser package
lint:
	@find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
	@node --check assets/search.js && node --check assets/editor.js

test: lint
	@php tests/acceptance.php
	@php tests/fuzz.php

browser:
	@php tests/render-preview.php > tests/preview.html
	@python tests/ui-test.py

package: test
	@python tools/build_release.py
