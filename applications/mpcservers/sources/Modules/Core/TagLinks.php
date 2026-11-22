<?php
/**
 * System Publikacji Serwerów MPCforum - znacznik [serwery] zapisany jako link (IPS5)
 *
 * IPS5 nie ma hooków na podglądy treści (widżety, strumienie aktywności, wyniki wyszukiwania, podpowiedzi tematu),
 * więc znacznik [serwery] wpisany przez uprawnionego autora zapisujemy w poście jako zwykły link do spisu serwerów
 * z czytelnym tekstem. Podglądy pokazują ten tekst zamiast "[serwery=...]", a widok tematu nadal zamienia go na spis.
 */
namespace IPS\mpcservers\Modules\Core;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class TagLinks
{
	/** Parametr adresu, po którym rozpoznajemy link-znacznik */
	const PARAM = 'mpcs_spis';

	/** [serwery] albo [serwery=195,299] */
	const TAG = '/\[serwery(?:\s*=\s*([0-9,;\s]*))?\]/i';

	/**
	 * Zamień znaczniki na linki (poza cytatami i blokami kodu)
	 *
	 * @param	string	$html		Treść posta
	 * @param	int		$forumId	Dział tematu (dla [serwery] bez parametru)
	 * @return	string
	 */
	public static function toLinks( string $html, int $forumId ): string
	{
		if ( stripos( $html, '[serwery' ) === FALSE )
		{
			return $html;
		}
		$parts = preg_split( '#(<(blockquote|pre|code)\b.*?</\2>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( $parts === FALSE )
		{
			return $html;
		}
		$out = '';
		for ( $i = 0, $n = \count( $parts ); $i < $n; $i++ )
		{
			$part = $parts[ $i ];
			if ( preg_match( '#^<(blockquote|pre|code)\b#i', $part ) )
			{
				$out .= $part;
				$i++; /* skip the captured tag name */
				continue;
			}
			$out .= preg_replace_callback( static::TAG, function( $m ) use ( $forumId ) {
				$arg = trim( preg_replace( '/[;\s]+/', ',', $m[1] ?? '' ), ',' );
				return static::link( $arg, $forumId );
			}, $part );
		}
		return $out;
	}

	/**
	 * Link w miejsce znacznika
	 *
	 * @param	string	$arg		Lista działów ("" = dział tematu)
	 * @param	int		$forumId	Dział tematu
	 * @return	string
	 */
	public static function link( string $arg, int $forumId ): string
	{
		$ids = $arg === '' ? array( $forumId ) : array_filter( array_map( 'intval', explode( ',', $arg ) ) );
		$names = array();
		foreach ( $ids as $id )
		{
			try
			{
				/* The real name (Forum::$_title is only a language placeholder until the page is rendered) */
				\IPS\forums\Forum::load( $id );
				$names[] = \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'forums_forum_' . $id );
			}
			catch ( \Throwable ) { }
		}
		$text = 'Spis serwerów' . ( $names ? ': ' . implode( ', ', $names ) : '' );
		/* Stored like other internal links of IPS posts, so a change of the community address does not break it */
		$url = (string) Presentation::url( array( static::PARAM => $arg === '' ? 'auto' : $arg ) );
		$base = rtrim( (string) \IPS\Settings::i()->base_url, '/' ) . '/';
		if ( mb_strpos( $url, $base ) === 0 )
		{
			$url = '<___base_url___>/' . mb_substr( $url, mb_strlen( $base ) );
		}
		return '<a href="' . str_replace( array( '&', '"' ), array( '&amp;', '&quot;' ), $url ) . '">' . htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) . '</a>';
	}

	/**
	 * Linki-znaczniki z powrotem na [serwery=...] (przed rozwinięciem spisów w widoku tematu)
	 *
	 * @param	string	$html
	 * @return	string
	 */
	public static function toTags( string $html ): string
	{
		if ( strpos( $html, static::PARAM ) === FALSE )
		{
			return $html;
		}
		return preg_replace_callback( '#<a\b[^>]*\bhref=(["\'])[^"\']*?[?&](?:amp;)?' . static::PARAM . '=([0-9,%Ca-z]*)[^"\']*\1[^>]*>.*?</a>#is', function( $m ) {
			$arg = urldecode( str_replace( '%2C', ',', $m[2] ) );
			return ( $arg === 'auto' or $arg === '' ) ? '[serwery]' : '[serwery=' . preg_replace( '/[^0-9,]/', '', $arg ) . ']';
		}, $html ) ?? $html;
	}

	/**
	 * Odśwież linki-znaczniki zapisanego posta (np. po zmianie nazwy działu)
	 *
	 * @param	\IPS\forums\Topic\Post	$post
	 * @return	bool
	 */
	public static function refreshPost( \IPS\forums\Topic\Post $post ): bool
	{
		$content = (string) $post->post;
		if ( strpos( $content, static::PARAM ) === FALSE )
		{
			return FALSE;
		}
		$post->post = static::toTags( $content );
		return static::convertPost( $post );
	}

	/**
	 * Zapisz znaczniki posta jako linki i przeindeksuj (wywoływane po zapisie posta)
	 *
	 * @param	\IPS\forums\Topic\Post	$post
	 * @return	bool	Czy treść się zmieniła
	 */
	public static function convertPost( \IPS\forums\Topic\Post $post ): bool
	{
        if (!FeatureHooks::enabled('link_conversion')) return FALSE;
		$content = (string) $post->post;
		if ( stripos( $content, '[serwery' ) === FALSE or !Servers::canUseTag( $post->author() ) )
		{
			return FALSE;
		}
		$new = static::toLinks( $content, (int) $post->item()->forum_id );
		if ( $new === $content )
		{
			return FALSE;
		}
		$post->post = $new;
		$post->save();
		try
		{
			\IPS\Content\Search\Index::i()->index( $post );
			if ( $post->isFirst() )
			{
				\IPS\Content\Search\Index::i()->index( $post->item() );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
		return TRUE;
	}
}
