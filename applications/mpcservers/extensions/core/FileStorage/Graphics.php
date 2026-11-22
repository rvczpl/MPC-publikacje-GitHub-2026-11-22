<?php
namespace IPS\mpcservers\extensions\core\FileStorage;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Graphics extends \IPS\Extensions\FileStorageAbstract
{
 public function count(): int{return count(\IPS\mpcservers\Media::references());}
 public function move( int $offset, int $storageConfiguration, ?int $oldConfiguration = NULL ): void{$refs=\IPS\mpcservers\Media::references();if(!isset($refs[$offset]))throw new \UnderflowException;$old=$refs[$offset];$file=\IPS\File::get($oldConfiguration?:'mpcservers_Graphics',$old)->move($storageConfiguration);\IPS\mpcservers\Media::replaceReference($old,(string)$file);}
 public function fixUrls($offset){$refs=\IPS\mpcservers\Media::references();if(!isset($refs[$offset]))throw new \UnderflowException;if($new=\IPS\File::repairUrl($refs[$offset]))\IPS\mpcservers\Media::replaceReference($refs[$offset],$new);}
 public function isValidFile( \IPS\File|string $file ): bool{return in_array((string)$file,\IPS\mpcservers\Media::references(),TRUE);}
 public function delete(): void{foreach(\IPS\mpcservers\Media::references() as $ref){try{\IPS\File::get('mpcservers_Graphics',$ref)->delete();}catch(\Exception $e){}}}
 public static function settingsUpdated(){}
}
