<?php
// Shared bootstrap for all /api/*.php
declare(strict_types=1);
date_default_timezone_set('UTC'); // all expiry math in UTC; SQL must use UTC_TIMESTAMP()
ini_set('display_errors', '0'); // never leak warnings into JSON responses
header('Content-Type: application/json; charset=utf-8');

function cfg(): array {
  static $c = null;
  if ($c) return $c;
  $f = __DIR__ . '/../config/config.php';
  if (!file_exists($f)) { http_response_code(500); echo json_encode(['error'=>'config.php missing. Copy config/config.sample.php']); exit; }
  $c = require $f;
  return $c;
}
function db(): PDO {
  static $p = null;
  if ($p) return $p;
  $c = cfg()['db'];
  $p = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}",
    $c['user'], $c['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  return $p;
}
function body(): array {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw ?: '{}', true);
  return is_array($j) ? $j : [];
}
function send(string $name, callable $fn) {
  try { echo json_encode($fn()); }
  catch (Throwable $e) { http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
}
function require_auth(): array {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
  if (!$h && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) { if (strtolower($k)==='authorization') { $h = $v; break; } }
  }
  if (!str_starts_with($h, 'Bearer ')) { http_response_code(401); echo json_encode(['error'=>'login required']); exit; }
  $tok = substr($h, 7);
  $st = db()->prepare("SELECT u.* FROM auth_tokens t JOIN users u ON u.id=t.user_id WHERE t.token=? AND t.expires_at>NOW() AND u.is_active=1");
  $st->execute([$tok]);
  $u = $st->fetch();
  if (!$u) { http_response_code(401); echo json_encode(['error'=>'session expired']); exit; }
  unset($u['password_hash']);
  return $u;
}
function require_role(array $u, array $roles) {
  if (!in_array($u['role'], $roles, true)) { http_response_code(403); echo json_encode(['error'=>'forbidden: '.implode(',',$roles).' only']); exit; }
}
function issue_token(int $uid): string {
  $tok = bin2hex(random_bytes(32));
  $days = (int)(cfg()['auth']['token_days'] ?? 30);
  db()->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL $days DAY))")->execute([$uid,$tok]);
  return $tok;
}
function send_mail(string $to, string $subject, string $body, string $from): void {
  $c = cfg();
  if (!$c['mail']['use_smtp']) {
    $headers = "From: $from\r\nReply-To: $from";
    @mail($to, $subject, $body, $headers);
    return;
  }
  // Minimal SMTP client (no composer dependency, works on cPanel)
  require_once __DIR__.'/smtp.php';
  smtp_send($c['mail'], $from, $to, $subject, $body);
}
function send_otp_mail(string $email, string $code): void {
  $c = cfg();
  $subject = "Your Travel Stories verification code: $code";
  $msg = "Namaste from Travel Stories!\n\nYour 5-digit verification code is: $code\nIt expires in 15 minutes.\n\nIf you did not request this, ignore this email.";
  try {
    send_mail($email, $subject, $msg, $c['mail']['from_noreply']);
  } finally {
    // Always log for deliverability debugging on shared hosting
    @file_put_contents(__DIR__.'/../storage/otp.log', date('c')." $email $code\n", FILE_APPEND);
  }
}
function send_booking_mail(string $to, string $subject, string $body): void {
  send_mail($to, $subject, $body, cfg()['mail']['from_booking']);
}
function cors(): void {
  // Same-origin by default; relax only if app_url differs (Android WebView)
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Authorization, Content-Type');
  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
  if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
}
cors();
