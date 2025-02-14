<?php

use Sunlight\Hcm;
use Sunlight\Post\PostService;
use Sunlight\Database\Database as DB;
use Sunlight\GenericTemplates;
use Sunlight\Post\Post;
use Sunlight\Router;
use Sunlight\User;
use Sunlight\Util\Arr;
use Sunlight\Util\StringManipulator;

return function ($limit = null, $pages = null, $type = null) {
    Hcm::normalizeArgument($limit, 'int', true);
    Hcm::normalizeArgument($pages, 'string', true);
    Hcm::normalizeArgument($type, 'string', true);

    $result = '';

    if ($limit !== null && $limit >= 1) {
        $limit = abs($limit);
    } else {
        $limit = 10;
    }

    $post_types =  [
        'section' => Post::SECTION_COMMENT,
        'article' => Post::ARTICLE_COMMENT,
        'book' => Post::BOOK_ENTRY,
        'topic' => Post::FORUM_TOPIC,
        'plugin' => Post::PLUGIN,
    ];

    if ($pages !== null) {
        $homes = Arr::removeValue(explode('-', $pages), '');
    } else {
        $homes = [];
    }

    if ($type !== null) {
        if (isset($post_types[$type])) {
            $type = $post_types[$type];
        } elseif (!in_array($type, $post_types)) {
            $type = Post::SECTION_COMMENT;
        }

        $types = [$type];
    } else {
        $types = $post_types;
    }

    [$columns, $joins, $cond] = Post::createFilter('post', $types, $homes);
    $userQuery = User::createQuery('post.author');
    $columns .= ',' . $userQuery['column_list'];
    $joins .= ' ' . $userQuery['joins'];
    $query = DB::query(
        'SELECT ' . $columns . ' FROM ' . DB::table('post') . ' post ' . $joins
        . ' WHERE ' . $cond
        . ' ORDER BY id DESC LIMIT ' . $limit
    );

    while ($item = DB::row($query)) {
        if ($item['author'] != -1) {
            $authorname = Router::userFromQuery($userQuery, $item);
        } else {
            $authorname = PostService::renderGuestName($item['guest']);
        }

        $result .= '
<div class="list-item">
<h2 class="list-title"><a href="' . _e(Router::postPermalink($item['id'])) . '">' . PostService::getPostTitle($item) . '</a></h2>
<p class="list-perex">' . StringManipulator::ellipsis(strip_tags(Post::render($item['text'])), 255) . '</p>
' . GenericTemplates::renderInfos([
    [_lang('global.postauthor'), $authorname],
    [_lang('global.time'), GenericTemplates::renderTime($item['time'], 'post')],
]) . "</div>\n";
    }

    return $result;
};
