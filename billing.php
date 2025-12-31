<?php
$MIKROTIK_IP   = "2.2.2.1";
$MIKROTIK_USER = "billing";
$MIKROTIK_PASS = "123456";
$MIKROTIK_PORT = 8728;

/* SQLITE */
$db = new SQLite3('/www/billing.db');
$db->exec("CREATE TABLE IF NOT EXISTS pelanggan (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 pppoe TEXT UNIQUE,
 status TEXT
)");

/* ROUTEROS API */
class RouterosAPI {
    public $s;
    function connect($ip,$user,$pass,$port){
        $this->s = fsockopen($ip,$port,$e,$s,5);
        if(!$this->s) die("❌ API GAGAL");
        $this->w("/login");
        $this->w("=name=$user");
        $this->w("=password=$pass",true);
        $this->r();
    }
    function w($w,$e=false){
        fwrite($this->s, chr(strlen($w)).$w);
        if($e) fwrite($this->s, chr(0));
    }
    function r(){
        $res=[];
        while(true){
            $l=ord(fread($this->s,1));
            if(!$l) break;
            $w=fread($this->s,$l);
            if($w=="!done") break;
            if($w=="!re") $res[]=[];
        }
        return $res;
    }
    function c($cmd){
        $this->w($cmd,true);
        return $this->r();
    }
    function d(){ fclose($this->s); }
}

/* AMBIL PPPoE */
$api = new RouterosAPI();
$api->connect($MIKROTIK_IP,$MIKROTIK_USER,$MIKROTIK_PASS,$MIKROTIK_PORT);
$list = $api->c("/ppp/secret/print");
$api->d();

/* SIMPAN */
foreach($list as $p){
    if(!isset($p['name'])) continue;
    $u = $p['name'];
    $s = (isset($p['disabled']) && $p['disabled']=="true") ? "NONAKTIF":"AKTIF";
    $db->exec("INSERT OR IGNORE INTO pelanggan(pppoe,status) VALUES('$u','$s')");
}
?>

<h2>PPPoE MikroTik</h2>
<table border=1 cellpadding=8>
<tr><th>User</th><th>Status</th></tr>
<?php
$q=$db->query("SELECT * FROM pelanggan");
while($d=$q->fetchArray()){
echo "<tr><td>{$d['pppoe']}</td><td>{$d['status']}</td></tr>";
}
?>
</table>
