<?php
/**
 * System Publikacji Serwerów MPCforum - wywołania z szablonów IPS5 (data/customtemplates.json)
 * Błąd w aplikacji nie może zepsuć strony forum, więc każde wywołanie jest osłonięte.
 */
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Highlights\Highlights;
use IPS\mpcservers\Modules\Highlights\Recommended;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class TemplateHooks
{
	/** Znaczki trybów przy tytule tematu na liście tematów działu */
	public static function rowBadges( $row ): string
	{
		try
		{
			return ( $row instanceof \IPS\forums\Topic and !Servers::pluginActive() ) ? (string) Servers::topicBadges( $row ) : '';
		}
		catch ( \Throwable $e ) { Servers::logError( $e ); return ''; }
	}

	/** MPC Poleca + znaczki trybów w nagłówku tematu */
	public static function topicBadges( $topic ): string
	{
		try
		{
			if ( !( $topic instanceof \IPS\forums\Topic ) )
			{
				return '';
			}
			return (string) Recommended::titleTag( $topic ) . ( Servers::pluginActive() ? '' : (string) Servers::topicBadges( $topic ) );
		}
		catch ( \Throwable $e ) { Servers::logError( $e ); return ''; }
	}

	/** Wyróżnione serwery w nagłówku działu */
	public static function forumHeader( $forum ): string
	{
		try
		{
			return $forum instanceof \IPS\forums\Forum ? (string) Highlights::forum( $forum ) : '';
		}
		catch ( \Throwable $e ) { Servers::logError( $e ); return ''; }
	}

	/** Przed pierwszym postem publikacji */
	public static function beforePost( $item, $comment ): string
	{
		try
		{
			return ( $item instanceof \IPS\forums\Topic and $comment instanceof \IPS\forums\Topic\Post and $comment->isFirst() ) ? PresentationView::before( $item, $comment ) : '';
		}
		catch ( \Throwable $e ) { Servers::logError( $e ); return ''; }
	}

	/** Po pierwszym poście publikacji */
	public static function afterPost( $item, $comment ): string
	{
		try
		{
			return ( $item instanceof \IPS\forums\Topic and $comment instanceof \IPS\forums\Topic\Post and $comment->isFirst() ) ? PresentationView::after( $item, $comment ) : '';
		}
		catch ( \Throwable $e ) { Servers::logError( $e ); return ''; }
	}
}
