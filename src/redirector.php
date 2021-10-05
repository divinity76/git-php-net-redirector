<?php
declare(strict_types = 1);

function get_github_redirect(string $original_uri): ?string
{
    $uri = parse_url($original_uri, PHP_URL_QUERY);
    $len = strlen($uri);
    $base = 'https://github.com/php/php-src/';
    if ($len < 1 || $uri === 'p=php-src.git;a=log') {
        return $base;
    }
    $data = [];
    parse_str($uri, $data);
    if (empty($data)) {
        return $base;
    }
    // <parse_p>
    // i have no idea how i is supposed to be parsed,
    // parsing it by ; and = is just my best guess.
    $tmp = explode(';', $data['p'] ?? "");
    $p = [];
    foreach ($tmp as $pdata) {
        $pdata = explode("=", $pdata, 2);
        if (count($pdata) === 1) {
            $p[] = $pdata[0];
        } else {
            $p[$pdata[0]] = $pdata[1];
        }
    }
    // </parse_p>
    if (count($p) === 3 && isset($p[0], $p['a'], $p['h']) && $p[0] === 'php-src.git' && in_array($p['a'], [
        'commit',
        'commitdiff'
    ], true) && preg_match('/^[a-f0-9]{7,40}$/', $p['h'])) {
        // from: http://git.php.net/?p=php-src.git;a=commit;h=3c939e3f69955d087e0bb671868f7267dfb2a502
        // to: https://github.com/php/php-src/commit/3c939e3f69955d087e0bb671868f7267dfb2a502
        // from: http://git.php.net/?p=php-src.git;a=commitdiff;h=c730aa26bd52829a49f2ad284b181b7e82a68d7d
        // to: https://github.com/php/php-src/commit/c730aa26bd52829a49f2ad284b181b7e82a68d7d
        $ret = "{$base}commit/{$p['h']}";
        return $ret;
    }
    if (count($p) === 3 && isset($p[0], $p['a'], $p['h']) && $p[0] === 'php-src.git' && $p['a'] === 'shortlog' && 0 === strncmp('refs/tags/', $p['h'], strlen('refs/tags/'))) {
        // from: https://git.php.net/?p=php-src.git;a=shortlog;h=refs/tags/php-8.0.0RC2
        // to: https://github.com/php/php-src/releases/tag/php-8.1.0RC3
        $ret = "{$base}releases/tag/" . substr($p['h'], strlen('refs/tags/'));
        return $ret;
    }
    if (count($p) === 4 && isset($p[0], $p['a'], $p['h'], $p['hb']) && $p[0] === 'php-src.git' && in_array($p['a'], [
        'tree',
        'log',
        'shortlog'
    ], true) && $p['h'] === $p['hb'] && 0 === strncmp($p['h'], 'refs/heads/', strlen('refs/heads/'))) {
        // from: https://git.php.net/?p=php-src.git;a=tree;h=refs/heads/master;hb=refs/heads/master
        // to: https://github.com/php/php-src/tree/master
        if ($p['a'] === 'tree') {
            $ret = 'tree';
        } else {
            // its "log" or "shortlog"
            $ret = "commits";
        }
        $ret = "{$base}{$ret}/" . substr($p['h'], strlen('refs/heads/'));
        return $ret;
    }

    if (0) {
        var_dump($p, $uri);
        die();
    }
    // dunno
    return null;
}
/** @var string[] $argv */
$uri = $_SERVER['REQUEST_URI'] ?? $argv[1] ?? null;
if ($uri === null) {
    http_response_code(500);
    echo "error: cannot find request uri (it is not in \$_SERVER nor in \$argv[1]";
    return;
}

$redirect = get_github_redirect($uri);
header("Content-Type: text/plain; charset=UTF-8");
if ($redirect !== null) {
    // 307=temporary redirect
    // 308=permanent redirect
    // 307 is probably easier to debug until its somewhat tested?
    // (code is completely untested as of writing)
    http_response_code(307);
    header("Location: {$redirect}");
    echo "you are being redirected to: {$redirect}";
    return;
}
http_response_code(500);
echo $uri . "\n\n";
?>
sorry, i don't know how to redirect that url to  github..
this is a bug, plz report. bugtracker: https://github.com/divinity76/git-php-net-redirector/issues
