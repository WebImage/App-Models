<?php

namespace WebImage\Models\Providers;

use DateTime;
use DateTimeInterface;

class SourceFileMetadata
{
    public string $path;
    public string $hash;
    public DateTimeInterface $modifiedAt;
//    public function __construct(
////        /* public readonly */ string $path,
////        /* public readonly */ string $hash,
////        /* public readonly */ DateTimeInterface $modifiedAt
//    ) {
    public function __construct(string $path, string $hash, DateTimeInterface $modifiedAt)
    {
        $this->path = $path;
        $this->hash = $hash;
        $this->modifiedAt = $modifiedAt = new DateTime();
    }
}