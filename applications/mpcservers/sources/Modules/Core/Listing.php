<?php
/**
 * System Publikacji Serwerów MPCforum - znacznik [serwery] i widok spisu
 *
 * Spis ma format spisów MPC Creators: panel filtrów z przyciskami IPS,
 * wyszukiwarka, sortowanie, wiersze (publikacja | autor | odświeżono)
 * i stronicowanie. Filtry działają po stronie serwera, a skrypt podmienia
 * sam spis bez przeładowania strony.
 */

namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Servers;
use IPS\mpcservers\Modules\Core\Archive;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Listing
{
	/**
	 * @brief	Czy autor posta może używać znacznika (pid => bool)
	 */
	protected static $authorCache = array();

	/**
	 * @brief	Sortowania spisu
	 */
	public static $sorts = array( 'refresh', 'new', 'old', 'az' );

	/**
	 * @return	void
	 */
	public static function resetCache()
	{
		static::$authorCache = array();
	}

	/* ===================================================================
	 *  Znacznik w widoku tematu
	 * =================================================================== */

	/**
	 * Zamień znaczniki [serwery] w HTML widoku tematu na spisy
	 *
	 * Znacznik działa tylko w treści postów autorów z uprawnieniami,
	 * poza cytatami i blokami kodu. Atrybuty HTML nie są ruszane.
	 *
	 * @param	string				$html	\IPS\Output::i()->output
	 * @param	\IPS\forums\Topic	$host	Bieżący temat (nie trafia do spisu)
	 * @return	string
	 */
	public static function processTopicOutput( $html, $host )
	{
		if ( !\is_string( $html ) or \stripos( $html, '[serwery' ) === FALSE )
		{
			return $html;
		}
		if ( !\preg_match_all( '/<article\b[^>]*\bid=(["\'])elComment_(\d+)\1/i', $html, $articles, PREG_OFFSET_CAPTURE ) )
		{
			return $html;
		}

		$viewer = \IPS\Member::loggedIn();
		$replacements = array();
		$count = \count( $articles[0] );

		for ( $i = 0; $i < $count; $i++ )
		{
			$start = $articles[0][ $i ][1];
			$end = ( $i + 1 < $count ) ? $articles[0][ $i + 1 ][1] : \strlen( $html );
			$region = static::contentRegion( $html, $start, $end );
			if ( !$region )
			{
				continue;
			}
			list( $cStart, $cEnd ) = $region;
			$content = \substr( $html, $cStart, $cEnd - $cStart );
			if ( \stripos( $content, '[serwery' ) === FALSE )
			{
				continue;
			}
			if ( !\preg_match_all( '/\[serwery(?:\s*=\s*([^\]<>]*))?\]/i', $content, $tags, PREG_OFFSET_CAPTURE ) )
			{
				continue;
			}

			$pid = (int) $articles[2][ $i ][0];
			$allowed = static::postAuthorCanUseTag( $pid );

			foreach ( $tags[0] as $k => $tag )
			{
				$pos = $tag[1];
				$len = \strlen( $tag[0] );
				if ( !$allowed or static::insideQuote( $content, $pos ) )
				{
					continue;
				}

				$arg = ( isset( $tags[1][ $k ] ) and $tags[1][ $k ][1] >= 0 ) ? $tags[1][ $k ][0] : '';
				$block = static::forTag( $arg, $host, $viewer );

				/* Znacznik sam w akapicie: zamień cały akapit (blok w <p> to błędny HTML) */
				$absStart = $cStart + $pos;
				$absLen = $len;
				if ( \preg_match( '/<p(?:\s[^>]*)?>\s*$/i', \substr( $content, 0, $pos ), $open ) and \preg_match( '/^\s*<\/p>/i', \substr( $content, $pos + $len ), $close ) )
				{
					$absStart -= \strlen( $open[0] );
					$absLen += \strlen( $open[0] ) + \strlen( $close[0] );
				}
				$replacements[] = array( $absStart, $absLen, $block );
			}
		}

		if ( !$replacements )
		{
			return $html;
		}

		\usort( $replacements, function( $a, $b ) {
			return $b[0] - $a[0];
		} );
		foreach ( $replacements as $r )
		{
			$html = \substr_replace( $html, $r[2], $r[0], $r[1] );
		}

		return $html . Servers::assets( 'list' );
	}

	/**
	 * Zakres treści posta (wnętrze elementu data-role="commentContent")
	 *
	 * @param	string	$html
	 * @param	int		$from	Początek <article>
	 * @param	int		$to		Początek następnego <article> albo koniec
	 * @return	array|NULL	array( początek, koniec )
	 */
	protected static function contentRegion( $html, $from, $to )
	{
		$slice = \substr( $html, $from, $to - $from );
		if ( !\preg_match( '/data-role=(["\'])commentContent\1/i', $slice, $m, PREG_OFFSET_CAPTURE ) )
		{
			return NULL;
		}
		$attrPos = $from + $m[0][1];
		$tagStart = \strrpos( \substr( $html, 0, $attrPos ), '<' );
		if ( $tagStart === FALSE or !\preg_match( '/^<([a-z0-9]+)/i', \substr( $html, $tagStart, 20 ), $tn ) )
		{
			return NULL;
		}
		$tagName = \strtolower( $tn[1] );
		$tagEnd = \strpos( $html, '>', $attrPos );
		if ( $tagEnd === FALSE )
		{
			return NULL;
		}

		$contentStart = $tagEnd + 1;
		$depth = 1;
		$offset = $contentStart;
		while ( \preg_match( '/<(\/?)' . $tagName . '\b[^>]*>/i', $html, $t, PREG_OFFSET_CAPTURE, $offset ) )
		{
			$depth += ( $t[1][0] === '/' ) ? -1 : 1;
			if ( $depth === 0 )
			{
				return array( $contentStart, $t[0][1] );
			}
			$offset = $t[0][1] + \strlen( $t[0][0] );
		}
		return NULL;
	}

	/**
	 * Czy pozycja leży w cytacie, bloku kodu albo wewnątrz znacznika HTML
	 *
	 * @param	string	$content
	 * @param	int		$pos
	 * @return	bool
	 */
	protected static function insideQuote( $content, $pos )
	{
		$before = \strtolower( \substr( $content, 0, $pos ) );

		$lt = \strrpos( $before, '<' );
		$gt = \strrpos( $before, '>' );
		if ( $lt !== FALSE and ( $gt === FALSE or $lt > $gt ) )
		{
			return TRUE;
		}

		foreach ( array( 'blockquote', 'pre', 'code' ) as $tag )
		{
			$open = \preg_match_all( '/<' . $tag . '\b/', $before );
			$close = \substr_count( $before, '</' . $tag );
			if ( $open > $close )
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Czy autor posta może używać znacznika
	 *
	 * @param	int	$pid
	 * @return	bool
	 */
	protected static function postAuthorCanUseTag( $pid )
	{
		if ( !isset( static::$authorCache[ $pid ] ) )
		{
			$ok = FALSE;
			try
			{
				$authorId = (int) \IPS\Db::i()->select( 'author_id', 'forums_posts', array( 'pid=?', (int) $pid ) )->first();
				if ( $authorId )
				{
					$ok = Servers::canUseTag( \IPS\Member::load( $authorId ) );
				}
			}
			catch ( \UnderflowException $e ) { }
			static::$authorCache[ $pid ] = $ok;
		}
		return static::$authorCache[ $pid ];
	}

	/**
	 * Spis dla znacznika (z obsługą błędów)
	 *
	 * @param	string				$arg	Tekst po "=" (numery działów)
	 * @param	\IPS\forums\Topic	$host
	 * @param	\IPS\Member			$viewer
	 * @return	string
	 */
	public static function forTag( $arg, $host, $viewer )
	{
        // The topic is designated by ACP; its directory is rendered by the topic hook.
        if (Presentation::isDirectory($host)) { return ''; }
        return static::tagError('Temat spisu wskazuje administrator w ACP → Działy publikacji.', $viewer);
    }

	/**
	 * Komunikat błędu znacznika: tylko dla osób, które mogą używać znacznika
	 *
	 * @param	string		$message
	 * @param	\IPS\Member	$viewer
	 * @return	string
	 */
	protected static function tagError( $message, $viewer )
	{
		if ( !Servers::canUseTag( $viewer ) and !Servers::isStaff( $viewer ) )
		{
			return '';
		}
		return Servers::assets( 'list' ) . '<div class="mpcs-err ipsMessage ipsMessage--warning">' . Servers::esc( $message ) . '</div>';
	}

	/* ===================================================================
	 *  Spis
	 * =================================================================== */

	/**
	 * Klucz parametrów spisu w adresie (różne spisy w jednym temacie nie mieszają filtrów)
	 *
	 * @param	array	$forumIds
	 * @return	string
	 */
	public static function key( array $forumIds )
	{
		return 'srv' . \implode( '_', \array_map( 'intval', $forumIds ) ) . '_';
	}

	/**
	 * Filtry z adresu: "grupa:opcja,grupa:opcja"
	 *
	 * @param	string	$raw
	 * @param	array	$groups
	 * @return	array	grupa => [opcje]
	 */
	public static function parseFilters( $raw, array $groups )
	{
		$out = array();
		foreach ( \explode( ',', \mb_substr( (string) $raw, 0, 1000 ) ) as $token )
		{
			$parts = \explode( ':', \trim( $token ), 2 );
			if ( \count( $parts ) !== 2 or !isset( $groups[ $parts[0] ]['options'][ $parts[1] ] ) )
			{
				continue;
			}
			if ( !isset( $out[ $parts[0] ] ) or !\in_array( $parts[1], $out[ $parts[0] ], TRUE ) )
			{
				$out[ $parts[0] ][] = $parts[1];
			}
		}
		return $out;
	}

	/**
	 * Filtry -> tekst do adresu
	 *
	 * @param	array	$filters
	 * @return	string
	 */
	public static function serializeFilters( array $filters )
	{
		$tokens = array();
		foreach ( $filters as $g => $opts )
		{
			foreach ( $opts as $o )
			{
				$tokens[] = $g . ':' . $o;
			}
		}
		return \implode( ',', $tokens );
	}

	/**
	 * Tekst do wyszukiwania: małe litery bez polskich znaków
	 *
	 * @param	string	$text
	 * @return	string
	 */
	public static function fold( $text )
	{
		$text = \mb_strtolower( (string) $text, 'UTF-8' );
		return \strtr( $text, array( 'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z' ) );
	}

	/**
	 * Czy wiersz pasuje do filtrów (OR w grupie, AND między grupami)
	 *
	 * @param	array		$attrs
	 * @param	array		$filters
	 * @param	string|NULL	$skipGroup	Pomiń grupę (liczniki przy filtrach)
	 * @return	bool
	 */
	protected static function matches( array $attrs, array $filters, $skipGroup = NULL )
	{
		foreach ( $filters as $g => $opts )
		{
			if ( $g === $skipGroup )
			{
				continue;
			}
			if ( empty( $attrs[ $g ] ) or !\array_intersect( $opts, $attrs[ $g ] ) )
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * HTML spisu publikacji
	 *
	 * @param	array				$forumIds
	 * @param	\IPS\forums\Topic	$host		Temat ze spisem (nie trafia do spisu, z niego budujemy adresy)
	 * @param	\IPS\Member			$viewer
	 * @return	string
	 */
	public static function render( array $forumIds, $host, $viewer )
	{
		/* Tematy po terminie trafiają do archiwum, zanim pokażemy spis */

		$forums = array();
		$groups = array();
		foreach ( $forumIds as $fid )
		{
			$cfg = Servers::enabledForum( $fid );
			try
			{
				$forum = \IPS\forums\Forum::load( $fid );
			}
			catch ( \OutOfRangeException $e )
			{
				continue;
			}
			if ( !$cfg or !$forum->can( 'view', $viewer ) or !$forum->can( 'read', $viewer ) )
			{
				continue;
			}
			$error = NULL;
			$forums[ (int) $fid ] = array(
				'forum'		=> $forum,
				'cfg'		=> $cfg,
				'target'	=> Archive::activeTarget( $cfg, $error ),
				'error'		=> $error,
				'mod'		=> ( Servers::isStaff( $viewer ) or Servers::isForumMod( $viewer, $forum, 'move' ) ),
			);

			/* Grupy z kilku działów łączą się po kluczu */
			foreach ( $cfg['groups'] as $key => $group )
			{
				if ( !isset( $groups[ $key ] ) )
				{
					$groups[ $key ] = $group;
				}
				else
				{
					$groups[ $key ]['options'] += $group['options'];
					$groups[ $key ]['multi'] = $groups[ $key ]['multi'] || $group['multi'];
				}
			}
		}

		if ( !$forums )
		{
			return '';
		}

		/* Parametry z adresu */
		$key = static::key( \array_keys( $forums ) );
		$request = \IPS\Request::i();
		$q = \trim( \mb_substr( (string) $request->{ $key . 'q' }, 0, 100 ) );
		$filters = static::parseFilters( (string) $request->{ $key . 'f' }, $groups );
		$sort = (string) $request->{ $key . 'sort' };
		if ( !\in_array( $sort, static::$sorts, TRUE ) )
		{
			$sort = 'refresh';
		}

		/* Tematy widoczne dla czytającego */
		$where = array(
			array( \IPS\Db::i()->in( 'forums_topics.forum_id', \array_keys( $forums ) ) ),
			array( 'forums_topics.approved=1' ),
			array( 'forums_topics.state<>?', 'link' ),
		);
		if ( $host and $host->tid )
		{
			$where[] = array( 'forums_topics.tid<>?', (int) $host->tid );
		}
		if ( !(int) \IPS\Settings::i()->mpcs_list_pinned )
		{
			$where[] = array( 'forums_topics.pinned=0' );
		}
		if ( !(int) \IPS\Settings::i()->mpcs_list_old )
		{
			$sinceClauses = array();
			foreach ( $forums as $fid => $f )
			{
				$sinceClauses[] = '(forums_topics.forum_id=' . (int) $fid . ' AND forums_topics.start_date>=' . (int) $f['cfg']['since'] . ')';
			}
			$where[] = array( '(' . \implode( ' OR ', $sinceClauses ) . ')' );
		}
		$limit = (int) \IPS\Settings::i()->mpcs_list_limit;
		$limit = ( $limit > 0 ) ? \min( $limit, 2000 ) : 1000;

		$topics = array();
		$rawRows = array();
		foreach ( \IPS\forums\Topic::getItemsWithPermission( $where, 'forums_topics.last_post DESC', array( 0, $limit ) ) as $topic )
		{
			if ( !$topic->canView() || \IPS\mpcservers\Presentation::isDirectory($topic) )
			{
				continue;
			}
            if (\IPS\mpcservers\Presentation::enabled($topic->forum_id) && empty(\IPS\mpcservers\Presentation::get($topic->tid)['is_server'])) { continue; }
			$topics[ (int) $topic->tid ] = $topic;
			$rawRows[ (int) $topic->tid ] = array( 'tid' => (int) $topic->tid, 'start_date' => (int) $topic->start_date, 'last_post' => (int) $topic->last_post, 'starter_id' => (int) $topic->starter_id, 'forum_id' => (int) $topic->forum_id );
		}
		$attrRows = Servers::attrRows( \array_keys( $topics ) );

		/* Termin archiwizacji: tematy po terminie są właśnie przenoszone, więc ich nie pokazujemy */
		$deadlines = array();
		$byMode = array();
		foreach ( $rawRows as $tid => $row )
		{
			$f = $forums[ $row['forum_id'] ];
			/* Archiwizacja obejmuje tylko tematy założone po dodaniu działu, bez przypiętych (opcja) i wyróżnionych */
			if ( $f['target'] and $row['start_date'] >= (int) $f['cfg']['since'] and ( !$topics[ $tid ]->pinned or empty( $f['cfg']['skip_pinned'] ) ) and !$topics[ $tid ]->featured )
			{
				$byMode[ $f['cfg']['activity'] ][ $tid ] = $row;
			}
		}
		foreach ( $byMode as $mode => $list )
		{
			foreach ( Archive::activity( $list, $mode ) as $tid => $act )
			{
				$deadlines[ $tid ] = Archive::deadline( $forums[ $rawRows[ $tid ]['forum_id'] ]['cfg'], $act );
			}
		}

		$now = \time();
		$items = array();
		foreach ( $topics as $tid => $topic )
		{
			if ( isset( $deadlines[ $tid ] ) and $deadlines[ $tid ] !== NULL and $deadlines[ $tid ] <= $now )
			{
				continue;
			}
			$cfg = $forums[ (int) $topic->forum_id ]['cfg'];
			$items[ $tid ] = array(
				'topic'		=> $topic,
				'attrs'		=> isset( $attrRows[ $tid ] ) ? Servers::cleanAttrs( $attrRows[ $tid ]['attrs'], $cfg['groups'] ) : array(),
				'title'		=> static::fold( $topic->title ),
				'deadline'	=> isset( $deadlines[ $tid ] ) ? $deadlines[ $tid ] : NULL,
			);
		}
		$total = \count( $items );

		/* Wyszukiwanie */
		$searched = $items;
		if ( $q !== '' )
		{
			$needle = static::fold( $q );
			$searched = \array_filter( $items, function( $item ) use ( $needle ) {
				return \mb_strpos( $item['title'], $needle ) !== FALSE;
			} );
		}

		/* Liczniki przy filtrach: z uwzględnieniem wyszukiwania i filtrów innych grup */
		$counts = array();
		foreach ( $searched as $item )
		{
			foreach ( $item['attrs'] as $g => $opts )
			{
				if ( !isset( $groups[ $g ] ) or !static::matches( $item['attrs'], $filters, $g ) )
				{
					continue;
				}
				foreach ( $opts as $o )
				{
					$counts[ $g ][ $o ] = isset( $counts[ $g ][ $o ] ) ? $counts[ $g ][ $o ] + 1 : 1;
				}
			}
		}

		$matches = array();
		foreach ( $searched as $tid => $item )
		{
			if ( static::matches( $item['attrs'], $filters ) )
			{
				$matches[ $tid ] = $item;
			}
		}

		/* Sortowanie (domyślnie: ostatnio odświeżone, jak lista tematów działu) */
		\uasort( $matches, function( $a, $b ) use ( $sort ) {
			$ta = $a['topic'];
			$tb = $b['topic'];
			switch ( $sort )
			{
				case 'new':
					return ( (int) $tb->start_date <=> (int) $ta->start_date ) ?: ( (int) $tb->tid <=> (int) $ta->tid );
				case 'old':
					return ( (int) $ta->start_date <=> (int) $tb->start_date ) ?: ( (int) $ta->tid <=> (int) $tb->tid );
				case 'az':
					return \strcmp( $a['title'], $b['title'] ) ?: ( (int) $ta->tid <=> (int) $tb->tid );
				default:
					return ( (int) $tb->last_post <=> (int) $ta->last_post ) ?: ( (int) $tb->tid <=> (int) $ta->tid );
			}
		} );

		$size = (int) \IPS\Settings::i()->mpcs_per_page;
		$size = ( $size > 0 ) ? \min( $size, 100 ) : 20;
		$pages = \max( 1, (int) \ceil( \count( $matches ) / $size ) );
		$page = \max( 1, \min( $pages, (int) $request->{ $key . 'page' } ) );

		/* Adresy: temat ze spisem + parametry spisu */
		$state = array( 'q' => $q, 'f' => static::serializeFilters( $filters ), 'sort' => $sort, 'page' => $page );
		$link = function( array $change ) use ( $host, $key, $state ) {
			$s = \array_merge( $state, $change );
			$params = array();
			foreach ( array( 'q', 'f', 'sort', 'page' ) as $p )
			{
				$params[ $key . $p ] = (string) $s[ $p ];
			}
			$url = $host->url()->setQueryString( $params );
			if ( \method_exists( $url, 'setFragment' ) )
			{
				$url = $url->setFragment( \rtrim( $key, '_' ) );
			}
			return Servers::esc( (string) $url );
		};

		$anyFilter = ( $filters or $q !== '' );
		$listId = \rtrim( $key, '_' );
		$names = array();
		foreach ( $forums as $f )
		{
			$names[] = $f['forum']->_title;
		}

		$html = Servers::assets( 'list' ) . '<section class="mpcs" id="' . Servers::esc( $listId ) . '" data-mpcs-list="' . Servers::esc( $listId ) . '" data-l-loading="' . Servers::esc( Servers::lang( 'mpcs_loading', 'Wczytywanie…' ) ) . '" data-l-error="' . Servers::esc( Servers::lang( 'mpcs_ajax_error', 'Nie udało się odświeżyć spisu. Spróbuj ponownie albo odśwież stronę.' ) ) . '" aria-label="' . Servers::esc( Servers::lang( 'mpcs_list_label', 'Spis serwerów: %s', array( \implode( ', ', $names ) ) ) ) . '">';

		/* Opis działu */
		foreach ( $forums as $f )
		{
			if ( \trim( (string) $f['cfg']['description'] ) !== '' )
			{
				$html .= '<div class="mpcs-description">' . \nl2br( Servers::esc( $f['cfg']['description'] ) ) . '</div>';
				break;
			}
		}

		/* Ostrzeżenie dla moderatorów: archiwizacja nie działa */
		foreach ( $forums as $f )
		{
			if ( $f['mod'] and !$f['target'] and $f['error'] !== NULL )
			{
				$html .= '<p class="mpcs-warn ipsMessage ipsMessage--warning">' . Servers::esc( Servers::lang( 'mpcs_list_archive_problem', 'Publikacje z działu „%s” nie trafiają do archiwum: %s. Popraw to w ACP → System Publikacji Serwerów MPCforum → Działy.', array( $f['forum']->_title, $f['error'] ) ) ) . '</p>';
			}
		}

		/* Panel: filtry i narzędzia */
		$html .= '<div class="mpcs-panel i-background_2">';
		foreach ( $groups as $g => $group )
		{
			$labelId = $listId . '_g_' . $g;
			$html .= '<div class="mpcs-filters" role="group" aria-labelledby="' . Servers::esc( $labelId ) . '"><strong id="' . Servers::esc( $labelId ) . '">' . Servers::esc( $group['name'] ) . '</strong>';
			foreach ( $group['options'] as $o => $label )
			{
				$on = ( isset( $filters[ $g ] ) and \in_array( (string) $o, $filters[ $g ], TRUE ) );
				$next = $filters;
				if ( $on )
				{
					$next[ $g ] = \array_values( \array_diff( $next[ $g ], array( (string) $o ) ) );
					if ( !$next[ $g ] )
					{
						unset( $next[ $g ] );
					}
				}
				else
				{
					$next[ $g ][] = (string) $o;
				}
				$n = isset( $counts[ $g ][ $o ] ) ? $counts[ $g ][ $o ] : 0;
				$html .= '<a class="mpcs-chip ipsButton ipsButton--tiny' . ( $on ? 'ipsButton--primary' : 'ipsButton--inherit' ) . ( ( !$n and !$on ) ? ' mpcs-chip-zero' : '' ) . '" ' . ( $on ? 'aria-current="true" ' : '' ) . 'href="' . $link( array( 'f' => static::serializeFilters( $next ), 'page' => 1 ) ) . '" rel="nofollow">'
					. Servers::esc( $label ) . ' <span class="mpcs-n" aria-hidden="true">' . Servers::number( $n ) . '</span>'
					. '<span class="mpcs-sr">' . Servers::esc( ', ' . $n . ' ' . Servers::plural( $n, 'serwer', 'serwery', 'serwerów' ) ) . '</span></a>';
			}
			$html .= '</div>';
		}

		$sortLabels = array(
			'refresh'	=> Servers::lang( 'mpcs_sort_refresh', 'Ostatnio odświeżone' ),
			'new'		=> Servers::lang( 'mpcs_sort_new', 'Najnowsze publikacje' ),
			'old'		=> Servers::lang( 'mpcs_sort_old', 'Najstarsze publikacje' ),
			'az'		=> Servers::lang( 'mpcs_sort_az', 'Nazwa A-Z' ),
		);
		$action = $host->url()->setQueryString( array( $key . 'f' => static::serializeFilters( $filters ), $key . 'page' => 1 ) );
		$html .= '<div class="mpcs-tools" data-action="' . Servers::esc( (string) $action ) . '" data-key="' . Servers::esc( $key ) . '">'
			. '<label class="mpcs-search">' . Servers::esc( Servers::lang( 'mpcs_search', 'Szukaj' ) ) . '<input type="search" name="' . Servers::esc( $key ) . 'q" value="' . Servers::esc( $q ) . '" placeholder="' . Servers::esc( Servers::lang( 'mpcs_search_placeholder', 'Nazwa serwera' ) ) . '" maxlength="100"></label>'
			. '<label>' . Servers::esc( Servers::lang( 'mpcs_sort', 'Sortuj' ) ) . '<select name="' . Servers::esc( $key ) . 'sort">';
		foreach ( $sortLabels as $value => $label )
		{
			$html .= '<option value="' . $value . '"' . ( $sort === $value ? ' selected' : '' ) . '>' . Servers::esc( $label ) . '</option>';
		}
		$html .= '</select></label>'
			. '<button type="button" class="mpcs-button ipsButton ipsButton--primary ipsButton--small">' . Servers::esc( Servers::lang( 'mpcs_apply', 'Zastosuj' ) ) . '</button>';
		if ( $anyFilter )
		{
			$html .= '<a class="mpcs-reset" href="' . $link( array( 'q' => '', 'f' => '', 'page' => 1 ) ) . '" rel="nofollow">' . Servers::esc( Servers::lang( 'mpcs_reset', 'Wyczyść filtry' ) ) . '</a>';
		}
		$html .= '</div></div>';

		/* Liczba wyników */
		$found = \count( $matches );
		$countText = $anyFilter
			? Servers::lang( 'mpcs_count_filtered', 'Pasuje %s z %s %s', array( Servers::number( $found ), Servers::number( $total ), Servers::plural( $total, 'serwera', 'serwerów', 'serwerów' ) ) )
			: Servers::lang( 'mpcs_count', '%s %s', array( Servers::number( $found ), Servers::plural( $found, 'serwer', 'serwery', 'serwerów' ) ) );
		$html .= '<p class="mpcs-count">' . Servers::esc( $countText ) . '</p>';

		/* Wiersze */
		$html .= '<ol class="mpcs-rows">';
		foreach ( \array_slice( $matches, ( $page - 1 ) * $size, $size, TRUE ) as $tid => $item )
		{
			$html .= static::rowHtml( $item, $groups, $filters, $forums[ (int) $item['topic']->forum_id ], $now );
		}
		$html .= '</ol>';

		if ( !$matches )
		{
			if ( $total )
			{
				$html .= '<div class="mpcs-empty"><p>' . Servers::esc( Servers::lang( 'mpcs_empty', 'Żaden serwer nie spełnia wybranych kryteriów.' ) ) . '</p>'
					. '<a class="ipsButton ipsButton--inherit ipsButton--small" href="' . $link( array( 'q' => '', 'f' => '', 'page' => 1 ) ) . '" rel="nofollow">' . Servers::esc( Servers::lang( 'mpcs_reset', 'Wyczyść filtry' ) ) . '</a></div>';
			}
			else
			{
				$html .= '<div class="mpcs-empty"><p>' . Servers::esc( Servers::lang( 'mpcs_empty_forum', 'W tym dziale nie ma jeszcze publikacji.' ) ) . '</p></div>';
			}
		}

		/* Stronicowanie */
		if ( $pages > 1 )
		{
			$html .= '<nav class="mpcs-pages" aria-label="' . Servers::esc( Servers::lang( 'mpcs_pages', 'Strony spisu' ) ) . '">';
			if ( $page > 1 )
			{
				$html .= '<a href="' . $link( array( 'page' => $page - 1 ) ) . '" rel="nofollow">' . Servers::esc( Servers::lang( 'mpcs_prev', 'Poprzednia' ) ) . '</a>';
			}
			$html .= '<span>' . Servers::esc( Servers::lang( 'mpcs_page_of', 'Strona %d z %d', array( $page, $pages ) ) ) . '</span>';
			if ( $page < $pages )
			{
				$html .= '<a href="' . $link( array( 'page' => $page + 1 ) ) . '" rel="nofollow">' . Servers::esc( Servers::lang( 'mpcs_next', 'Następna' ) ) . '</a>';
			}
			$html .= '</nav>';
		}

		return $html . '<span class="mpcs-status" data-mpcs-status role="status" aria-live="polite"></span></section>';
	}

	/**
	 * Jeden wiersz spisu
	 *
	 * @param	array	$item
	 * @param	array	$groups		Połączone grupy spisu
	 * @param	array	$filters	Aktywne filtry (wyróżnione prefiksy)
	 * @param	array	$f			Dział: forum, cfg, target, mod
	 * @param	int		$now
	 * @return	string
	 */
	protected static function rowHtml( array $item, array $groups, array $filters, array $f, $now )
	{
		$topic = $item['topic'];

		/* Prefiksy w kolejności grup, wybrane w filtrach wyróżnione */
		$prefixes = array();
		foreach ( $groups as $g => $group )
		{
			if ( empty( $item['attrs'][ $g ] ) )
			{
				continue;
			}
			foreach ( $group['options'] as $o => $label )
			{
				if ( \in_array( (string) $o, $item['attrs'][ $g ], TRUE ) )
				{
					$hit = ( isset( $filters[ $g ] ) and \in_array( (string) $o, $filters[ $g ], TRUE ) );
					$prefixes[] = '<span class="mpcs-p' . ( $hit ? ' mpcs-hit' : '' ) . '" title="' . Servers::esc( $group['name'] ) . '">' . Servers::esc( $label ) . '</span>';
				}
			}
		}
		$prefixHtml = $prefixes ? '<div class="mpcs-prefixes">' . \implode( '<span class="mpcs-sep" aria-hidden="true">/</span>', $prefixes ) . '</div>' : '';

		/* Autor: natywne zdjęcie i link z podglądem profilu po najechaniu */
		$author = $topic->author();
		if ( $author and $author->member_id )
		{
			$global = \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' );
			$authorHtml = '<div class="mpcs-author">' . $global->userPhoto( $author, 'tiny' ) . '<span class="mpcs-author-name">' . $global->userLink( $author ) . '</span></div>';
		}
		else
		{
			$authorHtml = '<div class="mpcs-author"><span class="mpcs-author-name">' . Servers::esc( $topic->starter_name ) . '</span></div>';
		}

		/* Odświeżono (albo dodano, gdy temat nie był jeszcze odświeżany) */
		$refreshed = ( (int) $topic->last_post > (int) $topic->start_date + 60 );
		$dateHtml = '<div class="mpcs-date"><small>' . Servers::esc( $refreshed ? Servers::lang( 'mpcs_refreshed', 'Odświeżono' ) : Servers::lang( 'mpcs_added', 'Dodano' ) ) . '</small>'
			. Servers::date( $refreshed ? $topic->last_post : $topic->start_date );
		if ( $f['mod'] and $item['deadline'] !== NULL and $item['deadline'] - $now <= 7 * 86400 )
		{
			$dateHtml .= '<small class="mpcs-exp">' . Servers::esc( Servers::lang( 'mpcs_exp', 'Do archiwum: %s', array( Servers::dateTime( $item['deadline'] ) ) ) ) . '</small>';
		}
		$dateHtml .= '</div>';

		return '<li class="mpcs-row i-border-bottom_3">'
			. '<div class="mpcs-publication"><a class="mpcs-title" href="' . Servers::esc( (string) $topic->url() ) . '">' . Servers::esc( $topic->title ) . '</a>' . $prefixHtml . '</div>'
			. $authorHtml
			. $dateHtml
			. '</li>';
	}
}
