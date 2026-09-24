<?php require __DIR__.'/_lib.php';
// Vendor: enlist packages/vehicles/properties. Admin: full CRUD.
// POST {action, ...} with Bearer token.
$u = require_auth();
$b = array_merge($_POST, body());
$a = $b['action'] ?? '';
try {
$pdo = db();
$isVendor = $u['role']==='vendor' || $u['role']==='admin';
switch ($a) {
  case 'upsert_package':
    if (!$isVendor) throw new Exception("vendor only");
    if (empty($b['title'])) throw new Exception("title required");
    $owner = $u['role']==='admin' && !empty($b['agent_id']) ? (int)$b['agent_id'] : (int)$u['id'];
    if (empty($b['id'])) { $pdo->prepare("INSERT INTO packages (agent_id,title,state_id,price,duration_days,itinerary,photos) VALUES (?,?,?,?,?,?,?)")
      ->execute([$owner,$b['title'],($b['state_id']??null)?:null,$b['price']??0,$b['duration_days']??3,$b['itinerary']??'',$b['photos']??'']); }
    else { $pdo->prepare("UPDATE packages SET title=?,state_id=?,price=?,duration_days=?,itinerary=?,photos=? WHERE id=? ".($u['role']==='admin'?"":"AND agent_id=".(int)$u['id']))->execute([$b['title'],($b['state_id']??null)?:null,$b['price']??0,$b['duration_days']??3,$b['itinerary']??'',$b['photos']??'',(int)$b['id']]); }
    echo json_encode(['ok'=>true]); break;
  case 'upsert_vehicle':
    if (!$isVendor) throw new Exception("vendor only");
    if (empty($b['id'])) { $pdo->prepare("INSERT INTO vehicles (owner_id,category,name,seats,price_per_day,photo_url) VALUES (?,?,?,?,?,?)")->execute([$u['id'],$b['category']??'suv_muv',$b['name'],($b['seats']??null)?:null,$b['price_per_day']??0,$b['photo_url']??'']); }
    else { $pdo->prepare("UPDATE vehicles SET category=?,name=?,seats=?,price_per_day=?,photo_url=? WHERE id=? ".($u['role']==='admin'?"":"AND owner_id=".(int)$u['id']))->execute([$b['category'],$b['name'],($b['seats']??null)?:null,$b['price_per_day']??0,$b['photo_url']??'',(int)$b['id']]); }
    echo json_encode(['ok'=>true]); break;
  case 'upsert_property':
    if (!$isVendor) throw new Exception("vendor only");
    if (empty($b['spot_id'])||empty($b['name'])) throw new Exception("spot_id + name required");
    if (empty($b['id'])) { $pdo->prepare("INSERT INTO properties (spot_id,owner_id,kind,name,tier,price_per_night,rating,amenities,photos) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$b['spot_id'],$u['id'],$b['kind']??'hotel',$b['name'],$b['tier']??'deluxe',$b['price']??0,($b['rating']??null)?:null,$b['amenities']??'',$b['photos']??'']); }
    else { $pdo->prepare("UPDATE properties SET spot_id=?,kind=?,name=?,tier=?,price_per_night=?,amenities=?,photos=? WHERE id=? ".($u['role']==='admin'?"":"AND owner_id=".(int)$u['id']))->execute([$b['spot_id'],$b['kind'],$b['name'],$b['tier'],$b['price']??0,$b['amenities']??'',$b['photos']??'',(int)$b['id']]); }
    echo json_encode(['ok'=>true]); break;
  case 'upsert_spot':
    require_role($u,['admin']);
    if (empty($b['district_id'])||empty($b['name'])) throw new Exception("district_id + name required");
    if (empty($b['id'])) { $pdo->prepare("INSERT INTO tourist_spots (district_id,name,description,lat,lng,best_time,image_url) VALUES (?,?,?,?,?,?,?)")->execute([$b['district_id'],$b['name'],$b['description']??'',($b['lat']??null)?:null,($b['lng']??null)?:null,$b['best_time']??'',$b['image_url']??'']); }
    else { $pdo->prepare("UPDATE tourist_spots SET district_id=?,name=?,description=?,lat=?,lng=?,best_time=?,image_url=? WHERE id=?")->execute([$b['district_id'],$b['name'],$b['description']??'',($b['lat']??null)?:null,($b['lng']??null)?:null,$b['best_time']??'',$b['image_url']??'',(int)$b['id']]); }
    echo json_encode(['ok'=>true]); break;
  case 'inquire':
    if (empty($b['package_id'])||empty($b['message'])) throw new Exception("package_id + message required");
    $pdo->prepare("INSERT INTO inquiries (package_id,user_id,message) VALUES (?,?,?)")->execute([$b['package_id'],$u['id'],$b['message']]);
    echo json_encode(['ok'=>true,'message'=>'Inquiry sent to agent']); break;
  case 'save_trip':
    $pdo->prepare("INSERT INTO custom_trips (user_id,origin,transit_mode,booking_pref,vehicle_id,payload,total_estimate) VALUES (?,?,?,?,?,?,?)")
      ->execute([$u['id'],$b['origin']??'',$b['transit_mode']??'train',$b['booking_pref']??'self',($b['vehicle_id']??null)?:null,json_encode($b['payload']??[]),$b['total']??0]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]); break;
  case 'my_listings':
    if (!$isVendor) throw new Exception("vendor only");
    $w = $u['role']==='admin' ? "" : "WHERE agent_id=".(int)$u['id'];
    $pkgs = $pdo->query("SELECT * FROM packages $w ORDER BY id DESC LIMIT 100")->fetchAll();
    $w2 = $u['role']==='admin' ? "" : "WHERE owner_id=".(int)$u['id'];
    $veh = $pdo->query("SELECT * FROM vehicles $w2 ORDER BY id DESC LIMIT 100")->fetchAll();
    $prop = $pdo->query("SELECT p.*, s.name spot FROM properties p JOIN tourist_spots s ON s.id=p.spot_id $w2 ORDER BY p.id DESC LIMIT 100")->fetchAll();
    echo json_encode(['ok'=>true,'packages'=>$pkgs,'vehicles'=>$veh,'properties'=>$prop]); break;
  default: throw new Exception("unknown action");
}
} catch (Throwable $e) { http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
