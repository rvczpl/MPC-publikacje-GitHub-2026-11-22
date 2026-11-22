<?php
/**
 * System Publikacji Serwerów MPCforum - ACP: ustawienia ogólne
 */

namespace IPS\mpcservers\modules\admin\servers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

use IPS\mpcservers\Servers;

class settings extends \IPS\Dispatcher\Controller
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
	 * Ustawienia
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title = Servers::lang( 'mpcs_settings_title', 'System Publikacji Serwerów MPCforum: ustawienia' );
		$settings = \IPS\Settings::i();

		$archive = 0;
		if ( (int) $settings->mpcs_default_archive )
		{
			try
			{
				$archive = \IPS\forums\Forum::load( (int) $settings->mpcs_default_archive );
			}
			catch ( \OutOfRangeException $e ) { }
		}

		$form = new \IPS\Helpers\Form( 'mpcs_settings', 'save' );
		$form->addHeader( 'mpcs_h_perms' );
        $ordinary = new \IPS\Helpers\Form\Select('sps_plain_groups', array_filter(array_map('intval',explode(',',(string)$settings->sps_plain_groups))), FALSE, array('options'=>Servers::groupOptions(),'multiple'=>TRUE,'parse'=>'normal'));
        $ordinary->label = 'Grupy mogące tworzyć zwykłe tematy';
        $ordinary->description = 'W tych grupach można wybrać zwykły temat bez danych serwera i prefiksów. Administratorzy mają to uprawnienie zawsze. Temat nie trafi do spisu ani automatycznej archiwizacji publikacji.';
        $form->add($ordinary);
		$form->add( new \IPS\Helpers\Form\Select( 'mpcs_tag_groups', \array_filter( \array_map( 'intval', \explode( ',', (string) $settings->mpcs_tag_groups ) ) ), FALSE, array( 'options' => Servers::groupOptions(), 'multiple' => TRUE, 'parse' => 'normal' ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_tag_mods', (bool) $settings->mpcs_tag_mods ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_mod_bypass', (bool) $settings->mpcs_mod_bypass ) );

		$form->addHeader( 'mpcs_h_archive_global' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_mover_enabled', (bool) $settings->mpcs_mover_enabled ) );
		$form->add( new \IPS\Helpers\Form\Node( 'mpcs_default_archive', $archive, FALSE, array(
			'class'				=> '\IPS\forums\Forum',
			'zeroVal'			=> 'mpcs_none',
			'clubs'				=> FALSE,
			'permissionCheck'	=> function( $node ) {
				return ( $node->sub_can_post and !$node->redirect_on and !\IPS\mpcservers\Servers::enabledForum( $node->_id ) );
			},
		) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'mpcs_batch', (int) $settings->mpcs_batch ?: 50, FALSE, array( 'min' => 1, 'max' => 500 ) ) );

		$form->addHeader( 'mpcs_h_list' );
		$form->add( new \IPS\Helpers\Form\Number( 'mpcs_per_page', (int) $settings->mpcs_per_page ?: 20, FALSE, array( 'min' => 5, 'max' => 100 ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_list_pinned', (bool) $settings->mpcs_list_pinned ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_list_old', (bool) $settings->mpcs_list_old ) );
		$form->add( new \IPS\Helpers\Form\Number( 'mpcs_list_limit', (int) $settings->mpcs_list_limit ?: 1000, FALSE, array( 'min' => 50, 'max' => 2000 ) ) );


		if ( $values = $form->values() )
		{


			$settings->changeValues( array(
                
                
                
                
                'sps_plain_groups' => implode(',',array_map('intval',(array)$values['sps_plain_groups'])),
				'mpcs_tag_groups'		=> \implode( ',', \array_map( 'intval', (array) $values['mpcs_tag_groups'] ) ),
				'mpcs_tag_mods'			=> $values['mpcs_tag_mods'] ? 1 : 0,
				'mpcs_mod_bypass'		=> $values['mpcs_mod_bypass'] ? 1 : 0,
				'mpcs_mover_enabled'	=> $values['mpcs_mover_enabled'] ? 1 : 0,
				'mpcs_default_archive'	=> \is_object( $values['mpcs_default_archive'] ) ? (int) $values['mpcs_default_archive']->_id : 0,
				'mpcs_batch'			=> \max( 1, \min( 500, (int) $values['mpcs_batch'] ) ),
				'mpcs_per_page'			=> \max( 5, \min( 100, (int) $values['mpcs_per_page'] ) ),
				'mpcs_list_pinned'		=> $values['mpcs_list_pinned'] ? 1 : 0,
				'mpcs_list_old'			=> $values['mpcs_list_old'] ? 1 : 0,
				'mpcs_list_limit'		=> \max( 50, \min( 2000, (int) $values['mpcs_list_limit'] ) ),
			) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=mpcservers&module=lists&controller=options' ), 'saved' );
			return;
		}

		\IPS\Output::i()->output = '<div class="ipsBox">' . $form . '</div>';
	}
}
