<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Retired in 6.1.8: never creates or updates IPS tags. Existing data is retained. */
class TagQueue
{
    public static function install(): void {}
    public static function enqueue(int $tid): void {}
    public static function run(): string { return 'disabled'; }
}
