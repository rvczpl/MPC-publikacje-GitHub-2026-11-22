<?php
/**
 * System Publikacji Serwerów MPCforum - zadanie: przenoszenie nieaktywnych publikacji do archiwum
 */

namespace IPS\mpcservers\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class mover extends \IPS\Task
{
	/**
	 * Uruchom zadanie
	 *
	 * @return	string|NULL	Wpis do logu zadania
	 */
	public function execute(): mixed
	{
		return \IPS\mpcservers\Modules\Core\BackgroundJobs::run('archive', fn()=>\IPS\mpcservers\Archive::runTask(), TRUE);
	}

	/**
	 * Sprzątanie
	 *
	 * @return	void
	 */
	public function cleanup()
	{
	}
}
