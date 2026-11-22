<?php
/**
 * System Publikacji Serwerów MPCforum - ACP: dziennik przeniesień
 */

namespace IPS\mpcservers\modules\admin\servers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

use IPS\mpcservers\Servers;

class log extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute(): void
	{
		\IPS\mpcservers\Modules\Core\Registry::admin('lists');
		parent::execute();
	}

	/**
	 * Ostatnie przeniesienia
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title = Servers::lang( 'mpcs_log_title', 'Dziennik przeniesień do archiwum' );

		$rows = array();
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'mpcservers_log', NULL, 'moved_at DESC, id DESC', array( 0, 200 ) ) as $row )
			{
				$rows[] = $row;
			}
		}
		catch ( \Throwable $e ) { }

		if ( !$rows )
		{
			\IPS\Output::i()->output = '<div class="ipsBox i-padding_3"><p>' . Servers::esc( Servers::lang( 'mpcs_log_none', 'Nic jeszcze nie przeniesiono.' ) ) . '</p></div>';
			return;
		}

		$html = '<div class="ipsBox"><table class="ipsTable ipsTable_zebra"><thead><tr>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_when', 'Kiedy' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_topic', 'Temat' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_from', 'Z działu' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_to', 'Do archiwum' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_activity', 'Ostatnie odświeżenie' ) ) . '</th>'
			. '</tr></thead><tbody>';
		foreach ( $rows as $row )
		{
			$titleHtml = Servers::esc( $row['title'] );
			try
			{
				$titleHtml = '<a href="' . Servers::esc( (string) \IPS\forums\Topic::load( (int) $row['tid'] )->url() ) . '" target="_blank" rel="noopener">' . $titleHtml . '</a>';
			}
			catch ( \Throwable $e ) { }
			$html .= '<tr><td>' . Servers::esc( Servers::dateTime( $row['moved_at'] ) ) . '</td>'
				. '<td>' . $titleHtml . '</td>'
				. '<td>' . Servers::esc( Servers::forumName( $row['forum_from'] ) ) . '</td>'
				. '<td>' . Servers::esc( Servers::forumName( $row['forum_to'] ) ) . '</td>'
				. '<td>' . Servers::esc( Servers::dateTime( $row['last_activity'] ) ) . '</td></tr>';
		}
		\IPS\Output::i()->output = $html . '</tbody></table></div>';
	}
}
