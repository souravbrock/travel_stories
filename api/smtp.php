<?php
// Tiny SMTP sender over ssl/tls sockets (for cPanel email accounts). No dependencies.
function smtp_send(array $mail, string $fromAddr, string $to, string $subject, string $body): void {
  $host = $mail['smtp_host']; $port = (int)$mail['smtp_port'];
  $secure = $mail['smtp_secure'] ?? 'ssl';
  $prefix = $secure === 'ssl' ? 'ssl://' : 'tcp://';
  $fp = @stream_socket_client("$prefix$host:$port", $errno, $err, 15);
  if (!$fp) throw new Exception("SMTP connect failed: $err");
  $read = function() use ($fp) { $r=''; while($l=fgets($fp,512)){ $r.=$l; if(preg_match('/^\d{3} /',$l)) break; } return $r; };
  $cmd = function($c) use ($fp,$read) { fwrite($fp,$c."\r\n"); return $read(); };
  $read();
  $cmd("EHLO reddevils.co.in");
  if ($secure==='tls') { $cmd("STARTTLS"); stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT); $cmd("EHLO reddevils.co.in"); }
  $cmd("AUTH LOGIN");
  $cmd(base64_encode($mail['smtp_user']));
  $cmd(base64_encode($mail['smtp_pass']));
  $from = preg_match('/<([^>]+)>/', $fromAddr, $m) ? $m[1] : $fromAddr;
  $cmd("MAIL FROM:<$from>");
  $cmd("RCPT TO:<$to>");
  $cmd("DATA");
  $headers = "From: $fromAddr\r\nTo: <$to>\r\nSubject: $subject\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n";
  fwrite($fp, $headers.$body."\r\n.\r\n"); $read();
  $cmd("QUIT"); fclose($fp);
}
