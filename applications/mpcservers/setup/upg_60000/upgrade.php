<?php
/**
 * Lista serwerów MPCforum 6.0.0 (IPS5): znaczniki [serwery] zapisane w postach jako linki do spisu,
 * żeby podglądy treści (widżety, strumienie, wyszukiwarka) pokazywały tekst, a nie znacznik.
 */
namespace IPS\mpcservers\setup\upg_60000;

use IPS\Db;
use IPS\forums\Topic\Post;
use IPS\mpcservers\Modules\Core\Servers;
use IPS\mpcservers\Modules\Core\TagLinks;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Upgrade
{
	/**
	 * Convert stored [serwery] tags
	 *
	 * @return	bool|array
	 */
	public function step1(): bool|array
	{
		$last = (int) ( $_SESSION['mpcs_upg_60000'] ?? 0 );
		$rows = iterator_to_array( Db::i()->select( 'pid', 'forums_posts', array( 'pid>? AND post LIKE ?', $last, '%[serwery%' ), 'pid ASC', 200 ) );
		foreach ( $rows as $pid )
		{
			try
			{
				TagLinks::convertPost( Post::load( (int) $pid ) );
			}
			catch ( \Throwable $e )
			{
				Servers::logError( $e, TRUE );
			}
			$last = (int) $pid;
		}
		if ( \count( $rows ) < 200 )
		{
			unset( $_SESSION['mpcs_upg_60000'] );
			return TRUE;
		}
		$_SESSION['mpcs_upg_60000'] = $last;
		return array( 'last' => $last );
	}

	/**
	 * Custom title for this step
	 *
	 * @return	string
	 */
	public function step1CustomTitle(): string
	{
		return 'Zapisywanie znaczników [serwery] jako linków do spisu';
	}
}
