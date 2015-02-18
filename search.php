<?php
#error_reporting(E_ALL);
#ini_set('display_errors', 1);

function prettyPrint( $json )
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;
}

require_once(dirname(__FILE__) . '/../lib/cache.class.php');
require_once(dirname(__FILE__) . '/../lib/ganon.php');

$cache_expire_time = 3 * 60 * 60;
$rca_url = "http://asfromania.ro/em/cedam/search.php?";

$cache = new Cache('carminder');
$cache->setCachePath(dirname(__FILE__) . '/../cache/');

$uri = $_SERVER['QUERY_STRING'];
$reply = $cache->retrieve($uri);

if ($reply == null) {
	$html = file_get_dom($rca_url . $uri);
	$response = $html->select('td', 2);
	if (count($response->select('i')) > 2) {
		$reply = json_encode(array(
				     'plate' => $response->select('b', 1)->getPlainText(),
			      	     'MTPL' => $response->select('b', 2)->getPlainText(),
				     'startDate' => $response->select('i', 1)->getPlainText(),
				     'endDate' => $response->select('i', 2)->getPlainText()));
	} else {
		$reply = json_encode(array(
				     'plate' => $response->select('b', 1)->getPlainText(),
			      	     'MTPL' => "Not existent",
				     'startDate' => "",
				     'endDate' => ""));
	}
	$cache->store($uri, $reply, $cache_expire_time);
}

$cache->eraseExpired();
echo prettyPrint($reply);


?>
