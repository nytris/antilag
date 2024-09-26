<?php

/*
 * Nytris Antilag
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/antilag/
 *
 * Released under the MIT license.
 * https://github.com/nytris/antilag/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Antilag;

use Asmblah\PhpCodeShift\Shifter\Stream\Native\StreamWrapper as ShiftStreamWrapper;
use LogicException;
use RuntimeException;

/**
 * Class StreamWrapper.
 *
 * A stream wrapper that records filesystem stats during early Nytris boot.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamWrapper
{
    public const PROTOCOL = 'file';
    /**
     * @var resource|null
     */
    public $context = null;

    private ?string $path = null;
    /**
     * @var resource|null
     */
    private $wrappedResource = null;

    public static function register(): void
    {
        stream_wrapper_unregister(static::PROTOCOL);
        stream_wrapper_register(static::PROTOCOL, static::class);
    }

    /**
     * @return resource|false
     */
    public function stream_cast(int $cast_as)
    {
        return false;
    }

    public function stream_close(): void
    {
        if (!$this->wrappedResource) {
            return;
        }

        fclose($this->wrappedResource);

        $this->path = null;
        $this->wrappedResource = null;
    }

    public function stream_eof(): bool
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return feof($this->wrappedResource);
    }

    public function stream_flush(): bool
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return fflush($this->wrappedResource);
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$openedPath
    ): bool {
        $useIncludePath = (bool) ($options & STREAM_USE_PATH);
        $stream = $this->unwrapped(fn () => fopen($path, $mode, use_include_path: $useIncludePath));

        if ($stream === false) {
            return false;
        }

        $this->path = $path;
        $this->wrappedResource = $stream;

        if ($useIncludePath) {
            $metaData = stream_get_meta_data($stream);

            $openedPath = $metaData['uri'];
        }

        return true;
    }

    public function stream_read(int $count): string|false
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return fread($this->wrappedResource, $count);
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return fseek($this->wrappedResource, $offset, $whence) !== -1;
    }

    public function stream_set_option(int $option, int $arg1, int|null $arg2): bool
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return $this->unwrapped(
            fn () => match ($option) {
                STREAM_OPTION_BLOCKING => stream_set_blocking($this->wrappedResource, (bool)$arg1),
                STREAM_OPTION_READ_TIMEOUT => stream_set_timeout($this->wrappedResource, $arg1, $arg2),
                STREAM_OPTION_WRITE_BUFFER => stream_set_write_buffer($this->wrappedResource, $arg1) === 0,
                STREAM_OPTION_READ_BUFFER => stream_set_read_buffer($this->wrappedResource, $arg1) === 0,
                default => false,
            }
        );
    }

    /**
     * Retrieves information about an open file resource.
     *
     * @see {@link https://www.php.net/manual/en/streamwrapper.stream-stat.php}
     *
     * @return array<mixed>|false
     */
    public function stream_stat(): array|false
    {
        if (!$this->wrappedResource) {
            return false;
        }

        $stat = Antilag::getCachedStat($this->path);

        if ($stat !== null) {
            return $stat;
        }

        $stat = fstat($this->wrappedResource);

        Antilag::cacheStat($this->path, $stat);

        return $stat;
    }

    public function stream_tell(): int|false
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return ftell($this->wrappedResource);
    }

    public function stream_truncate(int $newSize): bool
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return ftruncate($this->wrappedResource, $newSize);
    }

    public function stream_write(string $data): int|false
    {
        if (!$this->wrappedResource) {
            return false;
        }

        return fwrite($this->wrappedResource, $data);
    }

    public function unlink(string $path): bool
    {
        return $this->unwrapped(fn () => unlink($path));
    }

    public static function unregister(): void
    {
        @stream_wrapper_restore(static::PROTOCOL);
    }

    /**
     * Disables the stream wrapper while the given callback is executed,
     * allowing the native file:// protocol stream wrapper to be used for actual filesystem access.
     */
    public function unwrapped(callable $callback): mixed
    {
        static::unregister();

        try {
            return $callback();
        } finally {
            // Note that if we do not unregister again first following the above restore,
            // a segfault will be raised.
            static::register();
        }
    }

    /**
     * Retrieves information about a file from its path.
     *
     * @see {@link https://www.php.net/manual/en/streamwrapper.url-stat.php}
     *
     * @return array<mixed>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        $stat = Antilag::getCachedStat($path);

        if ($stat !== null) {
            return $stat;
        }

        $link = (bool)($flags & STREAM_URL_STAT_LINK);
        $quiet = (bool)($flags & STREAM_URL_STAT_QUIET);

        /*
         * This additional call to file_exists(...) should not cause an additional native filesystem stat,
         * due to PHP's stat cache, which keeps the most recent file status,
         * and so will be reused below by stat(...)/lstat(...) if the file does exist.
         *
         * This prevents the (l)stat call from raising a warning whose suppression below
         * is then potentially overridden by a custom error handler.
         */
        if ($quiet && !$this->unwrapped(static fn () => file_exists($path))) {
            return false;
        }

        // Use lstat(...) for links but stat() for other files.
        $doStat = static function () use ($link, $path) {
            try {
                return $link ?
                    lstat($path) :
                    stat($path);
            } catch (RuntimeException) {
                /*
                 * Stream wrapper must have been invoked by SplFileInfo::__construct(),
                 * which raises RuntimeExceptions in place of warnings
                 * such as `RuntimeException: stat(): stat failed for .../non_existent.txt`.
                 */
                return false;
            }
        };

        // Suppress warnings/notices if quiet flag is set.
        $stat = $this->unwrapped(
            $quiet ?
                static fn () => @$doStat() :
                $doStat
        );

        Antilag::cacheStat($path, $stat);

        return $stat;
    }
}

interface StorageInterface
{
    /**
     * Fetches the stat cache from the backing store, if it has been stored yet.
     *
     * @return array<mixed>|null
     */
    public function fetchStatCache(): ?array;

    public function isSupported(): bool;

    /**
     * Stores a new stat cache to the backing store.
     *
     * @param array<mixed> $statCache
     */
    public function saveStatCache(array $statCache): void;
}

class ApcuStorage implements StorageInterface
{
    public function __construct(
        private readonly string $apcuNamespace = 'nytris.antilag.stat'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function fetchStatCache(): ?array
    {
        $statCache = apcu_fetch($this->apcuNamespace, success: $success);

        return $success ? $statCache : null;
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    /**
     * @inheritDoc
     */
    public function saveStatCache(array $statCache): void
    {
        if (apcu_store($this->apcuNamespace, $statCache) === false) {
            trigger_error('Failed to save Nytris Antilag cache in APCu', E_USER_ERROR);
        }
    }
}

/**
 * Class Antilag.
 *
 * Initial entrypoint for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Antilag
{
    private static bool $isOn = false;
    /**
     * @var array<string, array<mixed>|false>
     */
    private static array $statCache = [];
    private static bool $statCacheIsDirty = false;
    private static StorageInterface $storage;

    /**
     * Adds a path to the stat cache.
     *
     * @param string $path
     * @param array<mixed>|false $stat
     */
    public static function cacheStat(string $path, array|false $stat): void
    {
        self::$statCache[$path] = $stat;
        self::$statCacheIsDirty = true;
    }

    /**
     * Fetches a stat from the stat cache.
     *
     * @param string $path
     * @return array<mixed>|false|null Returns array on success, false when cached as non-accessible or null on miss.
     */
    public static function getCachedStat(string $path): array|false|null
    {
        return self::$statCache[$path] ?? null;
    }

    /**
     * Fetches the in-memory contents of the stat cache.
     *
     * @return array<string, array<mixed>|false>
     */
    public static function getStatCache(): array
    {
        return self::$statCache;
    }

    /**
     * Fetches whether antilag is currently on.
     */
    public static function isOn(): bool
    {
        return self::$isOn;
    }

    /**
     * Turns on antilag.
     */
    public static function stage1(
        StorageInterface $storage = new ApcuStorage()
    ): void {
        if (!$storage->isSupported()) {
            return;
        }

        self::$storage = $storage;

        if (self::$isOn) {
            throw new LogicException('Nytris Antilag already turned on');
        }

        $statCache = self::$storage->fetchStatCache();

        if ($statCache !== null) {
            self::$statCache = $statCache;
        }

        StreamWrapper::register();

        self::$isOn = true;
    }

    /**
     * Turns off antilag.
     */
    public static function stage3(): void
    {
        if (!self::$isOn) {
            return;
        }

        if (!ShiftStreamWrapper::isRegistered()) {
            @stream_wrapper_restore('file');
        }

        if (self::$statCacheIsDirty) {
            self::$storage->saveStatCache(self::$statCache);
            self::$statCacheIsDirty = false;
        }

        self::$statCache = [];

        self::$isOn = false;
    }
}
