<?php
require __DIR__.'/_lib.php';
// POST {email,password} — only after OTP verification
send('setpw', function() {
  $b = array_merge($_POST, body());
  if (empty($b['email'])||empty($b['password'])) throw new Exception("email + password required");
  if (strlen($b['password'])<6) throw new Exception("password min 6 chars");
  $pdo = db();
  $st=$pdo->prepare("SELECT * FROM users WHERE email=?"); $st->execute([$b['email']]); $u=$st->fetch();
  if (!$u) throw new Exception("account not found");
  if (!(int)$u['is_verified']) throw new Exception("verify email OTP first");
  $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($b['password'],PASSWORD_DEFAULT),$u['id']]);
  return ['ok'=>true,'token'=>issue_token((int)$u['id']),'role'=>$u['role']];
});
