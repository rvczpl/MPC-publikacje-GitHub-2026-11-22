<?php
namespace IPS\mpcservers\Modules\Lists;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Highlights\Recommended;

use IPS\mpcservers\Modules\Core\Servers;

if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }

/** Read-only hovercards. Never run directory forms, embeds or scripts inside a preview. */
class TopicPreview
{
    public static function excerpt($html)
    {
        $html = preg_replace('~<(script|style|iframe)\b[^>]*>.*?</\1>~is', '', (string)$html);
        $html = preg_replace('~</?(?:p|div|li|br|h[1-6])\b[^>]*>~i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\[\/?serwery(?:\s*=\s*[^\]]*)?\]/iu', '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        return Directory::esc(mb_substr($text, 0, 440) . (mb_strlen($text) > 440 ? '...' : ''));
    }

    public static function directory($topic)
    {
        if (!Presentation::isDirectory($topic) || !Presentation::enabled($topic->forum_id)) { return NULL; }
        if (!$topic->canView() || $topic->hidden()) { return ''; }
        $e = array(Directory::class, 'esc');
        $game = Presentation::forumConfig($topic->forum_id)['category'];
        $out = '<div class="cTopicHovercard"><div class="i-padding_3 cTopicHovercard_container ipsScrollbar"><h3 class="">'.$e($topic->title).'</h3><p class="i-color_soft i-font-size_-1">Podgląd spisu serwerów</p><ul class="">';
        $rows = iterator_to_array(\IPS\Db::i()->select('p.*', array('mpcservers_presentations','p'), array('t.forum_id=? AND t.approved=1 AND t.state<>? AND p.is_server=1 AND p.category=? AND p.tid<>?', (int)$topic->forum_id, 'link', $game, (int)$topic->tid), 'p.featured DESC,p.bumped DESC,p.tid DESC', 50)->join(array('forums_topics','t'), 't.tid=p.tid', 'INNER'));
        $topics = Presentation::loadTopics(array_column($rows, 'tid'));
        $count = 0;
        foreach ($rows as $row) {
            $server = $topics[(int)$row['tid']] ?? NULL;
            if (!$server || !Presentation::visible($server, $row)) { continue; }
            $out .= '<li style="padding:10px 0;border-top:1px solid rgba(128,128,128,.2);overflow-wrap:anywhere">'.(!empty($row['featured']) ? Recommended::titleTag($server) : '').Servers::topicBadges($server).' <a href="'.$e($server->url()).'"><strong>'.$e($server->title).'</strong></a><div class="i-font-size_-1 i-color_soft">'.$e(Presentation::status($row)).'</div></li>';
            if (++$count === 5) { break; }
        }
        if (!$count) { $out .= '<li class="i-color_soft">Brak serwerów do wyświetlenia.</li>'; }
        return $out.'</ul><p class="i-margin-top_3"><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.$e($topic->url()).'">Otwórz pełny spis</a></p></div></div>';
    }

    public static function post($topic, $comment)
    {
        if (!$topic->canView() || !$comment->canView()) { return ''; }
        $native = $comment->content();
        if (!$comment->mapped('first')) { return $native; }
        $row = Presentation::get($topic->tid);
        if (!$row || !Presentation::visible($topic, $row)) {
            if (Presentation::containsDirectoryTag($native)) {
                return '<p>'.static::excerpt($native).'</p><a href="'.Directory::esc($topic->url()).'">Otwórz temat</a>';
            }
            return $native;
        }
        $out = '<div style="overflow-wrap:anywhere">'.(!empty($row['featured']) ? Recommended::titleTag($topic) : '').Servers::topicBadges($topic);
        $out .= '<p class="i-color_soft">'.Directory::esc(Presentation::status($row));
        if (!empty($row['open_date'])) { $out .= ' · '.Directory::esc(Presentation::opening($row)); }
        $out .= '</p>';
        $excerpt = static::excerpt($native);
        if ($excerpt !== '') { $out .= '<p>'.$excerpt.'</p>'; }
        if ($row['mode'] === 'html') { $out .= '<p class="i-color_soft">Prezentacja serwera jest dostępna w temacie.</p>'; }
        return $out.'<a class="ipsButton ipsButton--small ipsButton--inherit" href="'.Directory::esc($topic->url()).'">Otwórz publikację serwera</a></div>';
    }
}
