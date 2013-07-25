<?php

require(__DIR__ . '/config/config.php');
require(__DIR__ . '/classes/weixin.class.php');
require(__DIR__ . '/classes/request_handler.class.php');

spWxMessage::RegisterHandler(spWxMessage::REQUEST_TEXT,     'spWxRequestDefault');
spWxMessage::RegisterHandler(spWxMessage::REQUEST_URL,      'spWxRequestDefault');
spWxMessage::RegisterHandler(spWxMessage::REQUEST_IMAGE,    'spWxRequestDefault');
spWxMessage::RegisterHandler(spWxMessage::REQUEST_LOCATION, 'spWxRequestDefault');
spWxMessage::RegisterHandler(spWxMessage::REQUEST_EVENT,    'spWxRequestDefault');

spWxMessage::RegisterHandler(spWxMessage::REQUEST_VOICE,    'spWxRequestDefault');
spWxMessage::RegisterHandler(spWxMessage::REQUEST_VIDEO,    'spWxRequestDefault');

spWxMessage::MessageAPI();
exit;
