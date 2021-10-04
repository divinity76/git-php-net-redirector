<?php
declare(strict_types = 1);

function get_github_redirect(string $original_uri): ?string
{
    $uri = ltrim($original_uri, '/?');
    $len = strlen($uri);
    $base = 'https://github.com/php/php-src/';
    if ($len < 1) {
        return $base;
    }
    $data = [];
    parse_str($uri, $data);
    if (empty($data)) {
        return $base;
    }
    $p = explode(';', $data['p'] ?? "");
    if (count($p) === 3 && $p[0] === 'php-src.git' && $p[1] === 'a=commit' && preg_match('/^h\\=[a-f0-9]{40}$/', $p[2])) {
        // example: http://git.php.net/?p=php-src.git;a=commit;h=3c939e3f69955d087e0bb671868f7267dfb2a502
        $ret = $base . 'commit/' . substr($p[2], strlen('h='));
        return $ret;
    }
    // dunno 
    return null; 
}
// $uri = '/?p=php-src.git;a=commit;h=3c939e3f69955d087e0bb671868f7267dfb2a502';
$uri = $_SERVER['REQUEST_URI'];
$redirect = get_github_redirect($uri);
if($redirect!==null){
    // 307=temporary redirect
    // 308=permanent redirect
    // 307 is probably easier to debug until its somewhat tested?
    // (code is completely untested as of writing)
    http_response_code(307);
    header("Location: {$redirect}");
    return;
}
header("Content-Type: text/plain; charset=UTF-8");
http_response_code(500);
echo $uri."\n\n";
?>
sorry, i don't know how to redirect that url to  github..
this is a bug, plz report. bugtracker: https://github.com/divinity76/git-php-net-redirector/issues
