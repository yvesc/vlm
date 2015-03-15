<?php
    include_once("config.php");
    include_once("wslib.php");

    $ws = new WSBasePlayer();
    $ws->maxage = WS_PLAYER_LIST_CACHE_DURATION;

    $q = ws->check_cgi_var('q', 'PLAYERLIST01');
    if (is_null($q) $ws->reply_with_error('PLAYERLIST02');

    $ws->answer['list'] = Array();

    $query = sprintf("SELECT idplayers AS idp, playername FROM players WHERE playername LIKE '%%%s%%' LIMIT 20", $q);
    $result = $this->queryRead($query);
    if ($result)  {
        while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $row['jid'] = $row['playername'].'@'.VLM_XMPP_HOST;
            $ws->answer['list'][] = $row;
    }

    $ws->reply_with_success();

?>

