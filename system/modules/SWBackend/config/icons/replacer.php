<?php

// header icons
$GLOBALS['ICON_REPLACER']['header']['target'] = '.header_buttons span a';
$GLOBALS['ICON_REPLACER']['header']['addSpace'] = true;
$GLOBALS['ICON_REPLACER']['header']['imageIcons'][] = array('refresh', 'dbcheck16.png');
$GLOBALS['ICON_REPLACER']['header']['imageIcons'][] = array('download-alt', 'install16.png');
$GLOBALS['ICON_REPLACER']['header']['styleIcons'][] = array('user', 'header_user');
$GLOBALS['ICON_REPLACER']['header']['styleIcons'][] = array('external-link', 'header_preview');
$GLOBALS['ICON_REPLACER']['header']['styleIcons'][] = array('home', 'header_home');
$GLOBALS['ICON_REPLACER']['header']['styleIcons'][] = array('signout', 'header_logout');
$GLOBALS['ICON_REPLACER']['header']['listenTo'][] = array('document', 'domready');