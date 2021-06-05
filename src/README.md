# Development notes

To ensure consistent style and code quality we leverage `php-cs-fixer`, `PHPUnit` and `PHPStan`.

This is automatically managed through `grumphp` in development, wit the git hooks should automatically register themselves.

If this is not the case, run:

```bash
./src/vendor/bin/grumphp git:init
```

It will run on each commit making sure to warn you when something is wrong.  Make sure to have PHP >= 7.4 installed.

To run manually, from the base repository directory, run: 

```bash
./src/vendor/bin/grumphp run
```


