<?php
namespace IPS\mpcservers\Modules\Lists;
use IPS\mpcservers\Modules\Core\Presentation;

use IPS\mpcservers\Modules\Core\Servers;

if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }

/** Plain-text descriptions shared by IPS metadata and native link embeds. */
class TopicSeo
{
    public static function description($topic, $length = 180)
    {
        if (!Presentation::enabled($topic->forum_id)) { return NULL; }
        $directory = Presentation::isDirectory($topic);
        $row = $directory ? NULL : Presentation::get($topic->tid);
        if (!$directory && (!$row || empty($row['is_server']))) { return NULL; }
        if (!$topic->canView() || $topic->hidden()) { return ''; }
        if (!$directory && !Presentation::visible($topic, $row)) { return ''; }
        $game = Presentation::forumConfig($topic->forum_id)['category'];
        $label = Presentation::categories()[$game] ?? '';
        if ($directory) {
            $text = 'Spis serwerów '.$label.'. Przeglądaj publikacje, wybierz tryb serwera i sprawdź daty otwarcia. Podbijaj ulubione serwery i poznaj rankingi MPCforum.';
        } else {
            $text = $topic->title.' - '.$label.'. ';
            if (!empty($row['featured'])) { $text .= 'MPC Poleca. '; }
            $modes = static::plain(Servers::topicBadges($topic));
            if ($modes !== '') { $text .= 'Tryb: '.$modes.'. '; }
            if (!empty($row['open_date'])) { $text .= Presentation::opening($row).'. '; }
            $post = $topic->firstComment();
            if ($post && $post->canView()) { $text .= static::plain($post->content()); }
            if ($row['mode'] === 'html' && (!$post || trim(static::plain($post->content())) === '')) {
                $text .= 'Prezentacja serwera dostępna w temacie na MPCforum.';
            }
        }
        $text = static::plain(Directory::esc($text));
        $length = max(4, min(1000, (int)$length));
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length - 3);
            $space = mb_strrpos($text, ' ');
            if ($space !== FALSE && $space > $length / 2) { $text = mb_substr($text, 0, $space); }
            $text = rtrim($text, ' .,;:').'...';
        }
        return $text;
    }

    public static function plain($html)
    {
        return html_entity_decode(TopicPreview::excerpt($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
