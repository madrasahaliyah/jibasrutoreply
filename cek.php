<?php
 $nis = 'PG2022021';
 $from ='from';
 if(isset($_REQUEST['nis']))
	 $nis=$_REQUEST['nis'];
 if(isset($_REQUEST['no']))
	 $from=$_REQUEST['no'];
require_once('include/errorhandler.php');
require_once('include/common.php');
require_once('include/rupiah.php');
require_once('include/config.php');
require_once('include/db_functions.php');
require_once('include/theme.php');
require_once('include/sessioninfo.php');
require_once('library/jurnal.php');
require_once('library/repairdatajtt.php');
require_once('library/smsmanager.func.php');
OpenDb();
$pizza  = $nis;
$pieces = explode(" ", $pizza);
$info= $pieces[0]; // piece1
$nis =  $pieces[1]; // piece2
$sql = "INSERT INTO `jbsfina`.`a_log_wa` (	`from`, `message`) VALUES ('$from', '$pizza' );";
QueryDb($sql);
if(strtolower($info)!='va') exit();
$sql = "SELECT nama FROM jbsakad.siswa WHERE nis='$nis'";
$rr = QueryDb($sql);
if (mysql_num_rows($rr) < 1) {
	echo "Data Tidak ditemukan,<br> Silahkan coba lagi";exit();
}
$namasiswa = FetchSingle($sql);
if($namasiswa==''){
	echo "Data tidak ditemukan.";
	exit();
}
$x = "SELECT replid FROM jbsakad.siswa WHERE nis = '$nis'";
$row = mysql_fetch_row(QueryDb($x));
$niy = $row[0];
if($niy==''){
	echo "Data tidak ditemukan.";
	exit();
}
$sql = "SELECT b.replid as idbesarjtt, b.besar, b.cicilan, d.nama,d.replid as va FROM besarjtt b
LEFT JOIN `datapenerimaan` d ON b.idpenerimaan = d.replid
WHERE nis = '$nis' and aktif=1 ";
echo "Pembayaran atas nama $namasiswa <br>";
$result = QueryDb($sql);
	$c = 1;
		while($row = mysql_fetch_row($result))
		{
			$va = $row[4];
			$v = sprintf("%03s",$va).sprintf("%09s",$niy);
			$idbesarjtt=$row[0];
			$total_tagihan = FormatRupiah($row[1]);
			$cicilan = FormatRupiah($row[2]);
			$namabayar = $row[3];
			$ss = "SELECT p.replid AS id, j.nokas, date_format(p.tanggal, '%d-%b-%Y') as tanggal,
                           p.keterangan, p.jumlah, p.petugas, p.info1 AS diskon, jd.koderek AS rekkas, ra.nama AS namakas 
					  FROM penerimaanjtt p, besarjtt b, jurnal j, jurnaldetail jd, rekakun ra
					 WHERE p.idbesarjtt = b.replid
                       AND j.replid = p.idjurnal
                       AND j.replid = jd.idjurnal
                       AND jd.koderek = ra.kode
                       AND ra.kategori = 'HARTA'
                       AND b.replid = '$idbesarjtt'
					ORDER BY p.tanggal, p.replid ASC";
			$rr = QueryDb($ss);
			echo "$c. Pembayaran *$namabayar* <br>Nomor VA : $v<br>Total tagihan:$total_tagihan<br>Cicilan:$cicilan<br>";
			$c++;
			if (mysql_num_rows($rr) > 1) 
			{
				$cnt = 0;
				$total = 0;
				$total_diskon = 0;
				while ($rrd = mysql_fetch_array($rr))
				{
					$tanggal=$rrd['tanggal'];
					$jumlah = FormatRupiah($rrd['jumlah']);
					$petugas=$rrd['petugas'];
					$ket = isset($rrd['keterangan'])&&$rrd['keterangan']<>''?"(".$rrd['keterangan'].")":'';
					echo "Tanggal $tanggal sejumlah $jumlah diterima oleh $petugas $ket<br>";
				}
			}else{
				echo "*Belum ada transaksi pembayaran*<br>";
			}
			echo '<br>';
		}
CloseDb();
?>
