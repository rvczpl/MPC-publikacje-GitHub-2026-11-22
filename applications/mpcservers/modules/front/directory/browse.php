<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
use IPS\mpcservers\Presentation as P;
class browse extends \IPS\Dispatcher\Controller
{
    public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::front('lists');parent::execute();}
    protected function manage()
    {
        \IPS\Output::i()->title='Lista serwerów';
        \IPS\Output::i()->output=\IPS\mpcservers\Directory::render();
    }
    protected function recommend()
    {
        try{$topic=P::topic((int)\IPS\Request::i()->id);}catch(\OutOfRangeException $e){\IPS\Output::i()->error('Nie znaleziono dostępnej publikacji.','SPS/RECOMMEND404',404,'');}
        if(!\IPS\mpcservers\Recommended::canManage($topic)){\IPS\Output::i()->error('Nie masz uprawnień do polecania tego serwera.','SPS/RECOMMEND',403,'');}
        $row=P::get($topic->tid);$form=new \IPS\Helpers\Form('sps_recommend','save');
        $field=new \IPS\Helpers\Form\YesNo('sps_recommend_on',!empty($row['featured']),FALSE);$field->label='MPC Poleca';$field->description='Polecany serwer pojawia się nad numerowaną listą. Polecane serwery nie uczestniczą w TOP okresowych.';$form->add($field);
        if($values=$form->values()){\IPS\Session::i()->csrfCheck();\IPS\mpcservers\Recommended::set($topic,$values['sps_recommend_on']);\IPS\Output::i()->redirect($topic->url());}
        \IPS\Output::i()->title='Polecanie serwera: '.$topic->title;\IPS\Output::i()->output=(string)$form;
    }
    protected function fragment()
    {
        $ids=\IPS\mpcservers\Directory::requestedForums();
        \IPS\Output::i()->httpHeaders['Cache-Control']='no-store';
        \IPS\Output::i()->json(array('html'=>\IPS\mpcservers\Directory::render($ids,(int)\IPS\Request::i()->exclude,FALSE)));
    }
    protected function inlineVote()
    {
        \IPS\Output::i()->httpHeaders['Cache-Control']='no-store';
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { \IPS\Output::i()->json(array('error'=>'Wymagane żądanie POST.'),405); return; }
        if (!\IPS\Member::loggedIn()->member_id) { \IPS\Output::i()->json(array('error'=>'Zaloguj się, aby podbić serwer.'),403); return; }
        \IPS\Session::i()->csrfCheck();
        try {
            $code=\IPS\mpcservers\Boost::vote((int)\IPS\Request::i()->id,\IPS\Member::loggedIn(),trim((string)\IPS\Request::i()->player));
            \IPS\Output::i()->json(array('ok'=>TRUE,'code'=>$code,'votes'=>\IPS\mpcservers\Rankings::weekly((int)\IPS\Request::i()->id),'yearVotes'=>\IPS\mpcservers\Rankings::yearly((int)\IPS\Request::i()->id),'message'=>'Serwer podbity.'));
        } catch (\DomainException $e) { \IPS\Output::i()->json(array('error'=>\IPS\Member::loggedIn()->language()->addToStack($e->getMessage())),400); }
        catch (\OutOfRangeException $e) { \IPS\Output::i()->json(array('error'=>'Nie znaleziono dostępnego serwera.'),404); }
    }
    protected function vote()
    {
        if (!\IPS\Member::loggedIn()->member_id) { \IPS\Output::i()->error('Zaloguj się, aby podbić serwer.','SPS/1',403,''); }
        try { $topic=P::topic((int)\IPS\Request::i()->id); } catch (\OutOfRangeException $e) { \IPS\Output::i()->error('Nie znaleziono serwera.','SPS/2',404,''); }
        $r=P::get($topic->tid);
        if (!$r) { \IPS\Output::i()->error('Nie znaleziono serwera.','SPS/3',404,''); }
        \IPS\Output::i()->title='Podbij: '.$topic->title;
        $form=new \IPS\Helpers\Form('sps_vote','Podbij serwer');
        if (\IPS\mpcservers\Boost::rewardsEnabled($r['category'],$r['tid']) && \IPS\mpcservers\GameRewards::supports($r['category']) && $r['api_hash']) {
            $form->add(new \IPS\Helpers\Form\Text('sps_player','',FALSE,array('maxLength'=>16),function($v){if ($v!=='' && !preg_match('/^[A-Za-z0-9_]{3,16}$/D',$v)) {throw new \DomainException('sps_bad_player');}}));
        } else { $form->addMessage('Możesz podbić maksymalnie 5 różnych serwerów dziennie, każdy tylko raz. Limit odnawia się następnego dnia.'); }
        $values=$form->values();
        if ($values!==FALSE) {
            \IPS\Session::i()->csrfCheck();
            try {
                $code=\IPS\mpcservers\Boost::vote($topic->tid,\IPS\Member::loggedIn(),trim($values['sps_player']??''));
                \IPS\Output::i()->output='<div class="ipsBox i-padding_3"><h1>Serwer podbity!</h1>'.($code?'<p>Kod jest ważny 24 godziny. Na serwerze wpisz:</p><pre>/nagroda '.$code.'</pre><p>Zapisz kod - wyświetlamy go tylko teraz.</p>':'').'<a href="'.htmlspecialchars((string)P::url(),ENT_QUOTES,'UTF-8').'">Wróć do listy</a></div>';
                return;
            } catch (\DomainException $e) { $form->error=$e->getMessage(); }
        }
        \IPS\Output::i()->output=(string)$form;
    }
    protected function paid()
    {
        // Intentionally fail closed until a payment provider and verified callbacks exist.
        \IPS\Output::i()->error('Płatne podbijanie jest niedostępne.','SPS/PAID',403,'');
    }
}
