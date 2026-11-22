<?php
/**
 * System Publikacji Serwerów MPCforum - znaczniki [serwery] zapisywane jako linki
 */
namespace IPS\mpcservers\listeners;

use IPS\Content as ContentClass;
use IPS\Events\ListenerType\ContentListenerType;
use IPS\forums\Topic\Post;
use IPS\mpcservers\Modules\Core\Servers;
use IPS\mpcservers\Modules\Core\TagLinks;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class PostTags extends ContentListenerType
{
	/**
	 * @brief	[Required] The class that is handled by this listener
	 */
	public static string $class = Post::class;

	/**
	 * Post utworzony lub zmieniony
	 *
	 * @param	ContentClass	$object
	 * @param	array			$values
	 * @param	bool			$new
	 * @return	void
	 */
	public function onCreateOrEdit( ContentClass $object, array $values, bool $new = FALSE ): void
	{
		try
		{
			if ( $object instanceof Post )
			{
				TagLinks::convertPost( $object );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
	}
}
