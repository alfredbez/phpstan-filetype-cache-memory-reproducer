<?php

declare(strict_types=1);

if (!is_file(__DIR__ . '/vendor/bin/phpstan')) {
    fwrite(STDERR, "Run composer install first.\n");
    exit(1);
}

require __DIR__ . '/generate_fixture.php';

$cache = __DIR__ . '/cache';
if (is_dir($cache)) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($cache);
}

foreach (['WithImports', 'WithoutImports'] as $class) {
    foreach (['cold', 'warm'] as $temperature) {
        $command = [PHP_BINARY, '-d', 'memory_limit=2G', __DIR__ . '/vendor/bin/phpstan', 'analyse', '-c', "generated/phpstan-$class.neon", '--debug', '-vvv', '--no-progress', "generated/Use$class.php"];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start PHPStan');
        }
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || !preg_match('/--- consumed ([^,]+), total [^,]+, took ([0-9.]+ s)/', $output, $matches)) {
            fwrite(STDERR, $output);
            throw new RuntimeException("PHPStan failed for $class ($temperature, exit $exit)");
        }
        printf("%-14s %-4s consumed %-12s took %s\n", $class, $temperature, $matches[1], $matches[2]);
    }
}
