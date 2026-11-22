<?php
namespace IPS\mpcservers\modules\admin\servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class advanced extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('metin2');parent::execute();}
 protected function manage(){
  $settings=\IPS\Settings::i();$form=new \IPS\Helpers\Form('mpcs_advanced','save');
  $form->addHeader('sps_h_previews');
  $form->add(new \IPS\Helpers\Form\YesNo('sps_restrict_domains',(bool)$settings->sps_restrict_domains));
$form->add(new \IPS\Helpers\Form\YesNo('sps_link_mode_enabled',(bool)$settings->sps_link_mode_enabled));
        $renderer=new \IPS\Helpers\Form\Radio('sps_presentation_renderer','proxy',FALSE,array('options'=>array('proxy'=>'sps_renderer_proxy','screenshot'=>'sps_renderer_screenshot'),'disabled'=>array('screenshot')),function($value){if($value!=='proxy')throw new \DomainException('sps_renderer_unavailable');});$form->add($renderer);
        $form->add(new \IPS\Helpers\Form\YesNo('sps_proxy_js',(bool)$settings->sps_proxy_js));
		
  $form->add(new \IPS\Helpers\Form\YesNo('sps_proxy_ajax',(bool)$settings->sps_proxy_ajax));
  if($values=$form->values()){\IPS\Session::i()->csrfCheck();$settings->changeValues(array('sps_restrict_domains'=>$values['sps_restrict_domains']?1:0,'sps_link_mode_enabled'=>$values['sps_link_mode_enabled']?1:0,'sps_proxy_js'=>$values['sps_proxy_js']?1:0,'sps_proxy_ajax'=>$values['sps_proxy_ajax']?1:0,'sps_presentation_renderer'=>'proxy'));\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module=metin2&controller=advanced'),'saved');return;}
  \IPS\Output::i()->title='Zaawansowane';\IPS\Output::i()->output='<div class="ipsBox">'.$form.'</div>';
 }
}
