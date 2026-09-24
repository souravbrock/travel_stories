<?php require __DIR__.'/_lib.php';
$r = $_GET['resource'] ?? body()['resource'] ?? '';
$u = ($r==='public') ? null : require_auth();
$pdo = db();
$out = null;
try {
switch ($r) {
  case 'public': // open catalogue for landing preview (limited)
    $out = ['states'=> $pdo->query("SELECT * FROM states ORDER BY name")->fetchAll()];
    break;
  case 'me': $out = ['user'=>$u]; break;
  case 'states': $out = $pdo->query("SELECT * FROM states ORDER BY name")->fetchAll(); break;
  case 'districts': {
    $sid = (int)($_GET['state_id']??0);
    $st=$pdo->prepare("SELECT * FROM districts WHERE state_id=? ORDER BY name"); $st->execute([$sid]);
    $out=$st->fetchAll(); break; }
  case 'spots': {
    $did=(int)($_GET['district_id']??0);
    $st=$pdo->prepare("SELECT s.*, d.name district, st.name state, st.slug FROM tourist_spots s JOIN districts d ON d.id=s.district_id JOIN states st ON st.id=d.state_id ".($did?"WHERE s.district_id=$did":"")." ORDER BY s.name LIMIT 200");
    $st->execute(); $rows=$st->fetchAll();
    // AC2: attach distance/time matrix to all other spots in same district (Haversine)
    foreach ($rows as &$a) { $a['nearby']=[]; foreach ($rows as $b) {
      if ($a['id']===$b['id']||!$a['lat']||!$b['lat']) continue;
      $km = 6371*acos(min(1,cos(deg2rad($b['lat']-$a['lat'])))); // simplified below (accurate haversine in JS too)
      $dLat=deg2rad($b['lat']-$a['lat']); $dLng=deg2rad($b['lng']-$a['lng']);
      $h=sin($dLat/2)**2+cos(deg2rad($a['lat']))*cos(deg2rad($b['lat']))*sin($dLng/2)**2;
      $km=2*6371*asin(sqrt($h));
      $a['nearby'][]=['id'=>$b['id'],'name'=>$b['name'],'lat'=>$b['lat'],'lng'=>$b['lng'],'km'=>round($km,1),'mins'=>(int)round($km/38*60)];
    } usort($a['nearby'],fn($x,$y)=>$x['km']<=>$y['km']); $a['nearby']=array_slice($a['nearby'],0,12); }
    $out=$rows; break; }
  case 'properties': {
    $sid=(int)($_GET['spot_id']??0);
    $q="SELECT p.*, s.name spot FROM properties p JOIN tourist_spots s ON s.id=p.spot_id ".($sid?"WHERE p.spot_id=$sid":"")." ORDER BY p.rating DESC LIMIT 200";
    $out=$pdo->query($q)->fetchAll(); break; }
  case 'vehicles': $out=$pdo->query("SELECT * FROM vehicles ORDER BY price_per_day")->fetchAll(); break;
  case 'packages': $out=$pdo->query("SELECT p.*, s.name state, u.company_name agent FROM packages p LEFT JOIN states s ON s.id=p.state_id LEFT JOIN users u ON u.id=p.agent_id WHERE p.is_published=1 ORDER BY p.created_at DESC LIMIT 100")->fetchAll(); break;
  default: throw new Exception("unknown resource: $r");
}
echo json_encode(['ok'=>true,'data'=>$out]);
} catch (Throwable $e) { http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
