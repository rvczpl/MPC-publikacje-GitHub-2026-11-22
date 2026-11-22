<?php
/**
 * System Publikacji Serwerów MPCforum - "Panel właściciela serwerów" w menu konta (IPS4: hook szablonu userBar)
 */
namespace IPS\mpcservers\extensions\core\UserMenu;

use IPS\Helpers\Menu\Link;
use IPS\mpcservers\Modules\Community\Owner;
use IPS\Output\UI\MenuExtension;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class OwnerPanel extends MenuExtension
{
	/**
	 * Account menu items
	 *
	 * @param	string	$position	settings|content|logout
	 * @return	array
	 */
	public function accountMenu( string $position = 'content' ): array
	{
		return ( $position === 'logout' and Owner::hasPanel() ) ? array( new Link( Owner::url(), 'mpcs_owner_panel', icon: 'fa-solid fa-server', identifier: 'mpcServerOwner' ) ) : array();
	}

	/**
	 * Mobile menu items
	 *
	 * @param	string	$position
	 * @return	array
	 */
	public function mobileMenu( string $position = 'content' ): array
	{
		return $this->accountMenu( $position );
	}
}
