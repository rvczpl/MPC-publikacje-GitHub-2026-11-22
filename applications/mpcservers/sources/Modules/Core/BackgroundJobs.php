<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Fixed-size SQL state; single worker and failure backoff independent of Redis. */
class BackgroundJobs
{
    public const JOBS = ['archive','awards','status','cleanup'];
    public static function install(): void
    {
        $db=\IPS\Db::i();
        if (!$db->checkForTable('mpcservers_job_state')) {
            $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'), TRUE);
            $db->createTable($schema['mpcservers_job_state']);
        }
    }
    public static function checkMemory(array $info): void
    {
        if (!isset($info['used_memory'], $info['maxmemory'])) throw new \RuntimeException('Redis memory information unavailable');
        if ((int)$info['maxmemory']>0 && (int)$info['used_memory'] >= (int)$info['maxmemory'] * 0.9) {
            throw new \RuntimeException('Redis memory limit safety threshold');
        }
    }
    public static function checkNativeStore(): void
    {
        if (\IPS\STORE_METHOD !== 'Redis') return;
        $info=\IPS\Redis::i()->connection('write')->info('memory');
        if (!is_array($info)) throw new \RuntimeException('Redis memory information unavailable');
        static::checkMemory($info);
    }
    public static function run(string $key, callable $job, bool $nativeWrites=FALSE): mixed
    {
        if (!in_array($key, self::JOBS, TRUE)) throw new \InvalidArgumentException('Unknown job');
        if (!FeatureHooks::enabled($key)) return NULL;
        $db=\IPS\Db::i();
        if (!$db->checkForTable('mpcservers_job_state')) return NULL;
        $lock='mpcs-job-'.substr(hash('sha256',\IPS\SUITE_UNIQUE_KEY),0,24).'-'.$key;
        $stmt=$db->preparedQuery('SELECT GET_LOCK(?,0)',[$lock]);
        $stmt->bind_result($acquired);$stmt->fetch();$stmt->close();
        if ((int)$acquired!==1) return NULL;
        try {
            $db->insert('mpcservers_job_state',['job'=>$key],FALSE,TRUE);
            $state=$db->select('*','mpcservers_job_state',['job=?',$key])->first();
            if ((int)$state['blocked_until']>time()) return NULL;
            try {
                if ($nativeWrites) static::checkNativeStore();
                $result=$job();
                $db->update('mpcservers_job_state',['failures'=>0,'blocked_until'=>time()+60,'last_error'=>''],['job=?',$key]);
                return $result;
            } catch (\Throwable $e) {
                $failures=min(10,(int)$state['failures']+1);
                // Store only class/code, never credentials, SQL or source URLs.
                $db->update('mpcservers_job_state',[
                    'failures'=>$failures,'blocked_until'=>time()+min(3600,300*(2**($failures-1))),
                    'last_error'=>substr(get_class($e).': '.(int)$e->getCode(),0,190)
                ],['job=?',$key]);
                return NULL;
            }
        } finally {
            $db->preparedQuery('SELECT RELEASE_LOCK(?)',[$lock])->close();
        }
    }
}
