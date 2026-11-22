<?php
/**
 * System Publikacji Serwerów MPCforum - widok tematu i podglądy (IPS5 Loader)
 * Zastępuje hooki IPS4: szablon topic() dla tematów-spisów, znaczniki [serwery] w widoku tematu,
 * Topic::metaDescription() oraz tekst znaczników w podglądach treści.
 */
namespace IPS\mpcservers\extensions\core\Loader;

use IPS\Dispatcher;
use IPS\Extensions\LoaderAbstract;
use IPS\forums\Topic;
use IPS\mpcservers\Modules\Core\Listing;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Servers as Core;
use IPS\mpcservers\Modules\Core\TagLinks;
use IPS\mpcservers\Modules\Lists\TopicSeo;
use IPS\Output;
use IPS\Request;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Servers extends LoaderAbstract
{
    public function checkForRedirect(): \IPS\Http\Url|null
    {
        \IPS\mpcservers\Modules\Core\PublicationTags::prepareRequest();
        return NULL;
    }

    /** Versioned UI fix for duplicate metadata in publication feed cards. */
    public function js(): array
    {
        if (!Dispatcher::hasInstance() || Dispatcher::i()->controllerLocation !== 'front') return [];
        $base=\IPS\Http\Url::baseUrl().'applications/mpcservers/interface/';
        $urls=[];
        if (\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('loader')) $urls[]=$base.'topic-feed.js?v=60002';
        if (\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('badges')) {
            foreach (['topic-title.js?v=60104','prefix-rotation.js?v=60103','list-prefixes.js?v=60104'] as $file) $urls[]=$base.$file;
        }
        if (\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('shared_badges')) $urls[]=$base.'shared-badges.js?v=60111';
        return $urls ? [$urls] : [];
    }

	/**
	 * CSS: colours of the server mode badges (topic lists, topic header)
	 *
	 * @return	array
	 */
    public function css(): array
    {
        if (!Dispatcher::hasInstance() || Dispatcher::i()->controllerLocation !== 'front') return [];
        $files=[];
        if (\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('badges')) $files[]=\IPS\Theme::i()->css('badges.css','mpcservers','interface');
        if (\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('shared_badges')) $files[]=[\IPS\Http\Url::baseUrl().'applications/mpcservers/interface/shared-badges.css?v=60112'];
        return $files;
    }

	/**
	 * Runs after the controller, before the page is rendered
	 *
	 * @return	void
	 */
	public function onFinish(): void
	{
        if(!\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('loader'))return;
		if ( !Dispatcher::hasInstance() or Dispatcher::i()->controllerLocation !== 'front' )
		{
			return;
		}

		try
		{
			$dispatcher = Dispatcher::i();
			$topicView = ( $dispatcher->application?->directory === 'forums' && $dispatcher->module?->key === 'forums' && $dispatcher->controller === 'topic' && !Request::i()->do && Request::i()->id );
			if ( $topicView )
			{
				$this->topicView();
			}
			else
			{
				$this->previews();
			}
		}
		catch ( \Throwable $e )
		{
			Core::logError( $e );
		}
	}

	/**
	 * Topic view
	 *
	 * @return	void
	 */
	protected function topicView(): void
	{
		try
		{
			$topic = Topic::load( (int) Request::i()->id );
		}
		catch ( \OutOfRangeException )
		{
			return;
		}
		if ( !$topic->canView() )
		{
			return;
		}
		$output = Output::i();



		/* Directory topics show the list only (posts remain stored) */
		if ( !Request::i()->isAjax() and Presentation::enabled( $topic->forum_id ) and Presentation::isDirectory( $topic ) )
		{
			$title = htmlspecialchars( (string) $topic->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$output->output = '<div class="sps-directory-topic"><header class="ipsPageHeader ipsBox i-padding_3 i-margin-bottom_3 sps-list-title"><h1 class="ipsPageHeader__title">' . $title . '</h1><button type="button" class="sps-help-button" data-sps-help aria-label="Zasady podbijania serwerów" aria-haspopup="dialog" aria-controls="sps-list-help-topic-' . (int) $topic->tid . '">?</button></header>' . Presentation::render( $topic ) . '</div>';
		}
		else
		{
			/* [serwery] stored as links (IPS5) and raw tags of older posts */
			$html = TagLinks::toTags( $output->output );
			if ( !Core::pluginActive() and stripos( $html, '[serwery' ) !== FALSE )
			{
				$html = Listing::processTopicOutput( $html, $topic );
			}
			$output->output = $html;
		}

		/* Meta description (IPS4: Topic::metaDescription hook) */
		$description = TopicSeo::description( $topic );
		if ( $description !== NULL and !array_key_exists( 'description', $output->customMetaTags ) )
		{
			if ( $description === '' )
			{
				unset( $output->metaTags['description'], $output->metaTags['og:description'] );
			}
			else
			{
				$output->metaTags['description'] = $description;
				$output->metaTags['og:description'] = $description;
			}
		}
	}

	/**
	 * Other pages (lists, streams, search, AJAX fragments): raw [serwery] tags of older posts become text
	 *
	 * @return	void
	 */
	protected function previews(): void
	{
		$html = Output::i()->output;
		if ( $html !== '' and stripos( $html, '[serwery' ) !== FALSE )
		{
			Output::i()->output = preg_replace( TagLinks::TAG, 'Spis serwerów', $html ) ?? $html;
		}
	}
}
