<?php
/**
 * System Publikacji Serwerów MPCforum - ACP: działy publikacji
 */

namespace IPS\mpcservers\modules\admin\servers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

use IPS\mpcservers\Servers;
use IPS\mpcservers\Archive;

class forums extends \IPS\Dispatcher\Controller
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
        \IPS\mpcservers\Modules\Core\Registry::admin($this->gameModule());
		parent::execute();
	}

	/**
	 * Lista działów publikacji
	 *
	 * @return	void
	 */
    protected function gameModule(){return (string)\IPS\Request::i()->module==='minecraft'?'minecraft':'metin2';}
	protected function manage()
	{
		$base = \IPS\Http\Url::internal( 'app=mpcservers&module='.$this->gameModule().'&controller=publications' );

		\IPS\Output::i()->title = Servers::lang( 'mpcs_forums_title', 'Działy publikacji' );
		\IPS\Output::i()->sidebar['actions']['add'] = array(
			'primary'	=> TRUE,
			'icon'		=> 'plus',
			'title'		=> 'mpcs_add_forum',
			'link'		=> $base->setQueryString( 'do', 'edit' ),
		);

        \IPS\Output::i()->sidebar['actions']['people'] = array('icon'=>'users','title'=>'sps_people_link','link'=>\IPS\Http\Url::internal('app=mpcservers&module=servers&controller=people'));
        \IPS\Output::i()->sidebar['actions']['presentation'] = array('icon'=>'cog','title'=>'sps_general','link'=>$base->setQueryString('do','presentationSettings'));
		$html = Servers::assets( 'editor' );

		/* Stara wtyczka */
		if ( Servers::pluginTablesExist() )
		{
			$importUrl = $base->setQueryString( array( 'do' => 'import', 'csrfKey' => \IPS\Session::i()->csrfKey ) );
			$html .= '<div class="ipsMessage ipsMessage_' . ( Servers::pluginActive() ? 'warning' : 'info' ) . ' mpcs-plugin">'
				. '<p>' . Servers::esc( Servers::pluginActive()
					? Servers::lang( 'mpcs_plugin_active', 'Wtyczka „Spis publikacji serwerów” jest nadal zainstalowana. Dopóki działa, aplikacja nic nie zmienia na forum, żeby pola prefiksów i spisy nie pojawiały się podwójnie.' )
					: Servers::lang( 'mpcs_plugin_tables', 'W bazie są dane starej wtyczki „Spis publikacji serwerów”.' ) ) . '</p>'
				. '<p>' . Servers::esc( Servers::lang( 'mpcs_plugin_steps', 'Kliknij „Przenieś dane z wtyczki”, sprawdź listę działów poniżej, a potem odinstaluj wtyczkę w System → Wtyczki. Przenoszenie można powtórzyć, niczego nie dubluje.' ) ) . '</p>'
				. '<p><a class="ipsButton ipsButton--primary ipsButton--small" href="' . Servers::esc( (string) $importUrl ) . '">' . Servers::esc( Servers::lang( 'mpcs_import', 'Przenieś dane z wtyczki' ) ) . '</a></p></div>';
		}

		$forums = array_filter(Servers::forums(TRUE),function($cfg,$id){return \IPS\mpcservers\Modules\Core\Registry::kind(\IPS\mpcservers\Presentation::forumConfig($id)['category'])===$this->gameModule();},ARRAY_FILTER_USE_BOTH);
		if ( !$forums )
		{
			$html .= '<div class="ipsBox i-padding_3"><p>' . Servers::esc( Servers::lang( 'mpcs_forums_none', 'Nie dodano jeszcze żadnego działu publikacji.' ) ) . '</p>'
				. '<p><a class="ipsButton ipsButton--primary" href="' . Servers::esc( (string) $base->setQueryString( 'do', 'edit' ) ) . '">' . Servers::esc( Servers::lang( 'mpcs_add_forum', 'Dodaj dział publikacji' ) ) . '</a></p></div>';
			\IPS\Output::i()->output = $html;
			return;
		}

		$counts = array();
		try
		{
			foreach ( \IPS\Db::i()->select( 'forum_id, COUNT(*) AS n', 'mpcservers_topics', NULL, NULL, NULL, array( 'forum_id' ) ) as $c )
			{
				$counts[ (int) $c['forum_id'] ] = (int) $c['n'];
			}
		}
		catch ( \Throwable $e ) { }

		$html .= '<div class="ipsBox"><table class="ipsTable ipsTable_zebra mpcs-table"><thead><tr>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_forum', 'Dział' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_state', 'Stan' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_prefixes', 'Grupy prefiksów' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_archive', 'Archiwizacja' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_tag', 'Znacznik' ) ) . '</th>'
			. '<th>' . Servers::esc( Servers::lang( 'mpcs_col_topics', 'Tematy z prefiksami' ) ) . '</th>'
			. '<th></th></tr></thead><tbody>';
		foreach ( $forums as $fid => $cfg )
		{
			$groups = array();
			foreach ( $cfg['groups'] as $g )
			{
				$groups[] = Servers::esc( Servers::groupSummary( $g ) );
			}

			$archive = Servers::esc( Servers::lang( 'mpcs_archive_off', 'wyłączona' ) );
			if ( (int) $cfg['days'] > 0 )
			{
				$error = NULL;
				$target = Archive::archiveTarget( $cfg, $error );
				$days = (int) $cfg['days'] . ' ' . Servers::plural( (int) $cfg['days'], 'dzień', 'dni', 'dni' );
				$archive = $target
					? Servers::esc( Servers::lang( 'mpcs_archive_summary', 'do „%s” po %s bez odświeżenia', array( $target->_title, $days ) ) )
					: '<span class="i-color_warning">' . Servers::esc( $error ) . '</span>';
				if ( $target and !(int) \IPS\Settings::i()->mpcs_mover_enabled )
				{
					$archive .= '<br><span class="i-color_warning">' . Servers::esc( Servers::lang( 'mpcs_err_mover_off', 'archiwizacja jest wyłączona w ustawieniach aplikacji' ) ) . '</span>';
				}
			}

			$edit = $base->setQueryString( array( 'do' => 'edit', 'forum' => $fid ) );
            $moduleLinks='';foreach(array('community','highlights') as $moduleKey){if(\IPS\mpcservers\ModuleAccess::can($moduleKey))$moduleLinks.='<a class="ipsButton ipsButton--inherit ipsButton--tiny" href="'.Servers::esc((string)\IPS\Http\Url::internal('app=mpcservers&module='.$moduleKey.'&controller=settings')->setQueryString('game',\IPS\mpcservers\Presentation::forumConfig($fid)['category'])).'">'.Servers::esc(\IPS\mpcservers\ModuleAccess::definitions()[$moduleKey]['label']).'</a> ';}
            $recommendLink=\IPS\mpcservers\Recommended::allowed()?'<a class="ipsButton ipsButton--inherit ipsButton--tiny" href="'.Servers::esc((string)\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=recommended')->setQueryString('game',\IPS\mpcservers\Presentation::forumConfig($fid)['category'])).'">Polecane publikacje</a> ':'';
			$html .= '<tr><td><a href="' . Servers::esc( (string) $edit ) . '"><strong>' . Servers::esc( Servers::forumName( $fid ) ) . '</strong></a> <span class="i-color_soft">#' . (int) $fid . '</span></td>'
				. '<td>' . ( !empty( $cfg['enabled'] ) ? Servers::esc( Servers::lang( 'mpcs_state_on', 'włączony' ) ) : '<span class="i-color_soft">' . Servers::esc( Servers::lang( 'mpcs_state_off', 'wyłączony' ) ) . '</span>' ) . '</td>'
				. '<td>' . ( $groups ? \implode( '<br>', $groups ) : '<span class="i-color_warning">' . Servers::esc( Servers::lang( 'mpcs_no_groups', 'brak' ) ) . '</span>' ) . '</td>'
				. '<td>' . $archive . '</td>'
				. '<td><code>[serwery=' . (int) $fid . ']</code></td>'
				. '<td>' . ( isset( $counts[ $fid ] ) ? Servers::number( $counts[ $fid ] ) : 0 ) . '</td>'
				. '<td>'.$moduleLinks.$recommendLink.'<a class="ipsButton ipsButton--inherit ipsButton--tiny" href="' . Servers::esc( (string) $edit ) . '">' . Servers::esc( Servers::lang( 'mpcs_edit', 'Edytuj' ) ) . '</a></td></tr>';
		}
		$html .= '</tbody></table></div>';
		$html .= '<p class="i-color_soft mpcs-acp-note">' . Servers::esc( Servers::lang( 'mpcs_forums_note', 'Spis wstawiasz znacznikiem w poście, np. [serwery] (dział tematu), [serwery=195] albo [serwery=195,299]. Znacznik działa w postach osób z ustawień aplikacji.' ) ) . '</p>';

		\IPS\Output::i()->output = $html;
	}

	/**
	 * Przenieś dane ze starej wtyczki
	 *
	 * @return	void
	 */
	protected function import()
	{
        \IPS\mpcservers\Modules\Core\Registry::admin('minecraft');\IPS\mpcservers\Modules\Core\Registry::admin('metin2');
		\IPS\Session::i()->csrfCheck();
		$stats = Servers::importFromPlugin();
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=mpcservers&module='.$this->gameModule().'&controller=publications' ),
			Servers::lang( 'mpcs_imported', 'Przeniesiono: działy %d, tematy z prefiksami %d, wpisy dziennika %d. Teraz odinstaluj wtyczkę.', array( $stats['forums'], $stats['topics'], $stats['log'] ) )
		);
	}

	/**
	 * Dodawanie i edycja działu
	 *
	 * @return	void
	 */
	protected function edit()
	{
		$base = \IPS\Http\Url::internal( 'app=mpcservers&module='.$this->gameModule().'&controller=publications' );

		$forumId = (int) \IPS\Request::i()->forum;
		$cfg = $forumId ? Servers::forum( $forumId ) : NULL;
        if($cfg && \IPS\mpcservers\Modules\Core\Registry::kind(\IPS\mpcservers\Presentation::forumConfig($forumId)['category'])!==$this->gameModule()){\IPS\Output::i()->error('Dział należy do innego modułu gry.','SLS/GAME',403,'');}
		if ( $forumId and !$cfg )
		{
			\IPS\Output::i()->error( 'node_error', '2MPCS/1', 404, '' );
			return;
		}

		$forum = NULL;
		if ( $forumId )
		{
			try
			{
				$forum = \IPS\forums\Forum::load( $forumId );
			}
			catch ( \OutOfRangeException $e ) { }
		}

		\IPS\Output::i()->title = $cfg
			? Servers::lang( 'mpcs_edit_title', 'Dział publikacji: %s', array( Servers::forumName( $forumId ) ) )
			: Servers::lang( 'mpcs_new_title', 'Nowy dział publikacji' );
		\IPS\Output::i()->breadcrumb[] = array( $base, Servers::lang( 'mpcs_forums_title', 'Działy publikacji' ) );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Output::i()->title );

		$archiveDefault = 0;
		if ( $cfg and (int) $cfg['archive_id'] )
		{
			try
			{
				$archiveDefault = \IPS\forums\Forum::load( (int) $cfg['archive_id'] );
			}
			catch ( \OutOfRangeException $e ) { }
		}

		$form = new \IPS\Helpers\Form( 'mpcs_forum', 'save' );
		$form->addHeader( 'mpcs_h_forum' );
		if ( !$cfg )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'mpcs_f_forum', NULL, TRUE, array(
				'class'				=> '\IPS\forums\Forum',
				'clubs'				=> FALSE,
				'permissionCheck'	=> function( $node ) {
					return ( $node->sub_can_post and !$node->redirect_on and !\IPS\mpcservers\Servers::forum( $node->_id ) );
				},
			), function( $value ) {
				if ( \is_object( $value ) and \IPS\mpcservers\Servers::forum( $value->_id ) )
				{
					throw new \DomainException( 'mpcs_err_forum_exists' );
				}
				if ( \is_object( $value ) and ( !$value->sub_can_post or $value->redirect_on ) )
				{
					throw new \DomainException( 'mpcs_err_forum_nopost' );
				}
			} ) );
		}
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_f_enabled', $cfg ? (bool) $cfg['enabled'] : TRUE ) );

        $pc=\IPS\mpcservers\Presentation::forumConfig($forumId);
        $form->addHeader('sps_h_presentation');
        if ($cfg) {
            $options=array();
            foreach (\IPS\Db::i()->select('tid,title','forums_topics',array('forum_id=? AND state<>?',(int)$forumId,'link'),'tid DESC',2000) as $row) { $options[(int)$row['tid']]='#'.(int)$row['tid'].' - '.$row['title']; }
            foreach ((array)$pc['directory_topics'] as $tid) {
                if (!isset($options[(int)$tid])) {
                    try { $t=\IPS\forums\Topic::load((int)$tid); $options[(int)$tid]='#'.(int)$tid.' - '.$t->title; } catch (\OutOfRangeException $e) { }
                }
            }
            $form->add(new \IPS\Helpers\Form\Select('sps_f_directory_topics',(array)$pc['directory_topics'],FALSE,array('options'=>$options,'multiple'=>TRUE,'parse'=>'normal'),function($value) use ($options) {
                foreach ((array)$value as $id) { if (!isset($options[(int)$id])) { throw new \DomainException('Wybierz temat z listy działu.'); } }
            }));
        }
        $form->add(new \IPS\Helpers\Form\YesNo('sps_f_enabled',(bool)$pc['enabled']));
        $editorGame=$cfg?$pc['category']:\IPS\mpcservers\Modules\Core\Registry::canonicalGame($this->gameModule());
        $pc['category']=$editorGame;
        $editorLabel=$cfg?(\IPS\mpcservers\Presentation::categories()[$pc['category']]??''):'';
        $form->addMessage('Gra: '.\IPS\mpcservers\Modules\Core\Registry::definitions()[$this->gameModule()]['label']);
        if(\IPS\mpcservers\Modules\Core\Registry::can('highlights'))$form->addMessage('Serwer dnia, tygodnia, miesiąca i roku oraz rankingi prefiksów trybów ustawiasz w module <a href="'.htmlspecialchars((string)\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=settings'),ENT_QUOTES,'UTF-8').'">MPC Poleca i TOP serwery</a>.','ipsMessage ipsMessage--info',FALSE);
        foreach(array($editorGame=>$editorLabel?:'Nowa gra') as $game=>$label) {
            \IPS\mpcservers\ModeFilters::gameIconField($form,$game,$label);
            \IPS\mpcservers\GameRewards::field($form,$game);if($this->gameModule()==='minecraft')\IPS\mpcservers\Modules\Minecraft\Versions::field($form,$game);
            if(\IPS\mpcservers\ModuleAccess::can('people')) {
            $lines=array();foreach(\IPS\mpcservers\Crew::roles($game) as $key=>$name)$lines[]=$key.'='.$name;
            $field=new \IPS\Helpers\Form\TextArea('sps_roles_'.$game,implode("\n",$lines),FALSE,array(),function($value) use ($game){
                foreach(preg_split('/\R/',trim($value)) as $line){if(trim($line)==='')continue;if(!preg_match('/^[a-z0-9_]{1,40}=.{1,120}$/uD',trim($line)))throw new \DomainException('Podaj po jednej roli: identyfikator=Nazwa (do 120 znaków).');}
            },NULL,NULL,'sps_role_panel_'.$game);
            $field->label='Role ekipy: '.$label;$field->description='Role wspólne dla wszystkich działów przypisanych do tej gry. Jedna rola w wierszu: identyfikator=Nazwa. owner i technician są zawsze dostępne. Role nie nadają grup ani uprawnień IPS. Usunięte role pozostają w historii.';$form->add($field);
            }
        }

        if($this->gameModule()==='metin2'){$form->add(new \IPS\Helpers\Form\YesNo('sps_f_tcp',!empty($pc['tcp'])));$form->add(new \IPS\Helpers\Form\YesNo('sps_f_html',(bool)$pc['html']));
        $form->add(new \IPS\Helpers\Form\TextArea('sps_f_hosts',$pc['hosts'],FALSE,array(),function($v){\IPS\mpcservers\Presentation::validateHosts($v);}));}
		$form->addHeader( 'mpcs_h_prefixes' );
		$form->add( new \IPS\Helpers\Form\TextArea( 'mpcs_f_defs', $cfg ? (string) $cfg['defs'] : '', FALSE, array( 'rows' => 8 ), function( $value ) {
			$parsed = \IPS\mpcservers\Servers::parseDefs( $value );
			if ( $parsed['errors'] )
			{
				throw new \DomainException( \implode( ' ', $parsed['errors'] ) );
			}
		} ) );

        $modeImageFields=\IPS\mpcservers\ModeFilters::imageFields($form,$cfg['groups']??array(),$pc);
		$form->addHeader( 'mpcs_h_archive' );
		$form->add( new \IPS\Helpers\Form\Number( 'mpcs_f_days', $cfg ? (int) $cfg['days'] : 30, FALSE, array( 'min' => 0, 'max' => 3650 ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'mpcs_f_archive', $archiveDefault, FALSE, array(
			'class'				=> '\IPS\forums\Forum',
			'zeroVal'			=> 'mpcs_f_archive_default',
			'clubs'				=> FALSE,
			'permissionCheck'	=> function( $node ) use ( $forumId ) {
				return ( (int) $node->_id !== $forumId and $node->sub_can_post and !$node->redirect_on and !\IPS\mpcservers\Servers::enabledForum( $node->_id ) );
			},
		), function( $value ) use ( $forumId ) {
			if ( \is_object( $value ) and ( (int) $value->_id === $forumId or \IPS\mpcservers\Servers::enabledForum( $value->_id ) ) )
			{
				throw new \DomainException( 'mpcs_err_archive_pub_form' );
			}
		} ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'mpcs_f_activity', $cfg ? $cfg['activity'] : 'refresh', FALSE, array( 'options' => array( 'refresh' => 'mpcs_act_refresh', 'author' => 'mpcs_act_author' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_f_lock', $cfg ? (bool) $cfg['lock_moved'] : TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_f_skip_pinned', $cfg ? (bool) $cfg['skip_pinned'] : TRUE ) );

		$form->addHeader( 'mpcs_h_listing' );
		$form->add( new \IPS\Helpers\Form\TextArea( 'mpcs_f_description', $cfg ? (string) $cfg['description'] : '', FALSE, array( 'rows' => 3, 'maxLength' => 2000 ) ) );

		if ( $cfg )
		{
			$form->addHeader( 'mpcs_h_delete' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'mpcs_f_delete', FALSE ) );
		}

		$values = $form->values();
		if ( $values and !$cfg and \is_object( $values['mpcs_f_archive'] ) and (int) $values['mpcs_f_archive']->_id === (int) $values['mpcs_f_forum']->_id )
		{
			/* Nowy dział: wybrany dział i archiwum to to samo */
			$form->error = Servers::lang( 'mpcs_err_archive_same', 'Dział-archiwum musi być innym działem niż dział publikacji.' );
			$values = FALSE;
		}
		if ( $values )
		{
			$id = $cfg ? $forumId : (int) $values['mpcs_f_forum']->_id;
            \IPS\Session::i()->csrfCheck();
            if(Servers::forum($id) && \IPS\mpcservers\Modules\Core\Registry::kind(\IPS\mpcservers\Presentation::forumConfig($id)['category'])!==$this->gameModule())throw new \DomainException('Dział należy do innego modułu gry.');
			if ( $cfg and !empty( $values['mpcs_f_delete'] ) )
			{
				Servers::deleteForum( $id );
				\IPS\Output::i()->redirect( $base, 'mpcs_deleted' );
				return;
			}

			Servers::saveForum( $id, array(
				'enabled'		=> $values['mpcs_f_enabled'] ? 1 : 0,
				'defs'			=> \trim( \str_replace( array( "\r\n", "\r" ), "\n", (string) $values['mpcs_f_defs'] ) ),
				'days'			=> \max( 0, (int) $values['mpcs_f_days'] ),
				'archive_id'	=> \is_object( $values['mpcs_f_archive'] ) ? (int) $values['mpcs_f_archive']->_id : 0,
				'activity'		=> ( $values['mpcs_f_activity'] === 'author' ) ? 'author' : 'refresh',
				'lock_moved'	=> $values['mpcs_f_lock'] ? 1 : 0,
				'skip_pinned'	=> $values['mpcs_f_skip_pinned'] ? 1 : 0,
				'description'	=> \trim( (string) $values['mpcs_f_description'] ),
			) );
            $gamePlan=array('reuse'=>FALSE);$game=$editorGame;\IPS\mpcservers\Modules\Core\Registry::bind($game,$this->gameModule());
            if (!$gamePlan['reuse']) {
            if($game!==$editorGame){foreach(array('sps_game_icon_','sps_roles_','sps_hl_','sps_hl_modes_') as $prefix){foreach(array_keys($values) as $key){if($key===$prefix.$editorGame || strpos($key,$prefix.$editorGame.'_')===0){$values[$prefix.$game.substr($key,strlen($prefix.$editorGame))]=$values[$key];}}}}
            \IPS\mpcservers\ModeFilters::saveGameIcon($game,$values);
            if(\IPS\mpcservers\ModuleAccess::can('people')) {
            $crewRoles=json_decode((string)\IPS\Settings::i()->sps_crew_roles,TRUE) ?: array();$crewRoles[$game]=array();
            foreach(preg_split('/\R/',trim((string)($values['sps_roles_'.$game]??''))) as $line){if(strpos($line,'=')!==FALSE){list($key,$name)=explode('=',trim($line),2);$crewRoles[$game][$key]=trim($name);}}
            \IPS\Settings::i()->changeValues(array('sps_crew_roles'=>json_encode($crewRoles,JSON_UNESCAPED_UNICODE)));
            }
            }
            \IPS\mpcservers\GameRewards::save($game,$values);if($this->gameModule()==='minecraft')\IPS\mpcservers\Modules\Minecraft\Versions::save($game,$values);
            \IPS\mpcservers\Presentation::saveForumConfig($id,array('mode_images'=>\IPS\mpcservers\ModeFilters::imagesFromForm($modeImageFields,$values),'directory_topics'=>array_values(array_filter(array_map('intval',(array)($values['sps_f_directory_topics']??array())))),'enabled'=>$values['sps_f_enabled']?1:0,'category'=>$game,'tcp'=>$this->gameModule()==='metin2'&&!empty($values['sps_f_tcp'])?1:0,'html'=>$this->gameModule()==='metin2'&&!empty($values['sps_f_html'])?1:0,'hosts'=>trim($values['sps_f_hosts']??$pc['hosts'])));
			\IPS\Output::i()->redirect( $base, 'saved' );
			return;
		}

		$editorStrings = array(
			'empty'		=> Servers::lang( 'mpcs_ed_empty', 'Brak grup prefiksów: autorzy nie będą musieli niczego wybierać. Kliknij „Dodaj grupę prefiksów”.' ),
			'name'		=> Servers::lang( 'mpcs_ed_name', 'Nazwa grupy, np. Wersja' ),
			'mode'		=> Servers::lang( 'mpcs_ed_mode', 'Wybór' ),
			'single'	=> Servers::lang( 'mpcs_ed_single', 'jedna opcja' ),
			'multi'		=> Servers::lang( 'mpcs_ed_multi', 'kilka opcji' ),
			'max'		=> Servers::lang( 'mpcs_ed_max', 'najwyżej' ),
			'nolimit'	=> Servers::lang( 'mpcs_ed_nolimit', 'bez limitu' ),
			'up'		=> Servers::lang( 'mpcs_ed_up', 'W górę' ),
			'down'		=> Servers::lang( 'mpcs_ed_down', 'W dół' ),
			'delgroup'	=> Servers::lang( 'mpcs_ed_delgroup', 'Usuń grupę' ),
			'delopt'	=> Servers::lang( 'mpcs_ed_delopt', 'Usuń opcję' ),
			'newopt'	=> Servers::lang( 'mpcs_ed_newopt', 'Dodaj opcję i naciśnij Enter' ),
			'addgroup'	=> Servers::lang( 'mpcs_ed_addgroup', 'Dodaj grupę prefiksów' ),
			'raw'		=> Servers::lang( 'mpcs_ed_raw', 'Edytuj jako tekst' ),
			'visual'	=> Servers::lang( 'mpcs_ed_visual', 'Wróć do edytora' ),
		);
		$marker = '<div data-mpcs-editor';
		foreach ( $editorStrings as $k => $v )
		{
			$marker .= ' data-l-' . $k . '="' . Servers::esc( $v ) . '"';
		}
		$marker .= '></div>';

		$html = Servers::assets( 'editor' ) . $marker . '<div class="ipsBox">' . $form . '</div>' . \IPS\mpcservers\Media::assets();
		if ( $cfg )
		{
			$html .= '<div class="ipsBox i-padding_3 mpcs-preview">' . Archive::previewHtml( $cfg ) . '</div>';
		}
		\IPS\Output::i()->output = $html;
	}

    protected function presentationSettings()
    {
        \IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module='.$this->gameModule().'&controller=settings'));
    }
}
