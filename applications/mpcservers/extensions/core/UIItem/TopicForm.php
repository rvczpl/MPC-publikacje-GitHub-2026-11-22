<?php
/**
 * System Publikacji Serwerów MPCforum - formularz tematu i lista tematów działu (IPS5 UI extension)
 * Zastępuje hooki IPS4: Topic::formElements/processForm/save (prefiksy, dane publikacji, ekipa)
 * oraz kontroler działu (archiwizacja tematów po terminie przed wyświetleniem listy).
 */
namespace IPS\mpcservers\extensions\core\UIItem;

use IPS\Content\Item as BaseItem;
use IPS\forums\Topic;
use IPS\Helpers\Table\Content;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Core\Archive;
use IPS\mpcservers\Modules\Core\OrdinaryTopic;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Servers;
use IPS\Node\Model;
use IPS\Output\UI\Item;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class TopicForm extends Item
{
	/**
	 * @brief	Class to extend
	 */
	public static ?string $class = Topic::class;

	/**
	 * @brief	Forums already checked for expired publications in this request
	 */
	protected static array $archived = array();

	/**
	 * Badges: MPC Poleca and server modes (topic list and topic header)
	 *
	 * @param	BaseItem	$item
	 * @return	array
	 */
	public function badges( BaseItem $item ): array
	{
        if(!\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('badges'))return array();
		if ( !( $item instanceof Topic ) )
		{
			return array();
		}
		$badges = array();
        $row=NULL;
		try
		{
			$row = Presentation::get( $item->tid );
			if ( $row and !empty( $row['featured'] ) and Presentation::visible( $item, $row ) )
			{
				$badges[] = new \IPS\Helpers\Badge( '', 'MPC Poleca', '', 'fa-solid fa-star', array( 'sps-recommended' ) );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
        if($row && empty($row['featured'])){
            try{
                static $winners=array();
                $game=(string)$row['category'];
                foreach(array_unique(array('',$game)) as $scope){
                    if(!isset($winners[$scope]))$winners[$scope]=\IPS\mpcservers\Modules\Highlights\Highlights::winners($scope);
                    foreach($winners[$scope][(int)$item->tid]['awards']??array() as $award){
                        $badges[]=new \IPS\Helpers\Badge('',$award['title'],'','',array('sps-top-award'));
                    }
                }
            }catch(\Throwable $e){Servers::logError($e);}
        }
        if(count($badges)>1)$badges=array(new \IPS\Helpers\Badge('','TOP','','',array('sps-top-award')));
		return array_merge( $badges, Servers::topicBadgeObjects( $item ) );
	}

	/**
	 * Additional form elements (topic create / edit form)
	 *
	 * @param	BaseItem|null	$item
	 * @param	Model|null		$container
	 * @return	array
	 */
	public function formElements( ?BaseItem $item, ?Model $container ): array
	{
		$return = array();
		try
		{
			if ( !$container and $item )
			{
				$container = $item->container();
			}

			/* Prefixes (modes) */
			if ( !Servers::pluginActive() and $element = Servers::formElement( $item, $container ) )
			{
				$return['mpcs_prefixes'] = $element;
			}

			/* Publication details and crew */
			if ( $container and Presentation::enabled( $container->_id ) )
			{
				foreach ( Presentation::fields( $item, $container->_id ) as $key => $field )
				{
					$return[ $key ] = $field;
				}
			}

			/* IPS5 appends extension fields at the end of the form: move ours right after the title, as in IPS4 */
			if ( $return )
			{
				$return['mpcs_form_order'] = array( static::orderScript( array_keys( $return ) ) );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
		return $return;
	}

	/**
	 * After the topic was created or edited
	 *
	 * @param	BaseItem	$item
	 * @param	array		$values
	 * @return	void
	 */
	public function formPostSave( BaseItem $item, array $values ): void
	{
		if ( !( $item instanceof Topic ) or !$item->tid )
		{
			return;
		}

		/* Prefixes */
		try
		{
			$prefixes = NULL;
			if ( Presentation::enabled( $item->forum_id ) and OrdinaryTopic::selected( $item ) )
			{
				$prefixes = array();
			}
			elseif ( array_key_exists( 'mpcs_prefixes', $values ) )
			{
				$prefixes = Servers::normalizePicked( $values['mpcs_prefixes'] );
			}
			if ( $prefixes !== NULL )
			{
				Servers::storeAttrs( (int) $item->tid, (int) $item->forum_id, $prefixes );
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}

		/* Publication details and crew */
		try
		{
			if ( !Presentation::enabled( $item->forum_id ) )
			{
				return;
			}
			$crew = NULL;
			if ( array_key_exists( 'sps_crew', $values ) )
			{
				$crew = OrdinaryTopic::selected( $item ) ? array() : Crew::normalize( $values['sps_crew'], $item, $item->forum_id );
			}
			elseif ( OrdinaryTopic::selected( $item ) and !Presentation::isDirectory( $item ) )
			{
				$crew = array();
			}

			$details = NULL;
			if ( OrdinaryTopic::selected( $item ) )
			{
				$details = OrdinaryTopic::values( $item->forum_id );
			}
			elseif ( array_key_exists( 'sps_details', $values ) )
			{
				$details = Presentation::normalize( $values['sps_details'], $item->forum_id, $item->tid );
			}

			if ( $details !== NULL )
			{
				Presentation::store( $item->tid, $details );
				if ( $crew !== NULL )
				{
					Crew::sync( $item, $crew );
				}
			}
		}
		catch ( \Throwable $e )
		{
			Servers::logError( $e );
		}
	}

	/**
	 * Topic list of a forum: archive expired publications before the list is read (IPS4: forum controller hook)
	 *
	 * @param	BaseItem|null	$item
	 * @param	Content			$table
	 * @param	array			$where
	 * @return	void
	 */
	public function contentTableWhere( ?BaseItem $item, Content $table, array &$where ): void
	{
        // Kept for compatibility. Page reads never trigger archival.
	}

	/**
	 * Script that moves our rows after the title row
	 *
	 * @param	array	$names	Field names
	 * @return	string
	 */
	protected static function orderScript( array $names ): string
	{
		$names = json_encode( array_values( array_filter( $names, fn( $n ) => $n !== 'mpcs_form_order' ) ) );
		return <<<HTML
<script>(function(){function rowOf(n){var f=document.querySelector('form label.ipsFieldRow__label[for="'+n+'"]')||document.querySelector('form [name="'+n+'"]')||document.querySelector('form [name^="'+n+'["]')||(n==='mpcs_prefixes'?document.querySelector('form [data-mpcs-picker]'):null);return f?f.closest('.ipsFieldRow'):null;}function move(){var names={$names};var title=document.querySelector('form [name="topic_title"]')||document.querySelector('form [name="title"]');if(!title)return;var anchor=title.closest('.ipsFieldRow');if(!anchor)return;names.forEach(function(n){var row=rowOf(n);if(row&&row!==anchor&&!row.contains(anchor)){anchor.after(row);anchor=row;}});var me=document.querySelector('[data-mpcs-form-order]');if(me){var r=me.closest('.ipsFieldRow');if(r)r.hidden=true;}}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',move);else move();})();</script><span data-mpcs-form-order hidden></span>
HTML;
	}
}
