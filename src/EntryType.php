<?php

namespace Hashcrypttech\HashGuardian;

class EntryType
{
    const REQUEST = 'request';
    const QUERY = 'query';
    const EXCEPTION = 'exception';
    const JOB = 'job';
    const OUTGOING_REQUEST = 'outgoing_request';
    const CACHE = 'cache';
    const MAIL = 'mail';
    const NOTIFICATION = 'notification';
    const COMMAND = 'command';
    const SCHEDULE = 'schedule';
    const LOG = 'log';
    const ACTIVITY = 'activity';
    const METRIC = 'metric';
    const BATCH = 'batch';
    const DUMP = 'dump';
    const EVENT = 'event';
    const GATE = 'gate';
    const MODEL = 'model';
    const REDIS = 'redis';
    const VIEW = 'view';

    public static function all(): array
    {
        return [
            self::REQUEST,
            self::QUERY,
            self::EXCEPTION,
            self::JOB,
            self::OUTGOING_REQUEST,
            self::CACHE,
            self::MAIL,
            self::NOTIFICATION,
            self::COMMAND,
            self::SCHEDULE,
            self::LOG,
            self::ACTIVITY,
            self::METRIC,
            self::BATCH,
            self::DUMP,
            self::EVENT,
            self::GATE,
            self::MODEL,
            self::REDIS,
            self::VIEW,
        ];
    }

    public static function labels(): array
    {
        return [
            self::REQUEST => 'Requests',
            self::QUERY => 'Queries',
            self::EXCEPTION => 'Exceptions',
            self::JOB => 'Jobs',
            self::OUTGOING_REQUEST => 'Outgoing Requests',
            self::CACHE => 'Cache',
            self::MAIL => 'Mail',
            self::NOTIFICATION => 'Notifications',
            self::COMMAND => 'Commands',
            self::SCHEDULE => 'Scheduled Tasks',
            self::LOG => 'Logs',
            self::ACTIVITY => 'Activity',
            self::METRIC => 'Metrics',
            self::BATCH => 'Batches',
            self::DUMP => 'Dumps',
            self::EVENT => 'Events',
            self::GATE => 'Gates',
            self::MODEL => 'Models',
            self::REDIS => 'Redis',
            self::VIEW => 'Views',
        ];
    }
}
