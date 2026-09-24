<?php
require __DIR__.'/_lib.php';
// POST {name,email,phone,role?,vendor_type?,company_name?} → creates user + 5-digit OTP
send('register', function() {
  $b = array_merge($_POST, body());
  foreach (['name','email','phone'] as $k) if (empty($b[$k])) throw new Exception("$k required");
  if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) throw new Exception("invalid email");
  $role = ($b['role'] ?? 'customer'); if (!in_array($role,['customer','vendor'],true)) $role='customer';
  $pdo = db();
  $ex = $pdo->prepare("SELECT id,is_verified FROM users WHERE email=?"); $ex->execute([$b['email']]);
  if ($row = $ex->fetch()) {
    if ((int)$row['is_verified']===1) throw new Exception("email already registered. Please login.");
    $pdo->prepare("UPDATE users SET name=?,phone=?,role=?,vendor_type=?,company_name=? WHERE id=?")
      ->execute([$b['name'],$b['phone'],$role,$b['vendor_type']??null,$b['company_name']??null,$row['id']]);
  } else {
    $pdo->prepare("INSERT INTO users (name,email,phone,role,vendor_type,company_name) VALUES (?,?,?,?,?,?)")
      ->execute([$b['name'],$b['email'],$b['phone'],$role,$b['vendor_type']??null,$b['company_name']??null]);
  }
  $code = str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT);
  $pdo->prepare("UPDATE email_otps SET consumed=1 WHERE email=? AND consumed=0")->execute([$b['email']]);
  $pdo->prepare("INSERT INTO email_otps (email,code,expires_at) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE))")->execute([$b['email'],$code]);
  try { send_otp_mail($b['email'],$code); } catch (Throwable $e) { /* log, still respond */ }
  $dev = (cfg()['env']==='dev') ? ['dev_otp'=>$code] : [];
  return ['ok'=>true,'message'=>'OTP sent to email'] + $dev;
});
