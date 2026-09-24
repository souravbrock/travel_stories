<?php
require __DIR__.'/_lib.php';
// POST {email, code} → verify
send('verify', function() {
  $b = array_merge($_POST, body());
  if (empty($b['email'])||empty($b['code'])) throw new Exception("email + code required");
  $pdo = db();
  $st = $pdo->prepare("SELECT * FROM email_otps WHERE email=? AND consumed=0 ORDER BY id DESC LIMIT 1");
  $st->execute([$b['email']]); $o = $st->fetch();
  if (!$o) throw new Exception("no pending OTP. Register again.");
  if (strtotime($o['expires_at']) < time()) throw new Exception("OTP expired. Register again.");
  if ((int)$o['attempts'] >= 5) throw new Exception("too many attempts. Register again.");
  $pdo->prepare("UPDATE email_otps SET attempts=attempts+1 WHERE id=?")->execute([$o['id']]);
  if (!hash_equals($o['code'], $b['code'])) throw new Exception("wrong code");
  $pdo->prepare("UPDATE email_otps SET consumed=1 WHERE id=?")->execute([$o['id']]);
  $pdo->prepare("UPDATE users SET is_verified=1 WHERE email=?")->execute([$b['email']]);
  return ['ok'=>true,'message'=>'email verified. Now set your password.'];
});
