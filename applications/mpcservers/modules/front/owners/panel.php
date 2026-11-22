<?php
namespace IPS\mpcservers\modules\front\owners;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
use IPS\mpcservers\Owner;
use IPS\mpcservers\Presentation;
use IPS\mpcservers\Directory;
class panel extends \IPS\Dispatcher\Controller
{
    public static $csrfProtected=TRUE;
    public function execute(): void
    {
        \IPS\mpcservers\Modules\Core\Registry::front('community');
        if (!\IPS\Member::loggedIn()->member_id) { \IPS\Output::i()->error('Zaloguj się, aby otworzyć panel swoich serwerów.','SPS/OWNER1',403,''); return; }
        \IPS\Output::i()->httpHeaders['Cache-Control']='private, no-store';
        \IPS\Output::i()->httpHeaders['Referrer-Policy']='no-referrer';
        parent::execute();
    }
    protected function stats()
    {
        \IPS\Output::i()->title='Statystyki serwera';
        try { \IPS\Output::i()->output=\IPS\mpcservers\OwnerStats::render((int)\IPS\Request::i()->id); }
        catch (\OutOfRangeException $e) { \IPS\Output::i()->error('Statystyki są dostępne wyłącznie dla kont oznaczonych jako właściciel tego serwera.','SPS/STATS1',403,''); }
    }
    protected function manage()
    {
        \IPS\Output::i()->title='Moje serwery';
        \IPS\Output::i()->output=Owner::panel();
    }
    protected function owned()
    {
        try { $topic=Owner::topic((int)\IPS\Request::i()->id,\IPS\Member::loggedIn()); }
        catch (\OutOfRangeException $e) { \IPS\Output::i()->error('Nie znaleziono Twojego serwera.','SPS/OWNER2',404,''); return NULL; }
        if (!\IPS\mpcservers\GameRewards::enabled(Presentation::get($topic->tid)['category'],$topic->tid)) { \IPS\Output::i()->error('Nagrody nie są włączone dla tego serwera Minecraft.','SPS/OWNER3',403,''); return NULL; }
        return $topic;
    }
    protected function token()
    {
        $topic=$this->owned(); if (!$topic) { return; }
        \IPS\Output::i()->title='Token: '.$topic->title;
        $form=new \IPS\Helpers\Form('sps_token','sps_token_reset');
        $form->add(new \IPS\Helpers\Form\YesNo('sps_token_confirm',FALSE,TRUE,array(),function($v){if (!$v) { throw new \DomainException('Potwierdź reset tokenu.'); }}));
        if ($form->values()) {
            \IPS\Session::i()->csrfCheck();
            try { $key=Owner::rotate($topic->tid,\IPS\Member::loggedIn()); }
            catch (\OutOfRangeException $e) { \IPS\Output::i()->error('Serwer nie jest dostępny do konfiguracji.','SPS/OWNER4',403,''); return; }
            $config=Owner::config($topic->tid,$key);$e=array(Directory::class,'esc');
            \IPS\Output::i()->output='<div class="ipsBox i-padding_3"><h1>Token wygenerowany</h1><p>Poprzedni token przestał działać. Zapisz konfigurację teraz - token wyświetlamy tylko raz.</p><textarea id="spsGeneratedConfig" readonly rows="9" style="width:100%;font-family:monospace">'.$e($config).'</textarea><p><button type="button" class="ipsButton ipsButton--primary" id="spsDownloadConfig">Pobierz config.yml</button></p><p>Wgraj plik do plugins/MPCforum/config.yml i ustaw komendę nagrody.</p><a href="'.$e(Owner::url()).'">Wróć do moich serwerów</a></div><script>document.getElementById("spsDownloadConfig").addEventListener("click",function(){const u=URL.createObjectURL(new Blob([document.getElementById("spsGeneratedConfig").value],{type:"text/yaml;charset=utf-8"}));const a=document.createElement("a");a.href=u;a.download="config.yml";a.click();setTimeout(()=>URL.revokeObjectURL(u),1000);});</script>';
            return;
        }
        \IPS\Output::i()->output=(string)$form;
    }
    protected function download()
    {
        if (!$this->owned()) { return; }
        $path=\IPS\ROOT_PATH.'/applications/mpcservers/interface/downloads/MPCforum.jar';
        if (!is_file($path)) { \IPS\Output::i()->error('Plik pluginu jest chwilowo niedostępny.','SPS/OWNER5',503,''); return; }
        \IPS\Output::i()->sendOutput(file_get_contents($path),200,'application/java-archive',array('Content-Disposition'=>'attachment; filename="MPCforum.jar"','Cache-Control'=>'private, no-store'));
    }
}
