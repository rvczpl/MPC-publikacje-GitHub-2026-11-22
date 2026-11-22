<?php
/**
 * System Publikacji Serwerów MPCforum - wejście w temat (IPS4: hook na kontroler tematu)
 * Odczyt polityki widoczności tagów i niezależne statystyki wejść. Archiwizacja działa tylko w tle.
 */
namespace IPS\mpcservers\listeners;

use IPS\Content as ContentClass;
use IPS\Content\Item as ItemClass;
use IPS\Events\ListenerType\ContentListenerType;
use IPS\forums\Topic;
use IPS\mpcservers\Modules\Core\Archive;
use IPS\mpcservers\Modules\Core\Servers;
use IPS\mpcservers\Modules\Core\TagLinks;
use IPS\mpcservers\Modules\Community\TopicViews;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class TopicView extends ContentListenerType
{
	/**
	 * @brief	[Required] The class that is handled by this listener
	 */
	public static string $class = Topic::class;

	/**
	 * Temat wyświetlony
	 *
	 * @param	ItemClass	$item
	 * @return	void
	 */
	public function onItemView( ItemClass $item ): void
	{
		if ( !( $item instanceof Topic ) )
		{
			return;
		}
		try
		{
            if(\IPS\mpcservers\Modules\Core\PublicationTags::managed($item))\IPS\Output::i()->cssFiles[]=\IPS\Http\Url::baseUrl().'applications/mpcservers/interface/publication-tags.css?v=60008';
			TopicViews::record( $item );
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
	}

	/**
	 * Nowy temat lub pełna edycja tematu: znaczniki [serwery] w pierwszym poście jako linki
	 * (IPS5 wywołuje to zdarzenie dla tematu, a nie dla jego pierwszego posta)
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
			if ( $object instanceof Topic and !$object->isArchived() and ( $post = $object->firstComment() ) instanceof \IPS\forums\Topic\Post )
			{
				TagLinks::convertPost( $post );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
	}
}
