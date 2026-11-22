<?php
namespace IPS\mpcservers\Modules\Lists;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Community\OwnerAccess;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Highlights\Awards;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Boost
{
    public static function rewardsEnabled($game='minecraft',$tid=0) { return GameRewards::enabled($game,$tid); }
    public static function dailyAllowance($memberId,$now=NULL)
    {
        $day=Rankings::period('day','',$now);$used=array();
        if($memberId){foreach(array('mpcservers_boost_events','mpcservers_votes') as $table){foreach(\IPS\Db::i()->select('tid',$table,array('member_id=? AND created>=? AND created<?',(int)$memberId,$day['start'],$day['end'])) as $tid){$used[(int)$tid]=TRUE;}}}
        return array('used'=>$used,'remaining'=>max(0,5-count($used)),'end'=>$day['end']);
    }
    public static function blockedReason($tid,$memberId,$allowance=NULL)
    {
        if(!\IPS\mpcservers\Modules\Core\Registry::enabled('lists'))return 'Podbijanie jest wyłączone.';
        if(!$memberId)return 'Zaloguj się, aby podbić serwer.';
        $allowance=$allowance??static::dailyAllowance($memberId);
        if(isset($allowance['used'][(int)$tid]))return 'Ten serwer został już przez Ciebie podbity dzisiaj. Kolejny głos następnego dnia.';
        if(!$allowance['remaining'])return 'Wykorzystano dzienny limit 5 różnych serwerów. Limit odnowi się następnego dnia.';
        return '';
    }
    public static function vote($tid,$member,$player='')
    {
        if (!$member->member_id) { throw new \DomainException('Zaloguj się, aby podbić serwer.'); }
        $topic=Presentation::topic($tid);
        if ($topic->state==='closed') { throw new \DomainException('Ten temat jest zamknięty.'); }
        if (!static::rewardsEnabled(Presentation::get($tid)['category']??'',$tid)) { $player=''; }
        if ($player!=='' && !preg_match('/^[A-Za-z0-9_]{3,16}$/D',$player)) { throw new \DomainException('sps_bad_player'); }
        $db=\IPS\Db::i(); $now=time(); $code=NULL;
        $db->query('START TRANSACTION');
        try {
            $locked=$db->query('SELECT member_id FROM `'.$db->prefix.'core_members` WHERE member_id='.(int)$member->member_id.' FOR UPDATE')->fetch_assoc();
            if(!$locked)throw new \DomainException('Zaloguj się, aby podbić serwer.');
            $now=time();$blocked=static::blockedReason($tid,$member->member_id,static::dailyAllowance($member->member_id,$now));if($blocked!=='')throw new \DomainException($blocked);
            // Serialize boosts and redemption on the server row, including the first vote.
            $r=$db->query('SELECT * FROM `'.$db->prefix.'mpcservers_presentations` WHERE tid='.(int)$tid.' FOR UPDATE')->fetch_assoc();
            if (!$r || empty($r['is_server'])) { throw new \DomainException('Brak wpisu serwera.'); }
            if ($player!=='' && (!GameRewards::supports($r['category']) || !$r['api_hash'] || !OwnerAccess::isOwner($topic->tid,$r['api_owner']))) { throw new \DomainException('Nagrody Minecraft nie są skonfigurowane dla tego serwera.'); }
            $db->insert('mpcservers_boost_events',array('tid'=>(int)$tid,'member_id'=>(int)$member->member_id,'created'=>$now,'game'=>$r['category'],'mode_data'=>json_encode(Awards::modesFor($topic),JSON_UNESCAPED_UNICODE)));
            $db->replace('mpcservers_votes',array('tid'=>(int)$tid,'member_id'=>(int)$member->member_id,'created'=>$now));
            $db->update('mpcservers_presentations',array('bumped'=>$now,'votes'=>(int)$r['votes']+1),array('tid=?',(int)$tid));
            if ($player!=='') {
                $code=strtoupper(bin2hex(random_bytes(12)));
                $db->insert('mpcservers_rewards',array('code_hash'=>hash('sha256',$code),'tid'=>(int)$tid,'member_id'=>(int)$member->member_id,'player'=>strtolower($player),'created'=>$now,'expires'=>$now+86400,'claimed'=>0));
            }
            // A list boost only changes the topic date after seven days of inactivity.
            // The conditional UPDATE cannot overwrite the date of a concurrent reply.
            $db->update('forums_topics',array('last_post'=>$now),array('tid=? AND last_post<=? AND approved=1 AND state<>?',(int)$tid,$now-7*86400,'closed'));
            $db->query('COMMIT');
            return $code;
        } catch (\Throwable $e) { $db->query('ROLLBACK'); throw $e; }
    }
    public static function duration($seconds)
    {
        $minutes=(int)ceil($seconds/60); $h=intdiv($minutes,60); $m=$minutes%60;
        if ($h===0) { return $m.' min'; }
        return $h.' h'.($m?' '.$m.' min':'');
    }
    public static function claim($tid,$key,$code,$player)
    {
        if (!static::rewardsEnabled(Presentation::get($tid)['category']??'',$tid)) { return FALSE; }
        if (!preg_match('/^[A-Fa-f0-9]{64}$/D',$key) || !preg_match('/^[A-Fa-f0-9]{24}$/D',$code) || !preg_match('/^[A-Za-z0-9_]{3,16}$/D',$player)) { return FALSE; }
        $db=\IPS\Db::i();
        $db->query('START TRANSACTION');
        try {
            $r=$db->query('SELECT * FROM `'.$db->prefix.'mpcservers_presentations` WHERE tid='.(int)$tid.' FOR UPDATE')->fetch_assoc();
            if (!$r || empty($r['is_server']) || !$r['api_hash'] || !hash_equals($r['api_hash'],hash('sha256',$key)) || !GameRewards::supports($r['category'])) { $db->query('ROLLBACK'); return FALSE; }
            $topic=\IPS\forums\Topic::load((int)$tid);
            if (Presentation::isDirectory($topic) || !OwnerAccess::isOwner($topic->tid,$r['api_owner'])) { $db->query('ROLLBACK'); return FALSE; }
            $t=$db->select('forum_id,approved,state','forums_topics',array('tid=?',(int)$tid))->first();
            if (!Presentation::enabled($t['forum_id']) || (int)$t['approved']!==1 || $t['state']==='closed') { $db->query('ROLLBACK'); return FALSE; }
            $hash=hash('sha256',strtoupper($code));
            $reward=$db->select('*','mpcservers_rewards',array('code_hash=? AND tid=? AND player=? AND claimed=0 AND expires>?',$hash,(int)$tid,strtolower($player),time()))->first();
            $db->update('mpcservers_rewards',array('claimed'=>time()),array('code_hash=?',$hash));
            $db->query('COMMIT');
            return TRUE;
        } catch (\UnderflowException $e) { $db->query('ROLLBACK'); return FALSE; }
        catch (\Throwable $e) { $db->query('ROLLBACK'); throw $e; }
    }
}
