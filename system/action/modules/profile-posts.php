<?php

use Sunlight\Database\Database as DB;
use Sunlight\GenericTemplates;
use Sunlight\Paginator;
use Sunlight\Post\Post;
use Sunlight\Post\PostService;
use Sunlight\Router;
use Sunlight\Settings;
use Sunlight\User;
use Sunlight\Util\Request;
use Sunlight\Util\StringManipulator;

defined('SL_ROOT') or exit;

if (!User::isLoggedIn() && Settings::get('notpublicsite')) {
    $_index->unauthorized();
    return;
}

$id = StringManipulator::slugify(Request::get('id'), false);
$query = DB::queryRow('SELECT u.id,u.username,u.publicname,u.public,g.level FROM ' . DB::table('user') . ' u JOIN ' . DB::table('user_group') . ' g ON u.group_id=g.id WHERE u.username=' . DB::val($id));

if ($query === false) {
    $_index->notFound();
    return;
}

if (!$query['public'] && !User::checkLevel($query['id'], $query['level'])) {
    $_index->unauthorized();
    return;
}

// output
$_index->title = _lang('mod.profile.posts', ['%user%' => $query[$query['publicname'] !== null ? 'publicname' : 'username']]);

// backlink
$_index->backlink = Router::module('profile', ['query' => ['id' => $id]]);

// table
[$columns, $joins, $cond, $count] = Post::createFilter('post', [Post::SECTION_COMMENT, Post::ARTICLE_COMMENT, Post::BOOK_ENTRY, Post::FORUM_TOPIC, Post::PLUGIN], [], 'post.author=' . $query['id'], true);

$paging = Paginator::paginate(
    Router::module('profile-posts', ['query' => ['id' => $id]]),
    15,
    $count
);

if (Paginator::atTop()) {
    $output .= $paging['paging'];
}

$posts = DB::query('SELECT ' . $columns . ' FROM ' . DB::table('post') . ' post ' . $joins . ' WHERE ' . $cond . ' ORDER BY post.time DESC ' . $paging['sql_limit']);

if (DB::size($posts) != 0) {
    while ($post = DB::row($posts)) {
        $output .= '<div class="post">
<div class="post-head">
    <a href="' . _e(Router::postPermalink($post['id'])) . '" class="post-author">' . PostService::getPostTitle($post) . '</a>
    <span class="post-info">(' . GenericTemplates::renderTime($post['time'], 'post') . ')</span>
</div>
<div class="post-body">' . Post::render($post['text']) . '</div>
</div>';
    }

    if (Paginator::atBottom()) {
        $output .= $paging['paging'];
    }
} else {
    $output .= '<p>' . _lang('global.nokit') . '</p>';
}
