<?php
include "globals.inc";
include "config.inc";
$page_info = <<<EOD

----------------------------------------------------------------------------------------------------------------------
SquidGuard error page generator
----------------------------------------------------------------------------------------------------------------------
This program processes redirection requests to specified URL or generated error page for a standard HTTP error code.
Redirection supports HTTP and HTTPS protocols.
----------------------------------------------------------------------------------------------------------------------
Format:
        sgerror.php?url=[http://myurl]or[https://myurl]or[error_code[space_code]output-message][incoming SquidGuard variables]
Incoming SquidGuard variables:
        a=client_address
        n=client_name
        i=client_user
        s=client_group
        t=target_group
        u=client_url
Example:
        sgerror.php?url=http://myurl.com&a=..&n=..&i=..&s=..&t=..&u=..
        sgerror.php?url=https://myurl.com&a=..&n=..&i=..&s=..&t=..&u=..
        sgerror.php?url=404%20output-message&a=..&n=..&i=..&s=..&t=..&u=..
----------------------------------------------------------------------------------------------------------------------
Tags:
        myurl and output messages can include Tags
                [a] - client address
                [n] - client name
                [i] - client user
                [s] - client group
                [t] - target group
                [u] - client url
Example:
        sgerror.php?url=401 Unauthorized access to URL [u] for client [n]
        sgerror.php?url=http://my_error_page.php?cladr=%5Ba%5D&clname=%5Bn%5D // %5b=[ %d=]
----------------------------------------------------------------------------------------------------------------------
Special Tags:
        blank     - get blank page
        blank_img - get one-pixel transparent image (to replace images such as banners, ads, etc.)
Example:
        sgerror.php?url=blank
        sgerror.php?url=blank_img
----------------------------------------------------------------------------------------------------------------------
EOD;

define('ACTION_URL', 'url');
define('ACTION_RES', 'res');
define('ACTION_MSG', 'msg');

define('TAG_BLANK', 'blank');
define('TAG_BLANK_IMG', 'blank_img');

/* ----------------------------------------------------------------------------------------------------------------------
 * ?url=EMPTY_IMG
 *      Use this option to replace banners/ads with a transparent picture. This is better for web page rendering.
 * ----------------------------------------------------------------------------------------------------------------------
 * NULL GIF file
 * HEX: 47 49 46 38 39 61 - - -
 * SYM: G  I  F  8  9  a  01 00 | 01 00 80 00 00 FF FF FF | 00 00 00 2C 00 00 00 00 | 01 00 01 00 00 02 02 44 | 01 00 3B
 * ----------------------------------------------------------------------------------------------------------------------
 */
define(GIF_BODY, "GIF89a\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B");

$url  = '';
$msg  = '';
$cl   = Array(); // squidGuard variables: %a %n %i %s %t %u
$err_code = array();

$err_code[301] = "301 Moved Permanently";
$err_code[302] = "302 Found";
$err_code[303] = "303 See Other";
$err_code[305] = "305 Use Proxy";

$err_code[400] = "400 Bad Request";
$err_code[401] = "401 Unauthorized";
$err_code[402] = "402 Payment Required";
$err_code[403] = "403 Forbidden";
$err_code[404] = "404 Not Found";
$err_code[405] = "405 Method Not Allowed";
$err_code[406] = "406 Not Acceptable";
$err_code[407] = "407 Proxy Authentication Required";
$err_code[408] = "408 Request Time-out";
$err_code[409] = "409 Conflict";
$err_code[410] = "410 Gone";
$err_code[411] = "411 Length Required";
$err_code[412] = "412 Precondition Failed";
$err_code[413] = "413 Request Entity Too Large";
$err_code[414] = "414 Request-URI Too Large";
$err_code[415] = "415 Unsupported Media Type";
$err_code[416] = "416 Requested range not satisfiable";
$err_code[417] = "417 Expectation Failed";

$err_code[500] = "500 Internal Server Error";
$err_code[501] = "501 Not Implemented";
$err_code[502] = "502 Bad Gateway";
$err_code[503] = "503 Service Unavailable";
$err_code[504] = "504 Gateway Time-out";
$err_code[505] = "505 HTTP Version not supported";

/* ----------------------------------------------------------------------------------------------------------------------
 * Functions
 * ----------------------------------------------------------------------------------------------------------------------
 */
function get_page($body) { ?>
<html>
        <body>
<?=$body?>
        </body>
</html>
<?php
}

/*
 * Generate an error page for the user
 */
function get_error_page($er_code_id, $err_msg='') {
        global $g, $config, $err_code, $cl;
        header("HTTP/1.1 " . $err_code[$er_code_id]);

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html">
		<meta name="id" content="siteBlocked">
		<title>Web Site Bloqueado</title>
		<style type="text/css">
			#shd { width:500px;position:relative;right:3px;top:3px;margin-right:3px;margin-bottom:3px;text-align:center; }
			#shd .second,
			#shd .third,
			#shd .box { position:relative;left:-1px;top:-1px; }
			#shd .first { background: #f1f0f1; }
			#shd .second { background: #dbdadb; }
			#shd .third { background: #b8b6b8; }
			#shd .box { background:#ffffff;border:1px solid #848284;height:350px; }
			.strip { width:100%;height:70px; }
			.warn {
			background-color:#f0d44d;
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=’#fae379′, endColorstr=’#eed145′);
			background:-webkit-gradient(linear, left top, left bottom, from(#fae379), to(#eed145));
			background:-moz-linear-gradient(top, #fae379, #eed145);
			font-size:14px;
			font-weight:bold;
			}
			#nsa_banner { position:relative;top:0px;left:20px;float:center;}
			#alert_icon { position:relative;top:15px;left:20px;float:left; }
			#alert_text { float:left;position:relative;top:25px;left:40px;width:400px; }
		</style>
	</head>
<body>
	<div style="width:100%;height:100px;"></div>
	<center>
	<div id="shd"><div class="first"><div class="second"><div class="third">
	<div class="box">
	<div class="strip">
	<img src='' width='128' id='nsa_banner' alt="pfSense Network Applicance">
	</div>
	<div class="warn strip">
	<img src="data:image/png;base64,
	R0lGODlhKgAmAPUCAAcGCjo3HgocYj88RVBMLnBqMGRbFkhGQkdGQVdUUG5qT29tcWJfRIN5IZWM
	JqyiMYqGU4mHapqVbq2nSqypeMG1RNzPLePZOuXZLvLmOdTLTNTRbOXbQ+ffVObdaurhSe3nVfDq
	WvnxTfLtY/byaPn2d+zkcMO3PqqqsLCwt5KSmLm6wc3Ijf38h/Hsk/Hrp/HtuPv4p/v5t+3optzX
	ptjZ39TV28jJ0Nvc4/7+xPbzyPXyz+Tl7Ojp8P386t/g5yH5BAEAAAIALAAAAAAqACYAAAb+QIFw
	SCwaj8ikcslsLnsrFYrnrB55twSBsOpZv4KejfFwTQg2KtjZWzRivpwjgVs3eynCxsd3GVJedko/
	Cg85Pjs7OicEdYJIPSoGLog6iX4qao9EYgwViYo6ohpompthki6KMKwwOi8OCz+nQlAEHaGiO6w6
	HgYogZs8Cw4xuzA0LDQwMzAxDwmzm3gGJrs6LAoJCyzMLyYGKsF2PAkPMjquEdsDEjPvLhUEN+Nf
	kZOvrOsJ7TMu/y4aLDBlpUeNABpazZAh4UCCABT8uaDhghSgNcOKMXsng8KBAQNY/KPoosWcendu
	EPDwouWLhSwIBGBA0sUGDx4yFJjypdz+gxguXc5QAECBBxAWkibF8AANyiSRCriY8fLfCxclGgBw
	gEGpVwwNMlXBQUDDS39XPWDI4GBrhgsYLshdaoHARSZtHLho6S+E3AsiJgB4IOICBw5/4bJd8LSI
	mJVAXYy48KHyBxEnEIrg8OEwYg5xdQJbUm5CDBcmLIO4DEKEhgKsQayujBgwIxyNw6Ao0CKGh8qy
	QwgnQZz4iBHCZVtGLKKAOEg19LgI8QGE8OMjiJcg4YHEdhLHhVsG3GHeUx4qILSgLjxEdu8lSmyQ
	qec7+OSzQzxgbARLgu4dDGcCfPG1QBQAAKgXHwkmhDfbByRwUc8wE5RgXQgDxldCCxzGtrBBAQEU
	4EGHCzoIAgckaJBADePgoMB2IDRYggkbbtjhaSXF0GELCzao3ActMMDfEDaoF1x2GvK445I8aohc
	CMpdUMIEB0gjxA0MuBDDljHIkEMOXn4p5phf7vClDDJsuSQDdBCBwwIEKBDBnHTWaeeddkKgpwIE
	JJCCKTyksMAABxxAQKGIJqrooogOcKhDKKRBBA843IDCpZhmqmkKKWjq6aUprJDGOD3wwEMNqKZa
	Aw442KDqqzi8imqsq+LAQ260LBEEADs=" id="alert_icon" alt="Alert">
	<div id="alert_text">
	Este site foi bloqueado pelo Administrador da Rede.
	</div>
	</div>
	<div>
	<p id="urlp" style="text-align:left;padding-left:40px;">
	<b>URL: </b><?= htmlspecialchars($cl['u'])?><br/>
	<b> Categoria do Bloqueio: </b><?= htmlspecialchars($cl['t']) ?><br/>
	<b> IP Estação:    </b> <?= htmlspecialchars($cl['a']) ?><br/>
	<b> Usuário:    </b> <?= htmlspecialchars($cl['i']) ?><br/>
	<b> Nível de Acesso:   </b> <?= htmlspecialchars($cl['s']) ?></p>
	<p>Se você acredita que o website foi bloqueado incorretamente, envie uma notificação para a TI.</p>
	</div>
	</div>
	</div></div></div></div>
	</center>
	</body>
</html>
<?php
}

function get_about() {
        global $err_code, $page_info; ?>
<?= str_replace("\n", "<br/>", $page_info); ?>
<br/>
<table>
        <tr><th><b>HTTP error codes (ERROR_CODE):</b></th></tr>
        <?php foreach ($err_code as $val): ?>
        <tr><td><?= htmlspecialchars($val) ?></td></tr>
        <?php endforeach; ?>
</table>
<?php
}

function filter_by_image_size($url, $val_size) {
        // Load URL header
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $hd = curl_exec($ch);
        curl_close($ch);

        $size = 0;
        $SKEY = "content-length:";
        $s_tmp = strtolower($hd);
        $s_tmp = str_replace("\n", " ", $s_tmp); // replace all "\n"
        if (strpos($s_tmp, $SKEY) !== false) {
                $s_tmp = trim(substr($s_tmp, strpos($s_tmp, $SKEY) + strlen($SKEY)));
                $s_tmp = trim(substr($s_tmp, 0, strpos($s_tmp, " ")));
                if (is_numeric($s_tmp))
                        $size = intval($s_tmp);
                else $size = 0;
        }

        /*
         * check url type and content size
         * redirect to specified url
         */
        if (($size !== 0) && ($size < $val_size)) {
                header("HTTP/1.0");
                header("Location: $url", '', 302);
        } else {
                // Returna blank image
                header("Content-Type: image/gif;");
                echo GIF_BODY;
        }
}

/* ----------------------------------------------------------------------------------------------------------------------
 * Check arguments
 * ----------------------------------------------------------------------------------------------------------------------
 */
if (count($_REQUEST)) {
        $url  = trim($_REQUEST['url']);
        $msg  = $_REQUEST['msg'];
        $cl['a'] = $_REQUEST['a'];
        $cl['n'] = $_REQUEST['n'];
        $cl['i'] = $_REQUEST['i'];
        $cl['s'] = $_REQUEST['s'];
        $cl['t'] = $_REQUEST['t'];
        $cl['u'] = $_REQUEST['u'];
} else {
        // Show 'About page'
        echo get_page(get_about());
        exit();
}

/* ----------------------------------------------------------------------------------------------------------------------
 * Process URLs
 * ----------------------------------------------------------------------------------------------------------------------
 */
if ($url) {
        $err_id = 0;

        // Check error code
        foreach ($err_code as $key => $val) {
                if (strpos(strtolower($url), strval($key)) === 0) {
                        $err_id = $key;
                        break;
                }
        }

        if ($url === TAG_BLANK) {
                // Output a blank page
                echo get_page('');
        } elseif ($url === TAG_BLANK_IMG) {
                // Output a blank image
                $msg = trim($msg);
                if (strpos($msg, "maxlen_") !== false) {
                        $maxlen = intval(trim(str_replace("maxlen_", "", $url)));
                        filter_by_image_size($cl['u'], $maxlen);
                        exit();
                } else {
                        // Return a blank image
                        header("Content-Type: image/gif;"); // charset=windows-1251");
                        echo GIF_BODY;
                }
        } elseif ($err_id !== 0) {
                // Output an error code
                $er_msg = strstr($_GET['url'], ' ');
                echo get_error_page($err_id, $er_msg);
        } elseif ((strpos(strtolower($url), "http://") === 0) or (strpos(strtolower($url), "https://") === 0)) {
                // Redirect to the specified url
                header("HTTP/1.0");
                header("Location: $url", '', 302);
        } else {
                // Output an error
                echo get_page("sgerror: error arguments $url");
        }
} else {
        echo get_page($_SERVER['QUERY_STRING']); //$url . implode(" ", $_GET));
        // echo get_error_page(500);
}
