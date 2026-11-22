<?php
/**
 * System Publikacji Serwerów MPCforum - archiwizacja nieaktywnych publikacji
 *
 * Czas liczy się od ostatniego odświeżenia tematu. Wtyczka Bump Up Topics
 * odświeża temat, zmieniając datę ostatniej aktywności (forums_topics.last_post),
 * tak samo jak nowa odpowiedź. Według tej samej daty IPS układa listę tematów.
 *
 * Temat trafia do archiwum w chwili wybicia terminu: pierwsze wejście do działu,
 * tematu albo spisu po terminie przenosi go, zanim strona się wyświetli.
 * Zadanie w tle (co 5 minut) wyłapuje resztę.
 */

namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Servers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Archive
{
	/**
	 * @brief	Ile tematów najwyżej przenosi jedno wejście na stronę
	 */
	const ON_VIEW_LIMIT = 5;

	/**
	 * @brief	Jak często (w sekundach) jeden dział jest sprawdzany przy wejściach
	 */
	const CHECK_EVERY = 60;

	/**
	 * Ostatnia aktywność tematów
	 *
	 * refresh: założenie tematu, odświeżenie (Bump Up Topics) i każda widoczna odpowiedź,
	 *          czyli data, według której forum układa tematy;
	 * author:  tylko założenie tematu oraz posty i edycje autora tematu.
	 * W obu trybach liczy się też przywrócenie tematu z archiwum.
	 *
	 * @param	array	$rows	Wiersze forums_topics
	 * @param	string	$mode	refresh|author
	 * @return	array	tid => znacznik czasu
	 */
	public static function activity( array $rows, $mode )
	{
		$result = array();
		$starters = array();
		foreach ( $rows as $row )
		{
			$tid = (int) $row['tid'];
			$result[ $tid ] = (int) $row['start_date'];
			if ( $mode !== 'author' )
			{
				$result[ $tid ] = \max( $result[ $tid ], (int) $row['last_post'] );
			}
			$starters[ $tid ] = (int) $row['starter_id'];
		}

		foreach ( \array_chunk( \array_keys( $result ), 500 ) as $chunk )
		{
			if ( $mode === 'author' )
			{
				foreach ( \IPS\Db::i()->select( 'topic_id, author_id, MAX(post_date) AS lp, MAX(edit_time) AS le', 'forums_posts', array( array( \IPS\Db::i()->in( 'topic_id', $chunk ) ), array( 'queued=?', 0 ) ), NULL, NULL, array( 'topic_id', 'author_id' ) ) as $p )
				{
					$tid = (int) $p['topic_id'];
					if ( (int) $p['author_id'] !== $starters[ $tid ] )
					{
						continue;
					}
					$result[ $tid ] = \max( $result[ $tid ], (int) $p['lp'], (int) $p['le'] );
				}
			}
			foreach ( Servers::attrRows( $chunk ) as $tid => $s )
			{
				$result[ $tid ] = \max( $result[ $tid ], (int) $s['restored'] );
			}
		}

		foreach (array_chunk(array_keys($result),500) as $ids) {
            foreach (\IPS\Db::i()->select('tid,bumped','mpcservers_presentations',array(array(\IPS\Db::i()->in('tid',$ids)),array('is_server=1'))) as $server) {
                $result[(int)$server['tid']]=max($result[(int)$server['tid']],(int)$server['bumped']);
            }
        }
		return $result;
	}

	/**
	 * Termin przeniesienia tematu
	 *
	 * @param	array	$cfg		Konfiguracja działu
	 * @param	int		$activity	Ostatnia aktywność
	 * @return	int|NULL	NULL = dział bez archiwizacji
	 */
	public static function deadline( array $cfg, $activity )
	{
		if ( (int) $cfg['days'] <= 0 )
		{
			return NULL;
		}
		return \max( (int) $activity, (int) $cfg['since'] ) + (int) $cfg['days'] * 86400;
	}

	/**
	 * Czy archiwizacja w dziale faktycznie działa (wyłącznik, dni, poprawne archiwum)
	 *
	 * @param	array		$cfg
	 * @param	string|NULL	$error
	 * @return	\IPS\forums\Forum|NULL	Dział-archiwum
	 */
	public static function activeTarget( array $cfg, &$error = NULL )
	{
		if ( empty( $cfg['enabled'] ) or (int) $cfg['days'] <= 0 )
		{
			$error = NULL;
			return NULL;
		}
		if ( !(int) \IPS\Settings::i()->mpcs_mover_enabled )
		{
			$error = Servers::lang( 'mpcs_err_mover_off', 'archiwizacja jest wyłączona w ustawieniach aplikacji' );
			return NULL;
		}
		return static::archiveTarget( $cfg, $error );
	}

	/**
	 * Dział-archiwum dla działu publikacji
	 *
	 * @param	array		$cfg
	 * @param	string|NULL	$error	Opis problemu, jeśli brak archiwum
	 * @return	\IPS\forums\Forum|NULL
	 */
	public static function archiveTarget( array $cfg, &$error = NULL )
	{
		$id = (int) $cfg['archive_id'] ?: (int) \IPS\Settings::i()->mpcs_default_archive;
		if ( !$id )
		{
			$error = Servers::lang( 'mpcs_err_no_archive', 'nie wybrano działu-archiwum' );
			return NULL;
		}
		if ( $id === (int) $cfg['forum_id'] )
		{
			$error = Servers::lang( 'mpcs_err_archive_self', 'archiwum nie może być tym samym działem' );
			return NULL;
		}
		if ( Servers::enabledForum( $id ) )
		{
			$error = Servers::lang( 'mpcs_err_archive_pub', 'archiwum nie może być działem publikacji' );
			return NULL;
		}
		try
		{
			$forum = \IPS\forums\Forum::load( $id );
		}
		catch ( \OutOfRangeException $e )
		{
			$error = Servers::lang( 'mpcs_err_archive_missing', 'dział-archiwum nie istnieje' );
			return NULL;
		}
		if ( !$forum->sub_can_post or $forum->redirect_on )
		{
			$error = Servers::lang( 'mpcs_err_archive_nopost', 'dział-archiwum nie przyjmuje tematów' );
			return NULL;
		}
		$error = NULL;
		return $forum;
	}

	/**
	 * Tematy działu po terminie (aktywność przed $cutoff)
	 *
	 * Temat przywrócony z archiwum dostaje nowy pełny okres liczony od chwili,
	 * w której aplikacja to zauważyła.
	 *
	 * @param	array		$cfg				Konfiguracja działu
	 * @param	int			$cutoff
	 * @param	int			$limit
	 * @param	bool		$recordRestores		Zapisz przywrócenia; FALSE = tylko podgląd
	 * @param	int|NULL	$onlyTid			Sprawdź tylko ten temat
	 * @return	array	Wiersze forums_topics z kluczem '_activity'
	 */
	public static function findInactive( array $cfg, $cutoff, $limit, $recordRestores = FALSE, $onlyTid = NULL )
	{
		$found = array();
		if ( $limit <= 0 )
		{
			return $found;
		}

		$where = array(
			array( 'forum_id=?', (int) $cfg['forum_id'] ),
			array( 'approved=?', 1 ),
			array( 'state<>?', 'link' ),
			array( 'start_date<?', (int) $cutoff ),
			array( 'start_date>=?', (int) $cfg['since'] ),
			array( 'featured=?', 0 ),
		);
        $where[]=array('tid NOT IN (SELECT tid FROM '.\IPS\Db::i()->prefix.'mpcservers_presentations WHERE featured=1)');
        $directoryIds=\IPS\mpcservers\Presentation::directoryIds();
        if ($directoryIds) { $where[]=array('tid NOT IN ('.implode(',',array_map('intval',$directoryIds)).')'); }
		if ( $onlyTid )
		{
			$where[] = array( 'tid=?', (int) $onlyTid );
		}
		if ( !empty( $cfg['skip_pinned'] ) )
		{
			$where[] = array( 'pinned=?', 0 );
		}
		if ( $cfg['activity'] !== 'author' )
		{
			$where[] = array( 'last_post<?', (int) $cutoff );
		}

		$offset = 0;
		$chunk = 200;
		for ( $round = 0; $round < 10 and \count( $found ) < $limit; $round++ )
		{
			$rows = array();
			foreach ( \IPS\Db::i()->select( '*', 'forums_topics', $where, 'last_post ASC, tid ASC', array( $offset, $chunk ) ) as $row )
			{
				$rows[] = $row;
			}
			if ( !$rows )
			{
				break;
			}

			$activity = static::activity( $rows, $cfg['activity'] );
			$restored = static::pendingRestores( $rows, $activity, $cutoff );
			foreach ( $rows as $row )
			{
				$tid = (int) $row['tid'];
				$act = \max( $activity[ $tid ], (int) $cfg['since'] );
				if ( $act >= $cutoff )
				{
					continue;
				}
				if ( isset( $restored[ $tid ] ) )
				{
					if ( $recordRestores )
					{
						static::touch( $tid, (int) $cfg['forum_id'] );
					}
					continue;
				}
				if ( isset( $row['topic_archive_status'] ) and (int) $row['topic_archive_status'] !== 0 )
				{
					continue;
				}
				if ( static::topicHasListing( $row ) )
				{
					continue;
				}
				$row['_activity'] = $act;
				$found[] = $row;
				if ( \count( $found ) >= $limit )
				{
					break;
				}
			}

			if ( \count( $rows ) < $chunk )
			{
				break;
			}
			$offset += $chunk;
		}

		return $found;
	}

	/**
	 * Tematy przywrócone z archiwum, których przywrócenia jeszcze nie zapisano
	 *
	 * @param	array	$rows		Wiersze forums_topics
	 * @param	array	$activity	tid => ostatnia aktywność
	 * @param	int		$cutoff
	 * @return	array	tid => TRUE
	 */
	protected static function pendingRestores( array $rows, array $activity, $cutoff )
	{
		$tids = array();
		foreach ( $rows as $row )
		{
			if ( $activity[ (int) $row['tid'] ] < $cutoff )
			{
				$tids[] = (int) $row['tid'];
			}
		}
		if ( !$tids )
		{
			return array();
		}

		$moved = array();
		foreach ( \IPS\Db::i()->select( 'tid, MAX(moved_at) AS m', 'mpcservers_log', array( \IPS\Db::i()->in( 'tid', $tids ) ), NULL, NULL, array( 'tid' ) ) as $l )
		{
			$moved[ (int) $l['tid'] ] = (int) $l['m'];
		}
		if ( !$moved )
		{
			return array();
		}

		$rowsByTid = Servers::attrRows( \array_keys( $moved ) );
		$out = array();
		foreach ( $moved as $tid => $movedAt )
		{
			if ( ( isset( $rowsByTid[ $tid ] ) ? $rowsByTid[ $tid ]['restored'] : 0 ) < $movedAt )
			{
				$out[ $tid ] = TRUE;
			}
		}
		return $out;
	}

	/**
	 * Zapisz przywrócenie tematu z archiwum
	 *
	 * @param	int	$tid
	 * @param	int	$forumId
	 * @return	void
	 */
	protected static function touch( $tid, $forumId )
	{
		$row = Servers::attrRow( $tid );
		if ( $row )
		{
			\IPS\Db::i()->update( 'mpcservers_topics', array( 'restored' => \time() ), array( 'tid=?', (int) $tid ) );
		}
		else
		{
			\IPS\Db::i()->insert( 'mpcservers_topics', array( 'tid' => (int) $tid, 'forum_id' => (int) $forumId, 'attrs' => '{}', 'updated' => \time(), 'restored' => \time() ) );
		}
		Servers::forgetAttrRow( $tid );
	}

	/**
	 * Temat ze spisem ([serwery] w pierwszym poście) nigdy nie trafia do archiwum
	 *
	 * @param	array	$row
	 * @return	bool
	 */
	public static function topicHasListing( array $row )
	{
        $presentation=Presentation::get((int)$row['tid']);
        if ($presentation && (!empty($presentation['featured']) || empty($presentation['is_server']))) { return TRUE; }
		try
		{
			$where = !empty( $row['topic_firstpost'] ) ? array( 'pid=?', (int) $row['topic_firstpost'] ) : array( 'topic_id=? AND new_topic=1', (int) $row['tid'] );
			$post = (string) \IPS\Db::i()->select( 'post', 'forums_posts', $where )->first();
			return \stripos( $post, '[serwery' ) !== FALSE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
	}

	/**
	 * Przenieś jeden temat do archiwum i zapisz w dzienniku
	 *
	 * @param	array				$row		Wiersz forums_topics z kluczem '_activity'
	 * @param	array				$cfg
	 * @param	\IPS\forums\Forum	$target
	 * @return	void
	 */
	protected static function moveTopic( array $row, array $cfg, $target )
	{
		/* load() zamiast constructFromData(): IPS trzyma wczytane tematy w pamięci (multiton),
		   więc widok tematu, który właśnie się otwiera, od razu widzi nowy dział */
        $db=\IPS\Db::i();$db->query('START TRANSACTION');
        try {
        $current=$db->query('SELECT featured FROM `'.$db->prefix.'mpcservers_presentations` WHERE tid='.(int)$row['tid'].' FOR UPDATE')->fetch_assoc();
        if($current && !empty($current['featured'])){$db->query('ROLLBACK');return FALSE;}
		$topic = \IPS\forums\Topic::load( (int) $row['tid'] );
		$topic->move( $target );
		if ( !empty( $cfg['lock_moved'] ) and $topic->state !== 'closed' )
		{
			$topic->state = 'closed';
			$topic->save();
		}
		\IPS\Db::i()->insert( 'mpcservers_log', array(
			'tid'			=> (int) $row['tid'],
			'title'			=> \mb_substr( (string) $row['title'], 0, 255 ),
			'forum_from'	=> (int) $cfg['forum_id'],
			'forum_to'		=> (int) $target->_id,
			'author_id'		=> (int) $row['starter_id'],
			'last_activity'	=> (int) $row['_activity'],
			'moved_at'		=> \time(),
			'imported'		=> 0,
		) );
        $db->query('COMMIT');return TRUE;
        } catch (\Throwable $e) {$db->query('ROLLBACK');throw $e;}
	}

	/**
	 * Zajmij dział do sprawdzenia (najwyżej raz na CHECK_EVERY sekund, jedno żądanie naraz)
	 *
	 * @param	array	$cfg
	 * @param	int		$every
	 * @return	bool
	 */
	protected static function claim( array $cfg, $every )
	{
		$now = \time();
		if ( (int) $cfg['checked'] > $now - $every )
		{
			return FALSE;
		}
		return (bool) \IPS\Db::i()->update( 'mpcservers_forums', array( 'checked' => $now ), array( 'forum_id=? AND checked<=?', (int) $cfg['forum_id'], $now - $every ) );
	}

	/**
	 * Przenieś tematy po terminie w jednym dziale
	 *
	 * @param	array		$cfg
	 * @param	int			$limit
	 * @param	array		$problems	Dopisywane opisy problemów
	 * @param	int|NULL	$preferTid	Najpierw sprawdź ten temat (właśnie otwierany)
	 * @return	int	Liczba przeniesionych
	 */
	protected static function sweepForum( array $cfg, $limit, array &$problems, $preferTid = NULL )
	{
		$error = NULL;
		$target = static::activeTarget( $cfg, $error );
		if ( !$target )
		{
			if ( $error !== NULL )
			{
				$problems[] = Servers::forumName( $cfg['forum_id'] ) . ': ' . $error;
			}
			return 0;
		}

		$cutoff = \time() - (int) $cfg['days'] * 86400;
		if ( (int) $cfg['since'] > $cutoff )
		{
			return 0;
		}

		$rows = array();
		if ( $preferTid )
		{
			$rows = static::findInactive( $cfg, $cutoff, 1, TRUE, $preferTid );
		}
		foreach ( static::findInactive( $cfg, $cutoff, $limit, TRUE ) as $row )
		{
			if ( \count( $rows ) >= $limit )
			{
				break;
			}
			if ( !$preferTid or (int) $row['tid'] !== (int) $preferTid )
			{
				$rows[] = $row;
			}
		}

		$moved = 0;
		foreach ( $rows as $row )
		{
            // Stop the batch on a failure; BackgroundJobs records backoff in SQL.
            BackgroundJobs::checkNativeStore();
            if(static::moveTopic( $row, $cfg, $target )!==FALSE)$moved++;

		}
		return $moved;
	}

	/**
	 * Przeniesienie w chwili wejścia na stronę działu, tematu albo spisu
	 *
	 * @param	array		$forumIds
	 * @param	int|NULL	$preferTid
	 * @return	int	Liczba przeniesionych
	 */
	public static function onView( array $forumIds, $preferTid = NULL )
	{
        // Archival is performed exclusively by the scheduled mover task.
        return 0;
	}

	/**
	 * Zadanie w tle (co 5 minut)
	 *
	 * @return	string|NULL	Wpis do logu zadania
	 */
	public static function runTask()
	{
        if (!FeatureHooks::enabled('archive')) return NULL;
		if ( Servers::pluginActive() or !(int) \IPS\Settings::i()->mpcs_mover_enabled )
		{
			return NULL;
		}

		$batch = (int) \IPS\Settings::i()->mpcs_batch;
		$batch = ( $batch > 0 ) ? \min( $batch, 500 ) : 50;
		$moved = 0;
		$problems = array();

		foreach ( Servers::forums( TRUE ) as $fid => $cfg )
		{
			if ( empty( $cfg['enabled'] ) or (int) $cfg['days'] <= 0 or $moved >= $batch )
			{
				continue;
			}
			/* Zadanie nie czeka na minutową przerwę, ale nie wchodzi w drogę trwającemu sprawdzaniu */
			if ( !static::claim( $cfg, 10 ) )
			{
				continue;
			}
			$moved += static::sweepForum( $cfg, $batch - $moved, $problems );
		}

        /* Let failures reach the job guard instead of reporting a successful run. */
        \IPS\Db::i()->delete( 'mpcservers_topics', 'tid NOT IN (SELECT tid FROM ' . \IPS\Db::i()->prefix . 'forums_topics)' );
        \IPS\Db::i()->delete( 'mpcservers_log', array( 'moved_at<?', \time() - 180 * 86400 ) );

		Servers::resetCache();

		$parts = array();
		if ( $moved )
		{
			$parts[] = Servers::lang( 'mpcs_task_moved', 'Przeniesiono do archiwum: %d', array( $moved ) );
		}
		if ( $problems )
		{
			$parts[] = Servers::lang( 'mpcs_task_problems', 'Problemy: %s', array( \implode( '; ', \array_slice( $problems, 0, 10 ) ) ) );
		}
		return $parts ? \implode( '. ', $parts ) : NULL;
	}

	/**
	 * Podgląd: co trafi do archiwum w ciągu 7 dni (ACP)
	 *
	 * @param	array	$cfg
	 * @return	string
	 */
	public static function previewHtml( array $cfg )
	{
		$html = '<h2 class="ipsTitle ipsTitle--h3">' . Servers::esc( Servers::lang( 'mpcs_preview_title', 'Najbliższe przeniesienia do archiwum (7 dni)' ) ) . '</h2>';

		$error = NULL;
		$target = static::activeTarget( $cfg, $error );
		if ( (int) $cfg['days'] <= 0 )
		{
			return $html . '<p>' . Servers::esc( Servers::lang( 'mpcs_preview_off', 'Archiwizacja jest wyłączona (0 dni).' ) ) . '</p>';
		}
		if ( !$target )
		{
			$html .= '<p class="ipsMessage ipsMessage--warning">' . Servers::esc( Servers::lang( 'mpcs_preview_problem', 'Tematy nie są przenoszone: %s.', array( (string) $error ) ) ) . '</p>';
		}

		$now = \time();
		$cutoff = $now - (int) $cfg['days'] * 86400 + 7 * 86400;
		$rows = static::findInactive( $cfg, $cutoff, 50 );
		if ( !$rows )
		{
			return $html . '<p>' . Servers::esc( Servers::lang( 'mpcs_preview_none', 'W ciągu 7 dni nic nie trafi do archiwum.' ) ) . '</p>';
		}

		$html .= '<table class="ipsTable mpcs-table"><thead><tr><th>' . Servers::esc( Servers::lang( 'mpcs_col_topic', 'Temat' ) ) . '</th><th>' . Servers::esc( Servers::lang( 'mpcs_col_activity', 'Ostatnie odświeżenie' ) ) . '</th><th>' . Servers::esc( Servers::lang( 'mpcs_col_move', 'Przeniesienie' ) ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row )
		{
			$data = $row;
			unset( $data['_activity'] );
			$topic = \IPS\forums\Topic::constructFromData( $data );
			$moveAt = static::deadline( $cfg, $row['_activity'] );
			$html .= '<tr><td><a href="' . Servers::esc( (string) $topic->url() ) . '" target="_blank" rel="noopener">' . Servers::esc( $row['title'] ) . '</a></td><td>' . Servers::esc( Servers::dateTime( $row['_activity'] ) ) . '</td><td>'
				. ( $moveAt <= $now ? Servers::esc( Servers::lang( 'mpcs_move_next_view', 'przy najbliższym wejściu' ) ) : Servers::esc( Servers::dateTime( $moveAt ) ) ) . '</td></tr>';
		}
		return $html . '</tbody></table>';
	}
}
