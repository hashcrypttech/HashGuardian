<?php

namespace Hashcrypttech\HashGuardian\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void start()
 * @method static bool isRecording()
 * @method static void startRecording()
 * @method static void stopRecording()
 * @method static string getBatchId()
 * @method static void record(\Hashcrypttech\HashGuardian\IncomingEntry $entry)
 * @method static void store(\Hashcrypttech\HashGuardian\Contracts\EntriesRepository $repository)
 * @method static \Hashcrypttech\HashGuardian\HashGuardian filter(\Closure $callback)
 * @method static \Hashcrypttech\HashGuardian\HashGuardian afterStoring(\Closure $callback)
 * @method static \Hashcrypttech\HashGuardian\HashGuardian sample(float $rate)
 * @method static \Hashcrypttech\HashGuardian\HashGuardian auth(\Closure $callback)
 * @method static bool check($request)
 * @method static void count(string $name, int $increment = 1, array $metadata = [])
 * @method static void metric(string $name, float $value, array $metadata = [])
 * @method static void startTimer(string $name)
 * @method static ?float stopTimer(string $name, array $metadata = [])
 *
 * @see \Hashcrypttech\HashGuardian\HashGuardian
 */
class HashGuardian extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hashcrypttech\HashGuardian\HashGuardian::class;
    }
}
