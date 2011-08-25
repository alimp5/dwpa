<?
require('db.php');
require('common.php');
if (empty($_POST)): ?>
<h1>Last 20 submitted networks</h1>
<form class="form" method="POST" action="?nets" enctype="multipart/form-data">
<table style="border: 1;">
<tr><th>BSSID</th><th>SSID</th><th>WPA key</th><th>Timestamp</th></tr>
<?
    $sql = 'SELECT * FROM nets ORDER BY ts DESC LIMIT 20';
    $stmt = $mysql->stmt_init();
    $stmt->prepare($sql);
    $stmt->execute();
    $data = array();
    stmt_bind_assoc($stmt, $data);
    while ($stmt->fetch()) {
        $bssid = long2mac($data['bssid']);
        $ssid = htmlspecialchars($data['ssid']);
        if ($data['pass'] == '') {
            $pass = '<input class="input" type="text" name="'.$bssid.'" size="20"/>';
        } else
            $pass = htmlspecialchars($data['pass']);
        $ts = $data['ts'];
        echo "<tr><td style=\"font:Courier;\">$bssid</td><td>$ssid</td><td>$pass</td><td>$ts</td></tr>\n";
    }
?>
</table>
<input class="submitbutton" type="submit" value="Send WPA keys" />
</form>
<? else:
    //Check stmt
    $sql = 'SELECT * FROM nets WHERE bssid = ? AND n_state=0';
    $stmt = $mysql->stmt_init();
    $stmt->prepare($sql);
    $data = array();
    stmt_bind_assoc($stmt, $data);

    //Update key stmt
    $usql = 'UPDATE nets SET pass=?, sip=?, n_state=1, sts=NOW() WHERE bssid=?';
    $ustmt = $mysql->stmt_init();
    $ustmt->prepare($usql);

    foreach ($_POST as $bssid => $key) {
        if (valid_mac($bssid) && strlen($key) >= 8) {
            $ibssid = mac2long($bssid);
            $stmt->bind_param('i', $ibssid);
            $stmt->execute();

            if ($stmt->fetch())
                if (check_pass($bssid, $key)) {
                    $stmt->free_result();
                    $iip = ip2long($_SERVER['REMOTE_ADDR']);
                    $ustmt->bind_param('sii', mysqli_real_escape_string($mysql, $key), $iip, $ibssid);
                    $ustmt->execute();
                }
        }
    }
    $ustmt->close();
endif;
$stmt->close();
$mysql->close();
?>