<?php
namespace IPS\mpcservers\Modules\Metin2;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\Status;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ProxySecret
{
    /** Read the authoritative value, not a potentially stale IPS settings cache. */
    public static function key($initialize=TRUE)
    {
        $db=\IPS\Db::i();
        try{$row=$db->select('conf_value,conf_default','core_sys_conf_settings',array('conf_key=?','sps_proxy_secret'))->first();}
        catch(\UnderflowException $e){
            if(!$initialize)throw new \RuntimeException('Proxy key not initialized');
            \IPS\Application::load('mpcservers')->installSettings();
            $row=$db->select('conf_value,conf_default','core_sys_conf_settings',array('conf_key=?','sps_proxy_secret'))->first();
        }
        $key=(string)($row['conf_value']?:$row['conf_default']);
        if(strlen($key)<32){
            if(!$initialize)throw new \RuntimeException('Proxy key not initialized');
            // Conditional update: simultaneous viewers must never rotate a valid key.
            $db->update('core_sys_conf_settings',array('conf_value'=>bin2hex(random_bytes(32))),array('conf_key=? AND (conf_value IS NULL OR LENGTH(conf_value)<32)','sps_proxy_secret'));
            $key=(string)$db->select('conf_value','core_sys_conf_settings',array('conf_key=?','sps_proxy_secret'))->first();
            if(strlen($key)<32)throw new \RuntimeException('Proxy key initialization failed');
            unset(\IPS\Data\Store::i()->settings);
        }
        \IPS\Settings::i()->sps_proxy_secret=$key;
        return $key;
    }
}
