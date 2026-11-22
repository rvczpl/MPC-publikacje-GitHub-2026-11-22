<?php
/**
 * Lista serwerów MPCforum - RafalR - https://rvcz.pl
 */

namespace IPS\mpcservers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Application extends \IPS\Application
{
	/**
	 * Ikona aplikacji w ACP
	 *
	 * @return	string
	 */
	protected function get__icon(): string
	{
		return 'server';
	}

	/**
	 * Ostatni krok instalacji: przenieś dane ze starej wtyczki, jeśli jest
	 *
	 * @return	void
	 */
	public function installOther()
	{
        \IPS\mpcservers\Modules\Core\BackgroundJobs::install();
        \IPS\mpcservers\SystemMigration::run();
        \IPS\mpcservers\Rankings::install();
        \IPS\mpcservers\Awards::install();
        \IPS\mpcservers\CrewLock::install();
        \IPS\mpcservers\Modules\Core\Registry::migrate();
		try
		{
			\IPS\mpcservers\Servers::importFromPlugin();
            \IPS\mpcservers\Modules\Core\PublicationTags::enforceForums();
		}
		catch ( \Throwable $e )
		{
			\IPS\mpcservers\Servers::logError( $e, TRUE );
		}
	}
}
