<?php

namespace PHPSTORM_META;

registerArgumentsSet(
    'settings',
    'accactexpire',
    'admin_index_custom',
    'admin_index_custom_pos',
    'adminpagelist_mode',
    'adminscheme',
    'adminscheme_dark',
    'antispamtimeout',
    'article_pic_h',
    'article_pic_thumb_h',
    'article_pic_thumb_w',
    'article_pic_w',
    'articlesperpage',
    'artrateexpire',
    'artreadexpire',
    'atreplace',
    'author',
    'bbcode',
    'cacheid',
    'captcha',
    'comments',
    'commentsperpage',
    'cron_auth',
    'cron_auto',
    'cron_times',
    'dbversion',
    'default_template',
    'defaultgroup',
    'description',
    'extratopicslimit',
    'favicon',
    'galdefault_per_page',
    'galdefault_per_row',
    'galdefault_thumb_h',
    'galdefault_thumb_w',
    'galuploadresize_h',
    'galuploadresize_w',
    'index_page_id',
    'install_check',
    'language',
    'language_allowcustom',
    'lostpass',
    'lostpassexpire',
    'mailerusefrom',
    'maintenance_interval',
    'maxloginattempts',
    'maxloginexpire',
    'messages',
    'messagesperpage',
    'notpublicsite',
    'pagingmode',
    'pollvoteexpire',
    'postadmintime',
    'pretty_urls',
    'profileemail',
    'ratemode',
    'registration',
    'registration_confirm',
    'registration_grouplist',
    'rules',
    'sboxmemory',
    'search',
    'show_avatars',
    'showpages',
    'sysmail',
    'thumb_cleanup_threshold',
    'thumb_touch_threshold',
    'time_format',
    'title',
    'titleseparator',
    'titletype',
    'topic_hot_ratio',
    'topicsperpage',
    'ulist',
    'uploadavatar',
    'version_check'
);

registerArgumentsSet(
    'user_privileges',
    'level',
    'super_admin',
    'administration',
    'adminsettings',
    'adminplugins',
    'adminusers',
    'admingroups',
    'admincontent',
    'adminother',
    'adminpages',
    'adminsection',
    'admincategory',
    'adminbook',
    'adminseparator',
    'admingallery',
    'adminlink',
    'admingroup',
    'adminforum',
    'adminpluginpage',
    'adminart',
    'adminallart',
    'adminchangeartauthor',
    'adminconfirm',
    'adminautoconfirm',
    'adminpoll',
    'adminpollall',
    'adminsbox',
    'adminbox',
    'fileaccess',
    'fileglobalaccess',
    'fileadminaccess',
    'adminhcmphp',
    'adminbackup',
    'adminmassemail',
    'adminposts',
    'changeusername',
    'postcomments',
    'unlimitedpostaccess',
    'locktopics',
    'stickytopics',
    'movetopics',
    'artrate',
    'pollvote',
    'selfremove'
);

registerArgumentsSet(
    'tables',
    'article',
    'box',
    'post',
    'gallery_image',
    'iplog',
    'page',
    'pm',
    'poll',
    'redirect',
    'setting',
    'shoutbox',
    'user',
    'user_activation',
    'user_group',
);

registerArgumentsSet(
    'web_modules',
    'editpost',
    'locktopic',
    'login',
    'lostpass',
    'lostpass-reset',
    'massemail',
    'messages',
    'movetopic',
    'profile',
    'profile-art',
    'profile-posts',
    'reg',
    'search',
    'settings',
    'settings-account',
    'settings-download',
    'settings-password',
    'settings-profile',
    'settings-remove',
    'stickytopic',
    'ulist',
    'viewpost'
);

registerArgumentsSet(
    'admin_modules',
    'index',
    'index-edit',
    'content',
    'content-setindex',
    'content-sort',
    'content-titles',
    'content-redir',
    'content-articles',
    'content-articles-list',
    'content-articles-edit',
    'content-articles-delete',
    'content-confirm',
    'content-movearts',
    'content-artfilter',
    'content-polls',
    'content-polls-edit',
    'content-sboxes',
    'content-boxes',
    'content-boxes-edit',
    'content-delete',
    'content-editsection',
    'content-editcategory',
    'content-editgroup',
    'content-editbook',
    'content-editseparator',
    'content-editlink',
    'content-editgallery',
    'content-editforum',
    'content-editpluginpage',
    'content-manageimgs',
    'users',
    'users-editgroup',
    'users-delgroup',
    'users-list',
    'users-edit',
    'users-delete',
    'users-move',
    'fman',
    'plugins',
    'plugins-action',
    'plugins-upload',
    'settings',
    'backup',
    'other',
    'other-patch',
    'other-cleanup',
    'other-sqlex',
    'other-php',
    'other-massemail'
);

expectedArguments(\Sunlight\Settings::get(), 0, argumentsSet('settings'));
expectedArguments(\Sunlight\Settings::update(), 0, argumentsSet('settings'));
expectedArguments(\Sunlight\Settings::overwrite(), 0, argumentsSet('settings'));
expectedArguments(\Sunlight\User::hasPrivilege(), 0, argumentsSet('user_privileges'));
expectedArguments(\Sunlight\Database\Database::table(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::count(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::insert(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::insertMulti(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::update(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::updateSet(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::updateSetMulti(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::delete(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Database\Database::deleteSet(), 0, argumentsSet('tables'));
expectedArguments(\Sunlight\Router::module(), 0, argumentsSet('web_modules'));
expectedArguments(\Sunlight\Router::admin(), 0, argumentsSet('admin_modules'));
