<?php
require __DIR__.'/_lib.php';
// POST {email,password}
send('login', function() {
  $b = array_merge($_POST, body());
  $st = db()->prepare("SELECT * FROM users WHERE email=? AND is_active=1"); $st->execute([$b['email']??'']);
  $u = $st->fetch();
  if (!$u || empty($u['password_hash']) || !password_verify($b['password']??'', $u['password_hash'])) throw new Exception("invalid credentials");
  if (!(int)$u['is_verified']) throw new Exception("verify email first");
  unset($u['password_hash']);
  return ['ok'=>true,'token'=>issue_token((int)$u['id']),'user'=>$u];
});
