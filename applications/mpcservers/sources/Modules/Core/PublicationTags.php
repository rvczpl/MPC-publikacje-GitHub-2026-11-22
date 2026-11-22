<?php
namespace IPS\mpcservers\Modules\Core;
use IPS\forums\Topic;
use IPS\forums\Forum;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Native tagging policy for publication forums; display prefixes remain independent. */
class PublicationTags
{
    public static function managed($topic): bool
    {
        return $topic instanceof Topic && (isset(Servers::forums()[(int)$topic->forum_id]) || !empty(Presentation::get($topic->tid)['is_server']));
    }
    public static function enforceForums(): void
    {
        $saved=json_decode((string)\IPS\Settings::i()->sps_tag_policy_forums,TRUE)?:array();$before=$saved;$forums=Servers::forums();
        foreach($forums as $fid=>$cfg){
            try{$forum=Forum::load((int)$fid);}catch(\OutOfRangeException $e){continue;}
            if(!array_key_exists($fid,$saved))$saved[$fid]=(bool)$forum->forums_bitoptions['bw_disable_tagging'];
            if(!$forum->forums_bitoptions['bw_disable_tagging']){$forum->forums_bitoptions['bw_disable_tagging']=TRUE;$forum->save();}
        }
        foreach(array_diff_key($saved,$forums) as $fid=>$disabled){
            try{$forum=Forum::load((int)$fid);$forum->forums_bitoptions['bw_disable_tagging']=(bool)$disabled;$forum->save();}catch(\OutOfRangeException $e){}
            unset($saved[$fid]);
        }
        if($saved!==$before)\IPS\Settings::i()->changeValues(array('sps_tag_policy_forums'=>json_encode($saved)));
    }
    public static function prepareRequest(): void
    {
        $req=\IPS\Request::i();
        $d=\IPS\Dispatcher::i();
        if($d->controllerLocation!=='front'||$d->application?->directory!=='forums'||$d->controller!=='topic'||!(int)$req->id)return;
        try{$topic=Topic::load((int)$req->id);}catch(\OutOfRangeException $e){return;}
        if(!static::managed($topic))return;
        // A moved publication remains protected without changing its destination forum permanently.
        $topic->container()->forums_bitoptions['bw_disable_tagging']=TRUE;
        if((string)$req->do==='editTags')\IPS\Output::i()->error('Ręczne dodawanie tagów w publikacjach jest wyłączone. Prefiksy są niezależne od tagów.','MPCS/TAGS',403,'');
    }
    /** Compatibility with old migrations and extensions. Prefixes never write native tags. */
    public static function sync($topic): void {}
    public static function synchronize($topic,int $newTagBudget=0): bool { return TRUE; }
    public static function batch(int $after=0,int $size=50): array { return array('last'=>$after,'done'=>TRUE); }
}
