<?php

$source = __DIR__.'/../.agents/skills';
$target = __DIR__.'/../../.agents/skills/backend';

if (! is_dir($source)) {
    fwrite(STDERR, "No regenerated skills found at {$source}; nothing to sync".PHP_EOL);

    exit(0);
}

mirror($source, $target);
prune($source, $target);
removeDirectory(dirname($source));

echo 'Synced skills to '.realpath($target).' and removed '.dirname($source).PHP_EOL;

function mirror(string $source, string $target): void
{
    if (! is_dir($target)) {
        mkdir($target, 0777, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $destination = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $iterator->getSubPathName());

        if ($item->isDir()) {
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }

            continue;
        }

        if (! copy($item->getPathname(), $destination)) {
            fwrite(STDERR, "Failed to copy {$item->getPathname()} to {$destination}".PHP_EOL);

            exit(1);
        }
    }
}

function prune(string $source, string $target): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $subPath = str_replace(DIRECTORY_SEPARATOR, '/', $iterator->getSubPathName());
        $origin = $source.'/'.$subPath;

        if ($item->isDir()) {
            if (! is_dir($origin)) {
                @rmdir($item->getPathname());
            }

            continue;
        }

        if (! file_exists($origin)) {
            unlink($item->getPathname());
        }
    }
}

function removeDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());

            continue;
        }

        if (! @unlink($item->getPathname())) {
            fwrite(STDERR, "Could not remove {$item->getPathname()}; boost will refresh it on the next update".PHP_EOL);
        }
    }

    @rmdir($directory);
}
