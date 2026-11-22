<?php
/**
 * System Publikacji Serwerów MPCforum - rdzeń aplikacji
 *
 * Konfiguracja działów, prefiksy tematów (z limitem wyborów w grupie),
 * uprawnienia, pole prefiksów w formularzu tematu, znaczki i pomocnicze.
 */

namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\OrdinaryTopic;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Servers
{
	/**
	 * @brief	Pamięć podręczna na czas jednego żądania
	 */
	protected static $forumCache = NULL;
	protected static $attrCache = array();
	protected static $preloaded = array();
	protected static $wordCache = NULL;
	protected static $assetsDone = array();

	/**
	 * @brief	Kolory grup prefiksów (biały tekst ma na nich kontrast co najmniej 4,5 : 1)
	 */
	public static $palette = array( '#1d4ed8', '#047857', '#b45309', '#6d28d9', '#0e7490', '#be185d', '#4b5563', '#4d7c0f', '#b91c1c', '#1e3a8a' );

	/**
	 * Wyczyść pamięć podręczną (po zapisie konfiguracji i w testach)
	 *
	 * @return	void
	 */
	public static function resetCache()
	{
		static::$forumCache = NULL;
		static::$attrCache = array();
		static::$preloaded = array();
		static::$assetsDone = array();
	}

	/* ===================================================================
	 *  Współpraca ze starą wtyczką
	 * =================================================================== */

	/**
	 * Czy stara wtyczka „Spis publikacji serwerów” nadal działa
	 *
	 * Dopóki jej hooki są załadowane, aplikacja nic nie robi na forum,
	 * żeby pola prefiksów i spisy nie pojawiały się podwójnie.
	 *
	 * @return	bool
	 */
	public static function pluginActive()
	{
		return \method_exists( 'IPS\forums\Topic', 'srvpubBadges' );
	}

	/**
	 * Czy w bazie są tabele starej wtyczki
	 *
	 * @return	bool
	 */
	public static function pluginTablesExist()
	{
		try
		{
			return \IPS\Db::i()->checkForTable( 'srvpub_forums' );
		}
		catch ( \Throwable $e )
		{
			return FALSE;
		}
	}

	/**
	 * Przenieś konfigurację działów, prefiksy tematów i dziennik ze starej wtyczki
	 *
	 * Można uruchamiać wielokrotnie: wiersze o tych samych kluczach są nadpisywane.
	 *
	 * @return	array	array( 'forums' => n, 'topics' => n, 'log' => n )
	 */
	public static function importFromPlugin()
	{
		$db = \IPS\Db::i();
		$stats = array( 'forums' => 0, 'topics' => 0, 'log' => 0 );
		if ( !static::pluginTablesExist() )
		{
			return $stats;
		}

		foreach ( $db->select( '*', 'srvpub_forums' ) as $row )
		{
			$db->replace( 'mpcservers_forums', array(
				'forum_id'		=> (int) $row['forum_id'],
				'enabled'		=> (int) $row['enabled'],
				'description'	=> '',
				'defs'			=> (string) $row['defs'],
				'archive_id'	=> (int) $row['archive_id'],
				'days'			=> (int) $row['days'],
				'activity'		=> ( isset( $row['activity'] ) and $row['activity'] === 'author' ) ? 'author' : 'refresh',
				'lock_moved'	=> (int) $row['lock_moved'],
				'skip_pinned'	=> (int) $row['skip_pinned'],
				'since'			=> (int) $row['since'],
				'updated'		=> \time(),
				'updated_by'	=> (int) \IPS\Member::loggedIn()->member_id,
				'checked'		=> 0,
			) );
			$stats['forums']++;
		}

		if ( $db->checkForTable( 'srvpub_topics' ) )
		{
			foreach ( $db->select( '*', 'srvpub_topics' ) as $row )
			{
				$db->replace( 'mpcservers_topics', array(
					'tid'		=> (int) $row['tid'],
					'forum_id'	=> (int) $row['forum_id'],
					'attrs'		=> (string) $row['attrs'],
					'updated'	=> (int) $row['updated'],
					'restored'	=> 0,
				) );
				$stats['topics']++;
			}
		}

		if ( $db->checkForTable( 'srvpub_log' ) )
		{
			$db->delete( 'mpcservers_log', array( 'imported=?', 1 ) );
			foreach ( $db->select( '*', 'srvpub_log', NULL, 'id ASC' ) as $row )
			{
				$db->insert( 'mpcservers_log', array(
					'tid'			=> (int) $row['tid'],
					'title'			=> (string) $row['title'],
					'forum_from'	=> (int) $row['forum_from'],
					'forum_to'		=> (int) $row['forum_to'],
					'author_id'		=> (int) $row['author_id'],
					'last_activity'	=> (int) $row['last_activity'],
					'moved_at'		=> (int) $row['moved_at'],
					'imported'		=> 1,
				) );
				$stats['log']++;
			}
		}

		$default = (int) \IPS\Settings::i()->srvpub_default_archive;
		if ( $default and !(int) \IPS\Settings::i()->mpcs_default_archive )
		{
			\IPS\Settings::i()->changeValues( array( 'mpcs_default_archive' => $default ) );
		}

		static::resetCache();
		return $stats;
	}

	/* ===================================================================
	 *  Definicje prefiksów
	 * =================================================================== */

	/**
	 * Klucz z nazwy: małe litery ASCII, cyfry i podkreślniki
	 *
	 * @param	string	$text
	 * @return	string
	 */
	public static function slug( $text )
	{
		$text = \mb_strtolower( \trim( (string) $text ), 'UTF-8' );
		$text = \strtr( $text, array( 'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z' ) );
		$text = \preg_replace( '/[^a-z0-9]+/', '_', $text );
		return \substr( \trim( $text, '_' ), 0, 40 );
	}

	/**
	 * Rozbierz definicję prefiksów działu
	 *
	 * Format (jedna grupa w linii):
	 *   Wersja: Oldschool | Middleschool | Newschool
	 *   Typ (kilka): PvP | PvM | Easy | Hard
	 *   Tryb (max 2): Survival | SkyBlock | BoxPvP
	 *   # komentarz
	 *
	 * @param	string	$text
	 * @return	array	array( 'groups' => array( klucz => array( key, name, multi, max, options ) ), 'errors' => array( tekst ) )
	 */
	public static function parseDefs( $text )
	{
		$groups = array();
		$errors = array();

		foreach ( \preg_split( '/\r\n|\r|\n/', (string) $text ) as $i => $line )
		{
			$no = $i + 1;
			$line = \trim( $line );
			if ( $line === '' or $line[0] === '#' or \substr( $line, 0, 2 ) === '//' )
			{
				continue;
			}

			$pos = \mb_strpos( $line, ':' );
			if ( $pos === FALSE )
			{
				$errors[] = static::lang( 'mpcs_err_colon', 'Linia %d: brak dwukropka. Wzór: Nazwa grupy: opcja | opcja | opcja', array( $no ) );
				continue;
			}

			$name = \trim( \mb_substr( $line, 0, $pos ) );
			$rest = \trim( \mb_substr( $line, $pos + 1 ) );
			$multi = FALSE;
			$max = 0;

			if ( \mb_substr( $name, -1 ) === '+' )
			{
				$multi = TRUE;
				$name = \trim( \mb_substr( $name, 0, -1 ) );
			}
			/* Dopisek w nawiasie: (kilka), (max 2), (kilka, max 2). Inne nawiasy są częścią nazwy. */
			if ( \preg_match( '/^(.*?)\s*\(([^()]*)\)$/u', $name, $m ) )
			{
				$known = TRUE;
				$mMax = NULL;
				foreach ( \preg_split( '/\s*[,;]\s*/', \trim( $m[2] ) ) as $token )
				{
					$token = \mb_strtolower( \trim( $token ) );
					if ( \in_array( $token, array( 'kilka', 'wiele', 'multi' ), TRUE ) )
					{
						continue;
					}
					if ( \preg_match( '/^(?:max|maks\.?|do|limit)\s*(\d{1,3})$/u', $token, $mm ) )
					{
						$mMax = (int) $mm[1];
						continue;
					}
					$known = FALSE;
				}
				if ( $known )
				{
					if ( $mMax === 0 )
					{
						$errors[] = static::lang( 'mpcs_err_max_zero', 'Linia %d: limit wyborów musi wynosić co najmniej 1.', array( $no ) );
						continue;
					}
					$name = \trim( $m[1] );
					$multi = TRUE;
					$max = (int) $mMax;
				}
			}

			$key = static::slug( $name );
			if ( $name === '' or $key === '' )
			{
				$errors[] = static::lang( 'mpcs_err_name', 'Linia %d: nazwa grupy musi zawierać litery lub cyfry.', array( $no ) );
				continue;
			}
			if ( \mb_strlen( $name ) > 40 )
			{
				$errors[] = static::lang( 'mpcs_err_name_long', 'Linia %d: nazwa grupy może mieć najwyżej 40 znaków.', array( $no ) );
				continue;
			}
			if ( isset( $groups[ $key ] ) )
			{
				$errors[] = static::lang( 'mpcs_err_dup_group', 'Linia %d: grupa „%s” jest już zdefiniowana.', array( $no, $name ) );
				continue;
			}

			$parts = ( \mb_strpos( $rest, '|' ) !== FALSE ) ? \explode( '|', $rest ) : \explode( ',', $rest );
			$options = array();
            $colors = array();
			$bad = FALSE;
			foreach ( $parts as $part )
			{
				$label = \trim( $part );
                $customColor=NULL;if(preg_match('/\s*\{(#[a-f0-9]{6})\}$/iD',$label,$cm)){$customColor=strtolower($cm[1]);$label=trim(substr($label,0,-strlen($cm[0])));}
				if ( $label === '' )
				{
					continue;
				}
				$okey = static::slug( $label );
				if ( $okey === '' )
				{
					$errors[] = static::lang( 'mpcs_err_option', 'Linia %d: opcja „%s” musi zawierać litery lub cyfry.', array( $no, $label ) );
					$bad = TRUE;
					continue;
				}
				if ( \mb_strlen( $label ) > 40 )
				{
					$errors[] = static::lang( 'mpcs_err_option_long', 'Linia %d: opcja „%s” jest dłuższa niż 40 znaków.', array( $no, \mb_substr( $label, 0, 20 ) . '…' ) );
					$bad = TRUE;
					continue;
				}
				if ( isset( $options[ $okey ] ) )
				{
					$errors[] = static::lang( 'mpcs_err_dup_option', 'Linia %d: opcja „%s” powtarza się.', array( $no, $label ) );
					$bad = TRUE;
					continue;
				}
				$options[ $okey ] = $label;
                if($customColor)$colors[$okey]=$customColor;
			}

			if ( !\count( $options ) )
			{
				if ( !$bad )
				{
					$errors[] = static::lang( 'mpcs_err_no_options', 'Linia %d: grupa „%s” nie ma żadnych opcji.', array( $no, $name ) );
				}
				continue;
			}
			if ( \count( $options ) > 50 )
			{
				$errors[] = static::lang( 'mpcs_err_many_options', 'Linia %d: grupa może mieć najwyżej 50 opcji.', array( $no ) );
				continue;
			}

			/* Limit 1 = zwykły pojedynczy wybór; limit >= liczba opcji = bez limitu */
			if ( $multi and $max === 1 )
			{
				$multi = FALSE;
				$max = 0;
			}
			if ( !$multi or $max >= \count( $options ) )
			{
				$max = 0;
			}

			$groups[ $key ] = array( 'key' => $key, 'name' => $name, 'multi' => $multi, 'max' => $max, 'options' => $options, 'colors' => $colors );
		}

		return array( 'groups' => $groups, 'errors' => $errors );
	}

	/**
	 * Opis grupy do tabel w ACP, np. „Typ (najwyżej 2 z 6)”
	 *
	 * @param	array	$group
	 * @return	string
	 */
	public static function groupSummary( array $group )
	{
		$n = \count( $group['options'] );
		if ( !$group['multi'] )
		{
			return static::lang( 'mpcs_sum_single', '%s (1 z %d)', array( $group['name'], $n ) );
		}
		if ( $group['max'] )
		{
			return static::lang( 'mpcs_sum_max', '%s (najwyżej %d z %d)', array( $group['name'], $group['max'], $n ) );
		}
		return static::lang( 'mpcs_sum_multi', '%s (dowolnie z %d)', array( $group['name'], $n ) );
	}

	/* ===================================================================
	 *  Konfiguracja działów
	 * =================================================================== */

	/**
	 * Wszystkie skonfigurowane działy (także wyłączone)
	 *
	 * @param	bool	$reload
	 * @return	array	forum_id => wiersz + 'groups'
	 */
	public static function forums( $reload = FALSE )
	{
		if ( static::$forumCache === NULL or $reload )
		{
			static::$forumCache = array();
			try
			{
				foreach ( \IPS\Db::i()->select( '*', 'mpcservers_forums' ) as $row )
				{
					$parsed = static::parseDefs( $row['defs'] );
					$row['groups'] = $parsed['groups'];
                    $paletteIndex=0;foreach($row['groups'] as &$modeGroup){$modeGroup['_palette_index']=$paletteIndex++;if(ServerModes::isGroup($modeGroup)){$modeGroup['multi']=TRUE;$modeGroup['max']=0;}}
                    unset($modeGroup);
                    uasort($row['groups'],function($a,$b){return (int)ServerModes::isGroup($b)-(int)ServerModes::isGroup($a);});
					static::$forumCache[ (int) $row['forum_id'] ] = $row;
				}
			}
			catch ( \Throwable $e )
			{
				static::logError( $e );
			}
		}
		return static::$forumCache;
	}

	/**
	 * Konfiguracja działu (włączonego lub nie)
	 *
	 * @param	int	$forumId
	 * @return	array|NULL
	 */
	public static function forum( $forumId )
	{
		$forums = static::forums();
		return isset( $forums[ (int) $forumId ] ) ? $forums[ (int) $forumId ] : NULL;
	}

	/**
	 * Konfiguracja działu, jeśli jest włączonym działem publikacji
	 *
	 * @param	int	$forumId
	 * @return	array|NULL
	 */
	public static function enabledForum( $forumId )
	{
		$cfg = static::forum( $forumId );
		return ( $cfg and !empty( $cfg['enabled'] ) ) ? $cfg : NULL;
	}

	/**
	 * Czy dział jest archiwum dla któregoś działu publikacji
	 *
	 * @param	int	$forumId
	 * @return	bool
	 */
	public static function isArchive( $forumId )
	{
		$forumId = (int) $forumId;
		$default = (int) \IPS\Settings::i()->mpcs_default_archive;
		foreach ( static::forums() as $cfg )
		{
			if ( empty( $cfg['enabled'] ) )
			{
				continue;
			}
			$target = (int) $cfg['archive_id'] ?: $default;
			if ( $target === $forumId )
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Zapisz konfigurację działu
	 *
	 * @param	int		$forumId
	 * @param	array	$data	Kolumny tabeli mpcservers_forums
	 * @return	void
	 */
	public static function saveForum( $forumId, array $data )
	{
		$forumId = (int) $forumId;
		$old = static::forum( $forumId );
		$row = array(
			'forum_id'		=> $forumId,
			'enabled'		=> 1,
			'description'	=> '',
			'defs'			=> '',
			'archive_id'	=> 0,
			'days'			=> 30,
			'activity'		=> 'refresh',
			'lock_moved'	=> 1,
			'skip_pinned'	=> 1,
			'since'			=> \time(),
			'updated'		=> \time(),
			'updated_by'	=> (int) \IPS\Member::loggedIn()->member_id,
			'checked'		=> 0,
		);
		if ( $old )
		{
			foreach ( $row as $k => $v )
			{
				if ( \array_key_exists( $k, $old ) and !\in_array( $k, array( 'updated', 'updated_by', 'checked' ), TRUE ) )
				{
					$row[ $k ] = $old[ $k ];
				}
			}
		}
		foreach ( $data as $k => $v )
		{
			if ( \array_key_exists( $k, $row ) and !\in_array( $k, array( 'forum_id', 'since' ), TRUE ) )
			{
				$row[ $k ] = $v;
			}
		}
		$row['activity'] = ( $row['activity'] === 'author' ) ? 'author' : 'refresh';

		/* Data pierwszego włączenia: aplikacja obejmuje tylko tematy założone od tej chwili */
		if ( !$old or !(int) $old['since'] )
		{
			$row['since'] = \time();
		}

		\IPS\Db::i()->replace( 'mpcservers_forums', $row );
		static::forums( TRUE );
        PublicationTags::enforceForums();
	}

	/**
	 * Usuń konfigurację działu (prefiksy tematów zostają w bazie)
	 *
	 * @param	int	$forumId
	 * @return	void
	 */
	public static function deleteForum( $forumId )
	{
		\IPS\Db::i()->delete( 'mpcservers_forums', array( 'forum_id=?', (int) $forumId ) );
		static::forums( TRUE );
        PublicationTags::enforceForums();
	}

	/* ===================================================================
	 *  Prefiksy tematów
	 * =================================================================== */

	/**
	 * Zostaw tylko opcje istniejące w definicji, z limitem wyborów
	 *
	 * @param	array	$attrs
	 * @param	array	$groups
	 * @return	array
	 */
	public static function cleanAttrs( array $attrs, array $groups )
	{
		$clean = array();
		foreach ( $groups as $key => $group )
		{
			if ( empty( $attrs[ $key ] ) )
			{
				continue;
			}
			$chosen = \array_map( 'strval', (array) $attrs[ $key ] );
			$list = array();
			foreach ( $group['options'] as $okey => $label )
			{
				if ( \in_array( (string) $okey, $chosen, TRUE ) )
				{
					$list[] = (string) $okey;
				}
			}
			if ( !$group['multi'] )
			{
				$list = \array_slice( $list, 0, 1 );
			}
			elseif ( !empty( $group['max'] ) )
			{
				$list = \array_slice( $list, 0, (int) $group['max'] );
			}
			if ( $list )
			{
				$clean[ (string) $key ] = $list;
			}
		}
		return $clean;
	}

	/**
	 * Wartość pola prefiksów z formularza -> klucz grupy => [klucze opcji]
	 *
	 * @param	mixed	$value
	 * @return	array
	 */
	public static function normalizePicked( $value )
	{
		$out = array();
		if ( !\is_array( $value ) )
		{
			return $out;
		}
		foreach ( $value as $group => $list )
		{
			if ( (string) $group === '__sent' )
			{
				continue;
			}
			foreach ( (array) $list as $option )
			{
				if ( \is_scalar( $option ) and (string) $option !== '' )
				{
					$out[ (string) $group ][] = (string) $option;
				}
			}
		}
		return $out;
	}

	/**
	 * Sprawdź wybór prefiksów z formularza
	 *
	 * @param	mixed	$value		Wartość pola
	 * @param	array	$groups		Grupy działu
	 * @param	bool	$required	Czy grupy są wymagane
	 * @return	string|NULL	Komunikat błędu
	 */
	public static function validatePicked( $value, array $groups, $required )
	{
		$picked = static::normalizePicked( $value );

		/* Limit wyborów liczony przed czyszczeniem, żeby nie przycinać po cichu */
		$over = array();
		foreach ( $groups as $key => $group )
		{
			if ( $group['multi'] and !empty( $group['max'] ) and isset( $picked[ $key ] ) )
			{
				$valid = 0;
				foreach ( \array_unique( $picked[ $key ] ) as $o )
				{
					if ( isset( $group['options'][ $o ] ) )
					{
						$valid++;
					}
				}
				if ( $valid > (int) $group['max'] )
				{
					$over[] = static::lang( 'mpcs_err_over', '%s: wybierz najwyżej %d', array( $group['name'], (int) $group['max'] ) );
				}
			}
		}
		if ( $over )
		{
			return \implode( '. ', $over ) . '.';
		}

		if ( !$required )
		{
			return NULL;
		}
		$clean = static::cleanAttrs( $picked, $groups );
		$missing = array();
		foreach ( $groups as $key => $group )
		{
			if ( empty( $clean[ $key ] ) )
			{
				$missing[] = $group['name'];
			}
		}
		return $missing ? static::lang( 'mpcs_err_pick', 'Wybierz prefiksy: %s.', array( \implode( ', ', $missing ) ) ) : NULL;
	}

	/**
	 * Zapisz prefiksy tematu (oczyszczone wg definicji działu)
	 *
	 * @param	int		$tid
	 * @param	int		$forumId
	 * @param	array	$attrs
	 * @return	bool	Czy coś zapisano
	 */
	public static function storeAttrs( $tid, $forumId, array $attrs )
	{
		$cfg = static::enabledForum( $forumId );
		if ( !$tid or !$cfg )
		{
			return FALSE;
		}

		$clean = static::cleanAttrs( $attrs, $cfg['groups'] );
		$old = static::attrRow( $tid );
		$encoded = \json_encode( $clean );

		if ( $old and \json_encode( static::cleanAttrs( $old['attrs'], $cfg['groups'] ) ) === $encoded and (int) $old['forum_id'] === (int) $forumId )
		{
			return FALSE;
		}

		\IPS\Db::i()->replace( 'mpcservers_topics', array(
			'tid'		=> (int) $tid,
			'forum_id'	=> (int) $forumId,
			'attrs'		=> $encoded,
			'updated'	=> \time(),
			'restored'	=> $old ? (int) $old['restored'] : 0,
		) );
		static::$attrCache[ (int) $tid ] = array( 'tid' => (int) $tid, 'forum_id' => (int) $forumId, 'attrs' => $clean, 'updated' => \time(), 'restored' => $old ? (int) $old['restored'] : 0 );
		return TRUE;
	}

	/**
	 * Wiersz prefiksów tematu
	 *
	 * @param	int			$tid
	 * @param	int|NULL	$forumId	Dział, w którym temat jest teraz (pozwala wczytać cały dział jednym zapytaniem)
	 * @return	array|NULL	array( tid, forum_id, attrs, updated, restored )
	 */
	public static function attrRow( $tid, $forumId = NULL )
	{
		$tid = (int) $tid;
		if ( !$tid )
		{
			return NULL;
		}
		if ( \array_key_exists( $tid, static::$attrCache ) )
		{
			return static::$attrCache[ $tid ];
		}

		if ( $forumId )
		{
			$forumId = (int) $forumId;
			if ( !isset( static::$preloaded[ $forumId ] ) )
			{
				/* Najnowsze tematy działu jednym zapytaniem (lista tematów jest sortowana od najnowszych) */
				$count = 0;
				foreach ( \IPS\Db::i()->select( 's.*', array( 'mpcservers_topics', 's' ), array( 't.forum_id=?', $forumId ), 't.last_post DESC', array( 0, 1000 ) )->join( array( 'forums_topics', 't' ), 't.tid=s.tid', 'INNER' ) as $row )
				{
					static::$attrCache[ (int) $row['tid'] ] = static::decodeRow( $row );
					$count++;
				}
				static::$preloaded[ $forumId ] = ( $count < 1000 );
			}
			if ( \array_key_exists( $tid, static::$attrCache ) )
			{
				return static::$attrCache[ $tid ];
			}
			if ( static::$preloaded[ $forumId ] )
			{
				static::$attrCache[ $tid ] = NULL;
				return NULL;
			}
		}

		try
		{
			static::$attrCache[ $tid ] = static::decodeRow( \IPS\Db::i()->select( '*', 'mpcservers_topics', array( 'tid=?', $tid ) )->first() );
		}
		catch ( \UnderflowException $e )
		{
			static::$attrCache[ $tid ] = NULL;
		}
		return static::$attrCache[ $tid ];
	}

	/**
	 * Prefiksy wielu tematów naraz
	 *
	 * @param	array	$tids
	 * @return	array	tid => wiersz
	 */
	public static function attrRows( array $tids )
	{
		$out = array();
		$missing = array();
		foreach ( $tids as $tid )
		{
			$tid = (int) $tid;
			if ( \array_key_exists( $tid, static::$attrCache ) )
			{
				if ( static::$attrCache[ $tid ] )
				{
					$out[ $tid ] = static::$attrCache[ $tid ];
				}
			}
			elseif ( $tid )
			{
				$missing[] = $tid;
			}
		}
		foreach ( \array_chunk( $missing, 500 ) as $chunk )
		{
			foreach ( $chunk as $tid )
			{
				static::$attrCache[ $tid ] = NULL;
			}
			foreach ( \IPS\Db::i()->select( '*', 'mpcservers_topics', array( \IPS\Db::i()->in( 'tid', $chunk ) ) ) as $row )
			{
				$decoded = static::decodeRow( $row );
				static::$attrCache[ (int) $row['tid'] ] = $decoded;
				$out[ (int) $row['tid'] ] = $decoded;
			}
		}
		return $out;
	}

	/**
	 * Zapomnij wiersz tematu w pamięci podręcznej
	 *
	 * @param	int	$tid
	 * @return	void
	 */
	public static function forgetAttrRow( $tid )
	{
		unset( static::$attrCache[ (int) $tid ] );
	}

	/**
	 * @param	array	$row
	 * @return	array
	 */
	protected static function decodeRow( $row )
	{
		$attrs = \json_decode( (string) $row['attrs'], TRUE );
		return array(
			'tid'		=> (int) $row['tid'],
			'forum_id'	=> (int) $row['forum_id'],
			'attrs'		=> \is_array( $attrs ) ? $attrs : array(),
			'updated'	=> (int) $row['updated'],
			'restored'	=> isset( $row['restored'] ) ? (int) $row['restored'] : 0,
		);
	}

	/* ===================================================================
	 *  Uprawnienia
	 * =================================================================== */

	/**
	 * @param	\IPS\Member	$member
	 * @return	array	ID grup (główna + dodatkowe)
	 */
	public static function groupsOf( $member )
	{
		$groups = array( (int) $member->member_group_id );
		foreach ( \explode( ',', (string) $member->mgroup_others ) as $g )
		{
			if ( (int) $g )
			{
				$groups[] = (int) $g;
			}
		}
		return $groups;
	}

	/**
	 * @param	\IPS\Member		$member
	 * @param	string|array	$allowed	ID grup (CSV albo tablica)
	 * @return	bool
	 */
	public static function inGroups( $member, $allowed )
	{
		if ( !\is_array( $allowed ) )
		{
			$allowed = \explode( ',', (string) $allowed );
		}
		$allowed = \array_filter( \array_map( 'intval', $allowed ) );
		return (bool) \array_intersect( static::groupsOf( $member ), $allowed );
	}

	/**
	 * Administrator albo moderator globalny
	 *
	 * @param	\IPS\Member	$member
	 * @return	bool
	 */
	public static function isStaff( $member )
	{
		if ( !$member->member_id )
		{
			return FALSE;
		}
		if ( \method_exists( $member, 'isAdmin' ) and $member->isAdmin() )
		{
			return TRUE;
		}
		return ( \method_exists( $member, 'modPermission' ) and $member->modPermission() !== FALSE );
	}

	/**
	 * Czy użytkownik moderuje dział
	 *
	 * @param	\IPS\Member			$member
	 * @param	\IPS\forums\Forum	$forum
	 * @param	string				$permission
	 * @return	bool
	 */
	public static function isForumMod( $member, $forum, $permission = 'edit' )
	{
		if ( !$member->member_id )
		{
			return FALSE;
		}
		try
		{
			return (bool) \IPS\forums\Topic::modPermission( $permission, $member, $forum );
		}
		catch ( \Throwable $e )
		{
			return FALSE;
		}
	}

	/**
	 * Czy użytkownik może zapisać temat bez prefiksów (moderator działu, administrator)
	 *
	 * @param	\IPS\Member			$member
	 * @param	\IPS\forums\Forum	$forum
	 * @return	bool
	 */
	public static function canBypassPrefixes( $member, $forum )
	{
		if ( !$member->member_id or !(int) \IPS\Settings::i()->mpcs_mod_bypass )
		{
			return FALSE;
		}
		if ( \method_exists( $member, 'isAdmin' ) and $member->isAdmin() )
		{
			return TRUE;
		}
		return static::isForumMod( $member, $forum, 'edit' );
	}

	/**
	 * Czy autor może wstawiać znacznik [serwery]
	 *
	 * @param	\IPS\Member	$member
	 * @return	bool
	 */
	public static function canUseTag( $member )
	{
		if ( !$member->member_id )
		{
			return FALSE;
		}
		if ( \method_exists( $member, 'isAdmin' ) and $member->isAdmin() )
		{
			return TRUE;
		}
		if ( static::inGroups( $member, \IPS\Settings::i()->mpcs_tag_groups ) )
		{
			return TRUE;
		}
		return ( (int) \IPS\Settings::i()->mpcs_tag_mods and \method_exists( $member, 'modPermission' ) and $member->modPermission() !== FALSE );
	}

	/* ===================================================================
	 *  Pole prefiksów w formularzu tematu
	 * =================================================================== */

	/**
	 * Pole prefiksów dla formularza tematu albo NULL
	 *
	 * @param	\IPS\forums\Topic|NULL	$item
	 * @param	\IPS\forums\Forum|NULL	$forum
	 * @return	\IPS\Helpers\Form\FormAbstract|NULL
	 */
	public static function formElement( $item, $forum )
	{
        if ($item && (Presentation::isDirectory($item) || (OrdinaryTopic::saved($item) && !OrdinaryTopic::allowed($item)))) { return NULL; }
		if ( !$forum and $item )
		{
			$forum = $item->container();
		}
		if ( !$forum )
		{
			return NULL;
		}

		$cfg = static::enabledForum( $forum->_id );
		if ( !$cfg or !\count( $cfg['groups'] ) )
		{
			return NULL;
		}

		/* Tematy sprzed włączenia działu nie są objęte prefiksami */
		if ( $item and $item->tid and (int) $item->start_date and (int) $item->start_date < (int) $cfg['since'] and empty(Presentation::get($item->tid)['is_server']) and !OrdinaryTopic::allowed($item) )
		{
			return NULL;
		}

		$current = array();
		if ( $item and $item->tid and $row = static::attrRow( $item->tid ) )
		{
			$current = $row['attrs'];
		}

		$required = !static::canBypassPrefixes( \IPS\Member::loggedIn(), $forum );
		$groups = $cfg['groups'];
        foreach ($groups as &$group) { if (strcasecmp(trim($group['name']), 'Easy')===0) { $group['name']='Tryb serwera'; } }
        unset($group);
		$element = new \IPS\Helpers\Form\Custom( 'mpcs_prefixes', $current, FALSE, array(
			'getHtml'	=> function( $element ) use ( $groups, $required ) {
				return \IPS\mpcservers\Servers::pickerHtml( $groups, $element->value, $required );
			},
		), function( $value ) use ( $groups, $required, $item ) {
            if (OrdinaryTopic::selected($item)) { return; }
			$error = \IPS\mpcservers\Servers::validatePicked( $value, $groups, $required );
			if ( $error !== NULL )
			{
				throw new \DomainException( $error );
			}
		} );
		$element->label = static::esc( static::lang( 'mpcs_prefixes_label', 'Prefiksy' ) );
		return $element;
	}

	/**
	 * Zwarte pole prefiksów: każda grupa w jednym rzędzie, opcje jako przyciski
	 *
	 * @param	array	$groups		Grupy z definicji działu
	 * @param	mixed	$value		Obecna wartość (z bazy albo z wysłanego formularza)
	 * @param	bool	$required	Czy grupy są wymagane
	 * @return	string
	 */
	public static function pickerHtml( array $groups, $value, $required )
	{
		$picked = static::normalizePicked( $value );
		$html = static::assets( 'picker' ) . '<div class="mpcs-pickform" data-mpcs-picker><input type="hidden" name="mpcs_prefixes[__sent]" value="1">';
		$i = 0;
		foreach ( $groups as $key => $group )
		{
			$color = static::$palette[ ($group['_palette_index']??$i) % \count( static::$palette ) ];
			$i++;
			$chosen = isset( $picked[ $key ] ) ? $picked[ $key ] : array();
			$type = $group['multi'] ? 'checkbox' : 'radio';
			$name = 'mpcs_prefixes[' . static::esc( $key ) . '][]';
			$labelId = 'mpcs_pick_' . static::esc( $key );

			$hint = '';
			if ( $group['multi'] )
			{
				$hint = $group['max'] ? static::lang( 'mpcs_multi_max', 'najwyżej %d', array( (int) $group['max'] ) ) : static::lang( 'mpcs_multi', 'kilka' );
			}

			$html .= '<div class="mpcs-pickrow" style="--c:' . $color . '" role="group" aria-labelledby="' . $labelId . '"' . ( $group['max'] ? ' data-max="' . (int) $group['max'] . '"' : '' ) . '>'
				. '<span class="mpcs-pickname" id="' . $labelId . '">' . static::esc( $group['name'] )
				. ( $required ? '<span class="mpcs-req" aria-hidden="true">*</span>' : '' )
				. ( $hint !== '' ? ' <small>' . static::esc( $hint ) . '</small>' : '' )
				. '</span><span class="mpcs-picks">';
			if ( !$group['multi'] and !$required )
			{
				$html .= '<label class="mpcs-pick mpcs-pick-none"><input type="radio" name="' . $name . '" value=""' . ( $chosen ? '' : ' checked' ) . '><span>' . static::esc( static::lang( 'mpcs_pick_none', 'brak' ) ) . '</span></label>';
			}
			foreach ( $group['options'] as $okey => $label )
			{
				$on = \in_array( (string) $okey, $chosen, TRUE );
				$html .= '<label class="mpcs-pick" style="--c:' . static::optionColor($group,$okey,$color) . ';--mpcs-text:' . static::foreground(static::optionColor($group,$okey,$color)) . '"><input type="' . $type . '" name="' . $name . '" value="' . static::esc( $okey ) . '"' . ( $on ? ' checked' : '' ) . '><span>' . static::esc( $label ) . '</span></label>';
			}
			$html .= '</span></div>';
		}
		if ( !$required )
		{
			$html .= '<p class="mpcs-pickhint">' . static::esc( static::lang( 'mpcs_pick_bypass', 'Jako moderator możesz zostawić prefiksy puste.' ) ) . '</p>';
		}
		return $html . '</div>';
	}

	/* ===================================================================
	 *  Znaczki prefiksów (lista tematów, nagłówek tematu)
	 * =================================================================== */

	/**
	 * HTML znaczków prefiksów tematu
	 *
	 * @param	\IPS\forums\Topic	$topic
	 * @return	string
	 */
	/**
	 * IPS5: tryby tematu jako odznaki IPS (UIItem::badges - lista tematów i nagłówek tematu)
	 *
	 * @param	\IPS\forums\Topic	$topic
	 * @return	array	\IPS\Helpers\Badge
	 */
	public static function topicBadgeObjects( $topic ): array
	{
		$badges = array();
		try
		{
			if ( static::pluginActive() or OrdinaryTopic::saved( $topic ) or Presentation::isDirectory( $topic ) )
			{
				return $badges;
			}
			$fid = (int) $topic->forum_id;
			$forums = static::forums();
			$enabled = ( isset( $forums[ $fid ] ) and !empty( $forums[ $fid ]['enabled'] ) );
			if ( !$enabled and !static::isArchive( $fid ) )
			{
				return $badges;
			}
            $versionLabel=\IPS\mpcservers\Modules\Minecraft\Versions::topicLabel($topic);
            if($versionLabel!=='')$badges[]=new PrefixBadge($versionLabel,'#047857','',TRUE);
			$row = static::attrRow( $topic->tid, $fid );
			if ( !$row or !$row['attrs'] )
			{
				return $badges;
			}
			$groups = $enabled ? $forums[ $fid ]['groups'] : array();
			$list = static::badgeData( $groups, $row['attrs'] );
			if ( !$list and isset( $forums[ (int) $row['forum_id'] ] ) )
			{
				$list = static::badgeData( $forums[ (int) $row['forum_id'] ]['groups'], $row['attrs'] );
			}
			foreach ( $list as $b )
			{
				$badges[] = new PrefixBadge($b['label'],$b['hex'],$b['rotate_group']);
			}
		}
		catch ( \Throwable $e )
		{
			static::logError( $e );
		}
		return $badges;
	}

	/**
	 * Wybrane opcje grup prefiksów: etykieta i numer koloru palety
	 *
	 * @param	array	$groups
	 * @param	array	$attrs
	 * @return	array
	 */
    public static function foreground(string $hex): string
    {
        if(!preg_match('/^#[a-f0-9]{6}$/iD',$hex))return '#fff';
        return (hexdec(substr($hex,1,2))*299+hexdec(substr($hex,3,2))*587+hexdec(substr($hex,5,2))*114)>=150000?'#111':'#fff';
    }
    public static function optionColor($group,$option,$fallback='#1d4ed8'): string
    {
        $color=$group['colors'][$option]??'';
        return is_string($color)&&preg_match('/^#[a-f0-9]{6}$/iD',$color)?$color:$fallback;
    }

    public static function rotationGroup($key,array $group): string
    {
        return ServerModes::isGroup($group)?'server-modes':'normal-prefixes';
    }

	public static function badgeData( array $groups, array $attrs ): array
	{
		$out = array();
		$i = 0;
		foreach ( $groups as $key => $group )
		{
			$color = ($group['_palette_index']??$i) % \count( static::$palette );
			$i++;
			if ( empty( $attrs[ $key ] ) )
			{
				continue;
			}
			$chosen = \array_map( 'strval', (array) $attrs[ $key ] );
			foreach ( $group['options'] as $okey => $label )
			{
				if ( \in_array( (string) $okey, $chosen, TRUE ) )
				{
					$out[] = array( 'label' => (string) $label, 'color' => $color, 'hex' => static::optionColor($group,$okey,static::$palette[$color]), 'rotate_group' => static::rotationGroup($key,$group), 'group' => (string) $group['name'] );
				}
			}
		}
		return $out;
	}

	/**
	 * CSS kolorów odznak trybów (IPS5)
	 *
	 * @return	string
	 */
	public static function badgeCss(): string
	{
		$css = '.ipsBadge.mpcs-b{color:#fff;background:var(--mpcs-c)}.ipsBadge.sps-recommended{background:#d5ac48;color:#211b0d;font-weight:700}';
		foreach ( static::$palette as $i => $color )
		{
			$css .= '.mpcs-c' . $i . '{--mpcs-c:' . $color . '}';
		}
		return $css;
	}

	public static function topicBadges( $topic, bool $includeVersion = TRUE )
	{
        if (OrdinaryTopic::saved($topic) || Presentation::isDirectory($topic)) { return ''; }
		if ( static::pluginActive() )
		{
			return '';
		}
		try
		{
			$fid = (int) $topic->forum_id;
			$forums = static::forums();
			$enabled = ( isset( $forums[ $fid ] ) and !empty( $forums[ $fid ]['enabled'] ) );
			if ( !$enabled and !static::isArchive( $fid ) )
			{
				return '';
			}

            $versionLabel=\IPS\mpcservers\Modules\Minecraft\Versions::topicLabel($topic);
            $versionHtml=$includeVersion && $versionLabel!==''?'<span class="mpcs-b" style="display:inline-block;padding:0 7px;margin-right:4px;border-radius:3px;color:#fff;background:#047857;font-size:12px;font-weight:600">'.static::esc($versionLabel).'</span>':'';
			$row = static::attrRow( $topic->tid, $fid );
			if ( !$row or !$row['attrs'] )
			{
				return $versionHtml;
			}

			$html = '';
			if ( $enabled )
			{
				$html = static::badgesHtml( $forums[ $fid ]['groups'], $row['attrs'] );
			}
			if ( $html === '' and isset( $forums[ (int) $row['forum_id'] ] ) )
			{
				$html = static::badgesHtml( $forums[ (int) $row['forum_id'] ]['groups'], $row['attrs'] );
			}
			return $versionHtml.$html;
		}
		catch ( \Throwable $e )
		{
			static::logError( $e );
			return '';
		}
	}

	/**
	 * HTML znaczków dla wybranych prefiksów (style w linii: lista tematów nie ma naszego CSS)
	 *
	 * @param	array	$groups	Grupy z definicji działu
	 * @param	array	$attrs	Wybrane opcje: klucz grupy => [klucze opcji]
	 * @return	string
	 */
	public static function badgesHtml( array $groups, array $attrs )
	{
		$html = '';
		$i = 0;
		foreach ( $groups as $key => $group )
		{
			$color = static::$palette[ ($group['_palette_index']??$i) % \count( static::$palette ) ];
			$i++;
			if ( empty( $attrs[ $key ] ) )
			{
				continue;
			}
			$chosen = \array_map( 'strval', (array) $attrs[ $key ] );
			foreach ( $group['options'] as $okey => $label )
			{
				if ( \in_array( (string) $okey, $chosen, TRUE ) )
				{
					$html .= '<span class="mpcs-b" data-mpcs-rotate-group="'.static::esc(static::rotationGroup($key,$group)).'" title="' . static::esc( $group['name'] ) . '" style="display:inline-block;margin:0 4px 2px 0;padding:0 7px;border-radius:3px;font-size:12px;line-height:19px;font-weight:600;vertical-align:middle;white-space:nowrap;color:' . static::foreground(static::optionColor($group,$okey,$color)) . ';background:' . static::optionColor($group,$okey,$color) . '">' . static::esc( $label ) . '</span>';
				}
			}
		}
		return $html === '' ? '' : '<span class="mpcs-badges">' . $html . '</span> ';
	}

	/* ===================================================================
	 *  Pomocnicze
	 * =================================================================== */

	/**
	 * @return	array	ID grupy => nazwa
	 */
	public static function groupOptions()
	{
		$options = array();
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			$options[ $group->g_id ] = $group->name;
		}
		return $options;
	}

	/**
	 * @param	int	$forumId
	 * @return	string
	 */
	public static function forumName( $forumId )
	{
		try
		{
			return \IPS\forums\Forum::load( (int) $forumId )->_title;
		}
		catch ( \Throwable $e )
		{
			return '#' . (int) $forumId;
		}
	}

	/**
	 * Tekst językowy (ACP → Wygląd → Języki, klucze mpcs_*) albo tekst domyślny
	 *
	 * @param	string	$key
	 * @param	string	$fallback
	 * @param	array	$args	sprintf
	 * @return	string
	 */
	public static function lang( $key, $fallback, array $args = array() )
	{
		if ( static::$wordCache === NULL )
		{
			static::$wordCache = array();
			try
			{
				$langId = (int) \IPS\Member::loggedIn()->language()->id;
				foreach ( \IPS\Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( 'lang_id=? AND word_key LIKE ?', $langId, 'mpcs\_%' ) ) as $w )
				{
					$value = ( $w['word_custom'] !== NULL and $w['word_custom'] !== '' ) ? $w['word_custom'] : $w['word_default'];
					if ( $value !== NULL and $value !== '' )
					{
						static::$wordCache[ $w['word_key'] ] = $value;
					}
				}
			}
			catch ( \Throwable $e ) { }
		}

		$text = isset( static::$wordCache[ $key ] ) ? static::$wordCache[ $key ] : $fallback;
		if ( $args )
		{
			$formatted = @\vsprintf( $text, $args );
			$text = ( $formatted === FALSE or $formatted === '' ) ? \vsprintf( $fallback, $args ) : $formatted;
		}
		return $text;
	}

	/**
	 * Zapomnij wczytane teksty (testy)
	 *
	 * @return	void
	 */
	public static function resetWords()
	{
		static::$wordCache = NULL;
	}

	/**
	 * Polska odmiana: 1 serwer, 2 serwery, 5 serwerów
	 *
	 * @param	int		$n
	 * @param	string	$one
	 * @param	string	$few
	 * @param	string	$many
	 * @return	string
	 */
	public static function plural( $n, $one, $few, $many )
	{
		$n = \abs( (int) $n );
		if ( $n === 1 )
		{
			return $one;
		}
		$d = $n % 10;
		$h = $n % 100;
		return ( $d >= 2 and $d <= 4 and ( $h < 12 or $h > 14 ) ) ? $few : $many;
	}

	/**
	 * @param	int	$n
	 * @return	string
	 */
	public static function number( $n )
	{
		try
		{
			return \IPS\Member::loggedIn()->language()->formatNumber( (int) $n );
		}
		catch ( \Throwable $e )
		{
			return \number_format( (int) $n, 0, ',', ' ' );
		}
	}

	/**
	 * Data względna jak w IPS (element <time>)
	 *
	 * @param	int	$ts
	 * @return	string	HTML
	 */
	public static function date( $ts )
	{
		$ts = (int) $ts;
		if ( !$ts )
		{
			return '-';
		}
		try
		{
			return \IPS\DateTime::ts( $ts )->html();
		}
		catch ( \Throwable $e )
		{
			return \date( 'd.m.Y', $ts );
		}
	}

	/**
	 * Data i godzina bez względnych określeń, np. „12.11.2026, 16:07”
	 *
	 * @param	int	$ts
	 * @return	string	tekst
	 */
	public static function dateTime( $ts )
	{
		try
		{
			$d = \IPS\DateTime::ts( (int) $ts );
			return $d->localeDate() . ', ' . $d->localeTime( FALSE );
		}
		catch ( \Throwable $e )
		{
			return \date( 'd.m.Y, H:i', (int) $ts );
		}
	}

	/**
	 * @param	string	$text
	 * @return	string
	 */
	public static function esc( $text )
	{
		return \htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', FALSE );
	}

	/**
	 * @param	\Throwable	$e
	 * @param	bool		$always	Loguj zawsze (a nie tylko z DEBUG_HOOKS)
	 * @return	void
	 */
	public static function logError( $e, $always = FALSE )
	{
		try
		{
            // At most one application error per minute on this host during an outage.
            $file=sys_get_temp_dir().'/mpcs-errors-'.substr(hash('sha256',\IPS\ROOT_PATH),0,24).'.lock';
            $handle=@fopen($file,'c+');
            if(!$handle)return;
            if(!flock($handle,LOCK_EX|LOCK_NB)){fclose($handle);return;}
            try {
                $last=(int)stream_get_contents($handle);
                if($last>time()-60)return;
                rewind($handle);ftruncate($handle,0);fwrite($handle,(string)time());fflush($handle);
            } finally {flock($handle,LOCK_UN);fclose($handle);}

			static $seen = array();
			$key = md5( \get_class( $e ) . $e->getMessage() . $e->getFile() . $e->getLine() );
			if ( !isset( $seen[ $key ] ) )
			{
				$seen[ $key ] = TRUE;
				\IPS\Log::log( $e instanceof \Exception ? $e : (string) $e, 'mpcservers' );
			}
		}
		catch ( \Throwable $e2 ) { }
	}

	/**
	 * Style i skrypty - każdy zestaw raz na odpowiedź
	 *
	 * @param	string	$which	picker|list|editor
	 * @return	string
	 */
	public static function assets( $which )
	{
		$out = '';
		if ( empty( static::$assetsDone['css'] ) )
		{
			static::$assetsDone['css'] = TRUE;
			$out .= '<style>' . static::css() . '</style>';
		}
		if ( empty( static::$assetsDone[ $which ] ) )
		{
			static::$assetsDone[ $which ] = TRUE;
			$js = ( $which === 'list' ) ? static::listJs() : ( ( $which === 'editor' ) ? static::editorJs() : static::pickerJs() );
			$out .= '<script>' . $js . '</script>';
		}
		return $out;
	}

	/**
	 * @return	string
	 */
	public static function css()
	{
		return '.mpcs{padding:20px;font:inherit;color:inherit;text-align:left}.mpcs *{box-sizing:border-box}.mpcs-description{margin:0 0 14px;overflow-wrap:anywhere}.mpcs-warn{margin:0 0 14px}.mpcs-panel{display:grid;gap:10px;padding:12px;border-radius:6px}.mpcs-filters{display:flex;align-items:center;flex-wrap:wrap;gap:6px}.mpcs-filters strong{flex:0 0 110px;margin-right:4px}.mpcs a.mpcs-chip,.mpcs a.ipsButton{text-decoration:none!important}.mpcs-chip .mpcs-n{margin-left:6px;font-variant-numeric:tabular-nums}.mpcs-chip-zero{border-style:dashed}.mpcs-tools{display:flex;align-items:center;flex-wrap:wrap;gap:8px 12px;margin-top:2px}.mpcs-tools label{display:flex;flex-direction:row;align-items:center;gap:6px;min-width:0;margin:0}.mpcs-search{flex:1 1 220px}.mpcs input[type=search],.mpcs select{min-height:36px;font:inherit}.mpcs-tools input[type=search]{flex:1 1 auto;width:100%;min-width:90px}.mpcs-tools select{min-width:170px;max-width:240px}.mpcs-button{flex-shrink:0}.mpcs-reset{display:inline-block;padding:8px 2px;text-decoration:underline}.mpcs-count{margin:14px 0 4px;font-variant-numeric:tabular-nums}.mpcs-rows{list-style:none!important;margin:0!important;padding:0!important}.mpcs-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(150px,24%) 150px;align-items:center;gap:20px;padding:16px 10px;margin:0!important}.mpcs-publication{min-width:0}.mpcs-title{font-weight:bold;overflow-wrap:anywhere}.mpcs-prefixes{margin-top:4px;font-size:.92em;overflow-wrap:anywhere}.mpcs-p{white-space:nowrap}.mpcs-sep{margin:0 6px;opacity:.55}.mpcs-hit{font-weight:bold;text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:3px}.mpcs-author{display:flex;align-items:center;gap:10px;min-width:0;overflow-wrap:anywhere}.mpcs-author .ipsUserPhoto{flex-shrink:0}.mpcs-author-name{min-width:0}.mpcs-date small{display:block;margin-bottom:3px}.mpcs-date .mpcs-exp{margin:4px 0 0;font-weight:bold}.mpcs-empty{padding:30px 10px;text-align:center}.mpcs-empty p{margin:0 0 12px}.mpcs-pages{display:flex;justify-content:center;align-items:center;gap:20px;margin-top:20px}.mpcs-pages a{display:inline-block;padding:8px 4px}.mpcs-status{position:absolute!important;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}.mpcs-status.mpcs-status-error{position:static!important;width:auto;height:auto;margin:10px 0 0;overflow:visible;clip:auto;white-space:normal;font-weight:bold}.mpcs[aria-busy=true] .mpcs-rows{opacity:.6}.mpcs-sr{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}.mpcs a:focus-visible,.mpcs button:focus-visible,.mpcs input:focus-visible,.mpcs select:focus-visible{outline:2px solid currentColor;outline-offset:2px}.mpcs-err{margin:10px 0}.mpcs-pickform{display:grid;gap:8px}.mpcs-pickrow{display:flex;flex-wrap:wrap;align-items:center;gap:6px 10px}.mpcs-pickname{flex:0 0 130px;font-weight:600}.mpcs-pickname small{margin-left:4px;font-weight:400}.mpcs-req{margin-left:2px;color:#dc2626}.mpcs-picks{display:flex;flex:1 1 300px;flex-wrap:wrap;gap:6px}.mpcs-pick{position:relative;display:inline-flex;margin:0;cursor:pointer;font-weight:400}.mpcs-pick input{position:absolute;width:1px;height:1px;margin:0;padding:0;opacity:0;pointer-events:none}.mpcs-pick span{display:inline-block;min-height:28px;padding:3px 12px;border:1px solid var(--c);border-radius:4px;line-height:20px;user-select:none}.mpcs-pick:hover span{box-shadow:inset 0 0 0 1px var(--c)}.mpcs-pick input:checked+span{background:var(--c);color:var(--mpcs-text,#fff)}.mpcs-pick input:checked+span::before{content:"✓ "}.mpcs-pick input:focus-visible+span{outline:2px solid currentColor;outline-offset:2px}.mpcs-pick input:disabled+span{border-style:dashed;cursor:not-allowed}.mpcs-pick-none span{border-style:dashed}.mpcs-pickhint{margin:2px 0 0;font-size:.92em}.mpcs-table{width:100%}.mpcs-table th{text-align:left}.mpcs-acp-note{margin:12px 0}.mpcs-plugin p{margin:0 0 8px}.mpcs-preview{margin-top:16px}.mpcs-ed{display:grid;gap:10px;margin:0 0 8px}.mpcs-ed-group{display:grid;gap:8px;padding:10px 12px;border:1px solid rgba(127,127,127,.35);border-left:4px solid var(--c);border-radius:4px}.mpcs-ed-top{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px}.mpcs-ed-name{flex:1 1 200px;min-width:0;height:32px;font-weight:600}.mpcs-ed-mode{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}.mpcs-ed-mode select{height:32px}.mpcs-ed-max{width:64px;height:32px}.mpcs-ed-icon{min-width:32px;height:32px;padding:0 8px;border:1px solid rgba(127,127,127,.45);border-radius:4px;background:transparent;color:inherit;font:inherit;line-height:1;cursor:pointer}.mpcs-ed-icon[disabled]{opacity:.35;cursor:default}.mpcs-ed-opts{display:flex;flex-wrap:wrap;align-items:center;gap:6px}.mpcs-ed-opt{display:inline-flex;align-items:center;gap:2px;padding:0 2px 0 10px;border-radius:4px;background:var(--c);color:#fff;line-height:28px}.mpcs-ed-opt button{width:24px;height:24px;padding:0;border:0;border-radius:3px;background:transparent;color:inherit;font-size:16px;line-height:1;cursor:pointer}.mpcs-ed-drag{touch-action:none;cursor:grab!important}.mpcs-ed-dragging{opacity:.55}.mpcs-ed-drop{outline:2px solid currentColor;outline-offset:2px}.mpcs-ed-opt button:hover{background:rgba(0,0,0,.25)}.mpcs-ed-new{flex:1 1 200px;min-width:0;height:30px}.mpcs-ed-actions{display:flex;flex-wrap:wrap;align-items:center;gap:12px}.mpcs-ed-raw{padding:0;border:0;background:none;color:inherit;font:inherit;text-decoration:underline;cursor:pointer}.mpcs-ed-empty{margin:0}@media (max-width:700px){.mpcs{padding:14px}.mpcs-filters strong{flex-basis:100%}.mpcs-tools label{flex-wrap:wrap}.mpcs-search{flex-basis:100%}.mpcs-tools select{min-width:0;max-width:none}.mpcs-row{grid-template-columns:1fr auto;gap:10px 12px;padding:14px 4px}.mpcs-publication{grid-column:1/-1}.mpcs-date{text-align:right}.mpcs-pickname{flex-basis:100%}}';
	}

	/**
	 * @return	string
	 */
	public static function listJs()
	{
		return '(function () {
\'use strict\';
if (window.mpcsListReady) { return; }
window.mpcsListReady = true;
var pending = {};
function L(root, k, d) { return root.getAttribute(\'data-l-\' + k) || d; }
function update(root, url, focusName) {
var id = root.getAttribute(\'data-mpcs-list\');
if (pending[id]) { pending[id].abort(); }
var controller = window.AbortController ? new AbortController() : null;
pending[id] = controller || { abort: function () {} };
root.setAttribute(\'aria-busy\', \'true\');
var status = root.querySelector(\'[data-mpcs-status]\');
if (status) { status.classList.remove(\'mpcs-status-error\'); status.textContent = L(root, \'loading\', \'Wczytywanie…\'); }
var target = new URL(url, location.href);
if (target.origin !== location.origin) { location.href = url; return; }
fetch(target.href, { credentials: \'same-origin\', signal: controller ? controller.signal : undefined, headers: { Accept: \'text/html\' } })
.then(function (response) {
if (!response.ok) { throw new Error(\'HTTP \' + response.status); }
return response.text();
})
.then(function (text) {
if (pending[id] !== (controller || pending[id]) || !root.isConnected) { return; }
var doc = new DOMParser().parseFromString(text, \'text/html\');
var next = null;
[].forEach.call(doc.querySelectorAll(\'[data-mpcs-list]\'), function (el) {
if (!next && el.getAttribute(\'data-mpcs-list\') === id) { next = el; }
});
if (!next) { throw new Error(\'missing list\'); }
next = document.importNode(next, true);
root.replaceWith(next);
var nextStatus = next.querySelector(\'[data-mpcs-status]\');
var count = next.querySelector(\'.mpcs-count\');
if (nextStatus) { nextStatus.textContent = count ? count.textContent : \'\'; }
try { history.replaceState(history.state, \'\', target.href); } catch (e) {}
var focus = null;
if (focusName) {
[].forEach.call(next.querySelectorAll(\'[name]\'), function (el) { if (!focus && el.name === focusName) { focus = el; } });
}
if (!focus) { focus = next.querySelector(\'.mpcs-count\'); if (focus) { focus.setAttribute(\'tabindex\', \'-1\'); } }
if (focus) { focus.focus({ preventScroll: true }); }
var top = next.getBoundingClientRect().top;
if (top < 0) { next.scrollIntoView({ block: \'start\' }); }
if (window.jQuery) { window.jQuery(document).trigger(\'contentChange\', [window.jQuery(next)]); }
})
.catch(function (error) {
if (error && error.name === \'AbortError\') { return; }
if (status) { status.classList.add(\'mpcs-status-error\'); status.textContent = L(root, \'error\', \'Nie udało się odświeżyć spisu. Spróbuj ponownie albo odśwież stronę.\'); }
root.removeAttribute(\'aria-busy\');
})
.then(function () {
if (pending[id] === controller) { delete pending[id]; }
});
}
function apply(root, focusName) {
var tools = root.querySelector(\'.mpcs-tools\');
if (!tools) { return; }
var url = new URL(tools.getAttribute(\'data-action\'), location.href);
[].forEach.call(tools.querySelectorAll(\'[name]\'), function (field) { url.searchParams.set(field.name, field.value); });
url.hash = root.id || \'\';
update(root, url.href, focusName);
}
document.addEventListener(\'click\', function (event) {
if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) { return; }
var link = event.target.closest ? event.target.closest(\'.mpcs-filters a, .mpcs-pages a, .mpcs-reset, .mpcs-empty a\') : null;
var root = link ? link.closest(\'[data-mpcs-list]\') : null;
if (link && root) {
event.preventDefault();
update(root, link.href);
return;
}
var button = event.target.closest ? event.target.closest(\'.mpcs-button\') : null;
if (button && button.closest(\'[data-mpcs-list]\')) {
event.preventDefault();
apply(button.closest(\'[data-mpcs-list]\'));
}
});
document.addEventListener(\'change\', function (event) {
if (event.target.matches && event.target.matches(\'.mpcs-tools select\')) {
apply(event.target.closest(\'[data-mpcs-list]\'), event.target.name);
}
});
document.addEventListener(\'keydown\', function (event) {
if ((event.key === \'Enter\' || event.keyCode === 13) && event.target.matches && event.target.matches(\'.mpcs-tools input[type=search]\')) {
event.preventDefault();
apply(event.target.closest(\'[data-mpcs-list]\'), event.target.name);
}
});
})();';
	}

	/**
	 * @return	string
	 */
	public static function pickerJs()
	{
		return '(function () {
\'use strict\';
if (window.mpcsPickerReady) { return; }
window.mpcsPickerReady = true;
/* Limit wyborów w grupie: po osiągnięciu limitu pozostałe opcje są niedostępne */
function limit(row) {
var max = parseInt(row.getAttribute(\'data-max\'), 10);
if (!max) { return; }
var boxes = row.querySelectorAll(\'input[type=checkbox]\');
var checked = row.querySelectorAll(\'input[type=checkbox]:checked\').length;
[].forEach.call(boxes, function (box) { box.disabled = !box.checked && checked >= max; });
}
function init() { [].forEach.call(document.querySelectorAll(\'.mpcs-pickrow[data-max]\'), limit); }
document.addEventListener(\'change\', function (event) {
var row = event.target.closest ? event.target.closest(\'.mpcs-pickrow[data-max]\') : null;
if (row) { limit(row); }
});
if (document.readyState === \'loading\') { document.addEventListener(\'DOMContentLoaded\', init); } else { init(); }
if (window.jQuery) { window.jQuery(document).on(\'contentChange\', init); }
})();';
	}

	/**
	 * @return	string
	 */
	public static function editorJs()
	{
		return <<<'JS'
(function () {
'use strict';
var PALETTE = ['#1d4ed8', '#047857', '#b45309', '#6d28d9', '#0e7490', '#be185d', '#4b5563', '#4d7c0f', '#b91c1c', '#1e3a8a'];
function el(tag, cls, text) {
var e = document.createElement(tag);
if (cls) { e.className = cls; }
if (text !== undefined) { e.textContent = text; }
return e;
}
/* Ten sam format co Servers::parseDefs(): Nazwa (kilka) / Nazwa (max 2): opcja | opcja */
function parseDefs(text) {
var groups = [];
String(text || '').split(/\r\n|\r|\n/).forEach(function (line) {
line = line.trim();
if (!line || line.charAt(0) === '#' || line.slice(0, 2) === '//') { return; }
var p = line.indexOf(':');
if (p < 0) { return; }
var name = line.slice(0, p).trim(), rest = line.slice(p + 1).trim(), multi = false, max = 0;
if (name.slice(-1) === '+') { multi = true; name = name.slice(0, -1).trim(); }
var m = name.match(/^(.*?)\s*\(([^()]*)\)$/);
if (m) {
var known = true, mMax = 0;
m[2].split(/\s*[,;]\s*/).forEach(function (t) {
t = t.trim().toLowerCase();
var mm = t.match(/^(?:max|maks\.?|do|limit)\s*(\d{1,3})$/);
if (t === 'kilka' || t === 'wiele' || t === 'multi') { return; }
if (mm) { mMax = parseInt(mm[1], 10); return; }
known = false;
});
if (known) { name = m[1].trim(); multi = true; max = mMax; }
}
var parts = rest.indexOf('|') > -1 ? rest.split('|') : rest.split(','), opts = [];
parts.forEach(function (x) { x = x.trim(); if (x && opts.indexOf(x) < 0) { opts.push(x); } });
if (multi && max === 1) { multi = false; max = 0; }
groups.push({ name: name, multi: multi, max: max, opts: opts });
});
return groups;
}
function cleanName(s) { return String(s).replace(/[:\r\n|()]/g, ' ').replace(/\+\s*$/, '').replace(/\s+/g, ' ').trim(); }
function cleanOpt(s) { return String(s).replace(/[\r\n|]/g, ' ').replace(/\s+/g, ' ').trim(); }
function serialize(groups) {
return groups.filter(function (g) { return cleanName(g.name) !== '' || g.opts.length; }).map(function (g) {
var opts = g.opts.map(cleanOpt).filter(Boolean);
var mod = '';
if (g.multi) { mod = (g.max > 1 && g.max < opts.length) ? ' (max ' + g.max + ')' : ' (kilka)'; }
var line = cleanName(g.name) + mod + ': ' + opts.join(' | ');
if (opts.length === 1 && opts[0].indexOf(',') > -1) { line += ' |'; }
return line;
}).join('\n');
}
function initEditor(marker) {
if (marker.getAttribute('data-mpcs-ready')) { return; }
var ta = document.querySelector('textarea[name="mpcs_f_defs"]');
if (!ta) { return; }
marker.setAttribute('data-mpcs-ready', '1');
function L(k, d) { return marker.getAttribute('data-l-' + k) || d; }
var groups = parseDefs(ta.value);
if (!groups.length) { groups.push({name:'Tryby serwera',multi:false,max:0,opts:[]}); }
var holder = el('div');
ta.parentNode.insertBefore(holder, ta);
ta.style.display = 'none';
function sync() { ta.value = serialize(groups); }
function render(focus) {
var toFocus = null;
holder.innerHTML = '';
var wrap = el('div', 'mpcs-ed');
if (!groups.length) { wrap.appendChild(el('p', 'mpcs-ed-empty', L('empty', 'Brak grup prefiksów.'))); }
groups.forEach(function (g, i) {
var card = el('div', 'mpcs-ed-group');
card.style.setProperty('--c', PALETTE[i % PALETTE.length]);
var top = el('div', 'mpcs-ed-top');
var nameLabel=el('label','mpcs-ed-mode','Nazwa grupy');var groupName=el('input','ipsField_primary');groupName.type='text';groupName.value=g.name;groupName.maxLength=80;groupName.setAttribute('aria-label','Nazwa grupy prefiksów');groupName.addEventListener('input',function(){g.name=cleanName(groupName.value);sync();});nameLabel.appendChild(groupName);top.appendChild(nameLabel);
['W górę','W dół','Usuń grupę'].forEach(function(label,k){var button=el('button','ipsButton ipsButton--inherit ipsButton--tiny',label);button.type='button';button.addEventListener('click',function(){if(k===2){groups.splice(i,1);}else{var target=i+(k===0?-1:1);if(target<0||target>=groups.length)return;groups.splice(target,0,groups.splice(i,1)[0]);}sync();render();});top.appendChild(button);});
/* Wybór: jedna opcja / kilka opcji, z limitem */
var mode = el('label', 'mpcs-ed-mode');
mode.appendChild(document.createTextNode(L('mode', 'Wybór')));
var select = el('select');
[['single', L('single', 'jedna opcja')], ['multi', L('multi', 'kilka opcji')]].forEach(function (o) {
var opt = el('option', '', o[1]);
opt.value = o[0];
select.appendChild(opt);
});
select.value = g.multi ? 'multi' : 'single';
select.addEventListener('change', function () { g.multi = select.value === 'multi'; if (!g.multi) { g.max = 0; } sync(); render(); });
mode.appendChild(select);
top.appendChild(mode);
if (g.multi) {
var limit = el('label', 'mpcs-ed-mode');
limit.appendChild(document.createTextNode(L('max', 'najwyżej')));
var max = el('input', 'mpcs-ed-max');
max.type = 'number';
max.min = '0';
max.max = '50';
max.value = g.max ? String(g.max) : '';
max.placeholder = '-';
max.title = L('nolimit', 'bez limitu');
max.addEventListener('input', function () {
var v = parseInt(max.value, 10);
g.max = (v > 1) ? v : 0;
sync();
});
limit.appendChild(max);
top.appendChild(limit);
}
card.appendChild(top);
var opts = el('div', 'mpcs-ed-opts');
g.opts.forEach(function (o, j) {
var chip = el('span', 'mpcs-ed-opt');
var colourMatch=o.match(/\s*\{(#[a-f0-9]{6})\}$/i), optionLabel=colourMatch?o.slice(0,colourMatch.index).trim():o;
var colour=colourMatch?colourMatch[1]:PALETTE[i % PALETTE.length];function paint(value){chip.style.setProperty('--c',value);chip.style.color=(parseInt(value.slice(1,3),16)*299+parseInt(value.slice(3,5),16)*587+parseInt(value.slice(5,7),16)*114)>=150000?'#111':'#fff';}paint(colour);
var picker=el('input');picker.type='color';picker.value=colour;picker.title='Kolor prefiksu: '+optionLabel;picker.setAttribute('aria-label',picker.title);picker.style.cssText='width:30px;height:24px;padding:0;border:0;cursor:pointer';
picker.addEventListener('input',function(){g.opts[j]=optionLabel+' {'+picker.value+'}';paint(picker.value);sync();});chip.appendChild(picker);
var resetColour=el('button','','↺');resetColour.type='button';resetColour.title='Przywróć kolor grupy';resetColour.setAttribute('aria-label','Przywróć kolor grupy: '+optionLabel);resetColour.addEventListener('click',function(){g.opts[j]=optionLabel;sync();render({group:i,option:j});});chip.appendChild(resetColour);
var handle = el('button', 'mpcs-ed-drag', '↔');
handle.type = 'button';
handle.title = 'Przeciągnij, aby zmienić kolejność. Klawiatura: strzałka w lewo lub w prawo.';
handle.setAttribute('aria-label', 'Zmień kolejność: ' + o);
chip.dataset.index = String(j);
function move(to) {
if (to < 0 || to >= g.opts.length || to === j) { return; }
g.opts.splice(to, 0, g.opts.splice(j, 1)[0]); sync(); render({group:i, option:to});
}
handle.addEventListener('keydown', function(e) {
if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); move(j + (e.key === 'ArrowLeft' ? -1 : 1)); }
});
handle.addEventListener('pointerdown', function(e) {
if (e.button !== 0) { return; }
e.preventDefault(); handle.setPointerCapture(e.pointerId);
chip.classList.add('mpcs-ed-dragging');
var target = j;
function track(ev) {
var hit = document.elementFromPoint(ev.clientX, ev.clientY);
var over = hit && hit.closest('.mpcs-ed-opt');
opts.querySelectorAll('.mpcs-ed-drop').forEach(function(x){ x.classList.remove('mpcs-ed-drop'); });
if (over && over.parentNode === opts) { target = Number(over.dataset.index); over.classList.add('mpcs-ed-drop'); }
}
function finish(ev) {
handle.removeEventListener('pointermove', track);
handle.removeEventListener('pointerup', finish);
handle.removeEventListener('pointercancel', finish);
chip.classList.remove('mpcs-ed-dragging');
opts.querySelectorAll('.mpcs-ed-drop').forEach(function(x){ x.classList.remove('mpcs-ed-drop'); });
if (handle.hasPointerCapture(e.pointerId)) { handle.releasePointerCapture(e.pointerId); }
if (ev.type === 'pointerup') { move(target); }
}
handle.addEventListener('pointermove', track);
handle.addEventListener('pointerup', finish);
handle.addEventListener('pointercancel', finish);
});
chip.appendChild(handle);
chip.appendChild(document.createTextNode(optionLabel));
if (focus && focus.group === i && focus.option === j) { toFocus = handle; }
var x = el('button', '', '×');
x.type = 'button';
x.title = L('delopt', 'Usuń opcję');
x.setAttribute('aria-label', L('delopt', 'Usuń opcję') + ': ' + o);
x.addEventListener('click', function () { g.opts.splice(j, 1); sync(); render({ group: i }); });
chip.appendChild(x);
opts.appendChild(chip);
});
var input = el('input', 'mpcs-ed-new');
input.type = 'text';
input.placeholder = L('newopt', 'Dodaj opcję i naciśnij Enter');
input.setAttribute('aria-label', L('newopt', 'Dodaj opcję'));
input.maxLength = 200;
function addFromInput() {
var v = input.value.trim();
if (!v) { return false; }
(v.indexOf('|') > -1 ? v.split('|') : v.split(',')).forEach(function (part) {
part = cleanOpt(part).slice(0, 40);
if (part && g.opts.indexOf(part) < 0) { g.opts.push(part); }
});
input.value = '';
sync();
return true;
}
input.addEventListener('keydown', function (e) {
if (e.key === 'Enter' || e.keyCode === 13) {
e.preventDefault();
if (addFromInput()) { render({ group: i }); }
}
});
/* Opóźnione odświeżenie, żeby nie zgubić kliknięcia w inny przycisk */
input.addEventListener('blur', function () { if (addFromInput()) { setTimeout(function () { render(); }, 200); } });
opts.appendChild(input);
card.appendChild(el('strong','','Prefiksy w tej grupie'));card.appendChild(opts);
wrap.appendChild(card);
if (focus && focus.group === i && focus.option === undefined) { toFocus = input; }
});
wrap.appendChild(el('p', 'i-color_soft i-font-size_-1', 'Przy każdym prefiksie wybierz kolor. Przycisk ↺ przywraca kolor grupy. Przeciągnij prefiks za uchwyt ↔, aby zmienić kolejność. Następnie zapisz ustawienia.'));
var addGroup=el('button','ipsButton ipsButton--inherit','Dodaj grupę prefiksów');addGroup.type='button';addGroup.addEventListener('click',function(){groups.push({name:'Grupa '+(groups.length+1),multi:false,max:0,opts:[]});sync();render({group:groups.length-1});});wrap.appendChild(addGroup);holder.appendChild(wrap);
if (toFocus) { toFocus.focus(); }
}
/* Przy wysyłce formularza tekst musi odpowiadać edytorowi */
if (ta.form) { ta.form.addEventListener('submit', function () { sync(); }); }
render();
}
function init() { [].forEach.call(document.querySelectorAll('[data-mpcs-editor]'), initEditor); }
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
if (window.jQuery) { window.jQuery(document).on('contentChange', init); }
})();
JS;
	}
}
