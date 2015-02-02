<?php

namespace Reliv\Deploy\Xmpp\Event;

final class XmppEvents
{
    const CONNECT           = 'xmpp.on_connect';
    const STREAM_START      = 'xmpp.on_stream_start';
    const STREAM_FEATURES   = 'xmpp.on_stream_features';
    const AUTH              = 'xmpp.on_auth';
    const PRESENCE_STANZA   = 'xmpp.on_presence_stanza';
    const CHAT_MESSAGE      = 'xmpp.on_chat_message';
    const GROUPCHAT_MESSAGE = 'xmpp.on_groupchat_message';
    const HEADLINE_MESSAGE  = 'xmpp.on_headline_message';
    const NORMAL_MESSAGE    = 'xmpp.on_normal_message';
    const ERROR_MESSAGE     = 'xmpp.on_error_message';
    const DISCONNECT        = 'xmpp.on_disconnect';
}