<?php
namespace IPS\mpcservers\Modules\Lists;


if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Status
{
    public static function publicIp($ip)
    {
        // IPv4 only: avoids mapped IPv6 addresses and transition-network bypasses.
        if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) { return FALSE; }
        $n=ip2long($ip);
        foreach (array(array('100.64.0.0',10),array('169.254.0.0',16),array('192.0.0.0',24),array('192.0.2.0',24),array('198.18.0.0',15),array('198.51.100.0',24),array('203.0.113.0',24),array('224.0.0.0',4),array('240.0.0.0',4)) as list($base,$bits)) {
            $mask=(-1 << (32-$bits)); if (($n & $mask)===(ip2long($base)&$mask)) { return FALSE; }
        }
        return TRUE;
    }
    public static function resolve($host)
    {
        if (!preg_match('/^(?=.{1,253}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D',$host)) { return NULL; }
        $ips=filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)?array($host):gethostbynamel($host);
        if (!$ips) { return NULL; }
        foreach ($ips as $ip) { if (!static::publicIp($ip)) { return NULL; } }
        return $ips[0];
    }
    /**
     * Minecraft address with an SRV record (_minecraft._tcp), as the game client resolves it.
     * Used when the owner left the default port, which is how SRV addresses are usually entered.
     */
    public static function minecraftSrv($host,$port)
    {
        if (filter_var($host,FILTER_VALIDATE_IP) || ($port!==25565 && $port!==0)) { return array($host,$port ?: 25565); }
        $records=@dns_get_record('_minecraft._tcp.'.$host,DNS_SRV);
        if (!is_array($records) || !$records) { return array($host,$port ?: 25565); }
        usort($records,function($a,$b){ return ((int)($a['pri']??0)<=>(int)($b['pri']??0)) ?: ((int)($b['weight']??0)<=>(int)($a['weight']??0)); });
        $target=rtrim((string)($records[0]['target']??''),'.');
        $srvPort=(int)($records[0]['port']??0);
        if ($target==='' || $srvPort<1 || $srvPort>65535) { return array($host,$port ?: 25565); }
        return array($target,$srvPort);
    }
    public static function varint($value)
    {
        $out=''; do { $byte=$value&127; $value>>=7; $out.=chr($byte|($value?128:0)); } while ($value); return $out;
    }
    protected static function read($socket,$length)
    {
        $out=''; $deadline=microtime(TRUE)+3;
        while (strlen($out)<$length) {
            if (microtime(TRUE)>$deadline) { throw new \RuntimeException('Status timeout'); }
            $data=fread($socket,$length-strlen($out));
            if ($data===FALSE || $data==='') { throw new \RuntimeException('Incomplete status'); }
            $out.=$data;
        }
        return $out;
    }
    protected static function readVarint($socket)
    {
        $value=0; for ($i=0;$i<5;$i++) { $b=ord(static::read($socket,1)); $value|=($b&127)<<($i*7); if (!($b&128)) { return $value; } }
        throw new \RuntimeException('Invalid VarInt');
    }
    public static function probe($r)
    {
        $out=array('status'=>'unknown','checked'=>time(),'players'=>0,'capacity'=>0);
        if ($r['protocol']==='none' || $r['host']==='') { return $out; }
        $host=$r['host']; $port=(int)$r['port'];
        if ($r['protocol']==='minecraft') { list($host,$port)=static::minecraftSrv($host,$port); }
        $ip=static::resolve($host);
        if (!$ip || $port<1024 || $port>65535) { $out['status']='blocked'; return $out; }
        // Connect to the validated address, not to the hostname (DNS rebinding defense).
        $socket=@stream_socket_client(($r['protocol']==='source'?'udp':'tcp').'://'.$ip.':'.$port,$errno,$error,2,STREAM_CLIENT_CONNECT);
        $out['status']='offline'; if (!$socket) { return $out; }
        stream_set_timeout($socket,2);
        try {
            if ($r['protocol']==='source') {
                $request="\xff\xff\xff\xffTSource Engine Query\0";
                fwrite($socket,$request); $packet=fread($socket,65536);
                if (strlen($packet)===9 && substr($packet,0,5)==="\xff\xff\xff\xffA") {
                    fwrite($socket,$request.substr($packet,5,4)); $packet=fread($socket,65536);
                }
                $players=static::sourcePlayers($packet);
                $out['players']=$players[0]; $out['capacity']=$players[1];
            }
            if ($r['protocol']==='minecraft') {
                $handshake="\0".static::varint(47).static::varint(strlen($r['host'])).$r['host'].pack('n',$port)."\1";
                $request=static::varint(strlen($handshake)).$handshake."\1\0";
                if (fwrite($socket,$request)!==strlen($request)) { throw new \RuntimeException('Write failed'); }
                $packet=static::readVarint($socket);
                if ($packet<3 || $packet>65536 || static::readVarint($socket)!==0) { throw new \RuntimeException('Invalid packet'); }
                $length=static::readVarint($socket);
                if ($length<2 || $length>$packet-2 || $length>65536) { throw new \RuntimeException('Invalid JSON length'); }
                $data=json_decode(static::read($socket,$length),TRUE,32);
                if (!is_array($data) || !isset($data['players']['online'],$data['players']['max']) || !is_numeric($data['players']['online']) || !is_numeric($data['players']['max'])) { throw new \RuntimeException('Invalid status'); }
                $out['players']=max(0,min(1000000,(int)$data['players']['online'])); $out['capacity']=max(0,min(1000000,(int)$data['players']['max']));
            }
            $out['status']='online';
        } catch (\Throwable $e) { $out['status']='offline'; }
        finally { fclose($socket); }
        return $out;
    }
    public static function sourcePlayers($packet)
    {
        if (substr($packet,0,5)!=="\xff\xff\xff\xffI" || strlen($packet)<6) { throw new \RuntimeException('Unsupported A2S packet'); }
        $offset=6; // Header, response type and protocol; then four null-terminated strings.
        for ($i=0;$i<4;$i++) {
            $end=strpos($packet,"\0",$offset);
            if ($end===FALSE) { throw new \RuntimeException('Invalid A2S string'); }
            $offset=$end+1;
        }
        $offset+=2; // App ID, little endian.
        if (strlen($packet)<$offset+2) { throw new \RuntimeException('Invalid A2S players'); }
        return array(ord($packet[$offset]),ord($packet[$offset+1]));
    }
}
