# PHPStan file type cache and large import maps

A standalone reproducer for memory used by a class with many import statements and PHPDoc blocks. Both generated classes have 1150 identical methods. One also has 572 `use` statements that point back into its own namespace. They are redundant, which makes it possible to remove them without changing class behavior.

## Reproduce

Requirements: PHP 8.2 or newer and Composer.

```sh
composer install
php run.php
```

`generate_fixture.php` creates the two classes and their PHPStan configs. `run.php` clears its local cache and analyses each variant twice. `--debug` disables the result cache; the second run reuses the file type cache. No application code is needed.

On macOS with PHP 8.5.10 and PHPStan `2.3.x-dev@9b5c7d6`, this repository's benchmark costs **35.94 MB cold and 131.94 MB warm** with imports, versus **10 MB cold and 4 MB warm** without. These are increases in the PHP allocator peak (`memory_get_peak_usage(true)`) for the analysed file, not RSS (the resident set size that tools like `top` show). RSS also counts the PHP binary, loaded extensions and memory that PHP has not given back to the OS, so it is usually higher.

`FileTypeMapper` stores a name scope for each doc block. Each scope appears to carry the full import map into the cache representation. A shared or deduplicated map could reduce the memory and cache size for classes with many documented members. GitHub Actions runs the comparison on every push.
