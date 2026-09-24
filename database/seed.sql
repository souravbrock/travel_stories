-- Demo seed: states, districts (WB), spots, properties, vehicles, package
-- Import with: mysql --default-character-set=utf8mb4 ... < seed.sql
USE `reddevil_tstory`;

-- Admin: register admin@reddevils.co.in via the site (OTP + password), then promote:
--   UPDATE users SET role='admin' WHERE email='admin@reddevils.co.in';
INSERT IGNORE INTO users (name,email,phone,role,is_verified) VALUES
('Site Admin','admin@reddevils.co.in','+91-9800000000','customer',0);

INSERT IGNORE INTO states (name,slug,best_time,peak_note,highlights,color) VALUES
('West Bengal','west-bengal','Winter (Oct-Mar)','Peak: Dec-Jan for Darjeeling and Sundarbans','Darjeeling Himalayas - Sundarbans - Digha - Kalimpong','#e74c3c'),
('Rajasthan','rajasthan','Winter (Oct-Mar)','Peak: Dec-Feb for desert festivals','Jaipur - Udaipur - Jaisalmer - Pushkar','#3498db'),
('Kerala','kerala','Winter + Monsoon (Sep-Mar)','Peak: Dec-Jan houseboats','Kochi - Munnar - Alleppey - Kovalam','#27ae60'),
('Goa','goa','Winter (Nov-Feb)','Peak: Dec parties','Baga - Palolem - Dudhsagar - Old Goa','#f39c12'),
('Himachal Pradesh','himachal-pradesh','Summer + Winter (Mar-Jun, Dec-Feb)','Peak: May-Jun and Dec snow','Shimla - Manali - Dharamshala','#9b59b6');

-- West Bengal districts (demo subset)
INSERT IGNORE INTO districts (state_id, name)
SELECT s.id, d.n FROM states s JOIN
(SELECT 'Darjeeling' n UNION SELECT 'Kolkata' UNION SELECT 'South 24 Parganas' UNION SELECT 'Purba Medinipur' UNION SELECT 'Kalimpong') d
WHERE s.slug='west-bengal';

-- One row per spot, mapped to its correct district
INSERT IGNORE INTO tourist_spots (district_id, name, description, lat, lng, best_time)
SELECT d.id, v.n, v.des, v.lat, v.lng, v.bt FROM districts d JOIN
(SELECT 'Darjeeling' dn, 'Tiger Hill' n, 'Sunrise over Kanchenjunga, Darjeeling.' des, 27.0099 lat, 88.2630 lng, 'Winter' bt UNION ALL
 SELECT 'Darjeeling', 'Batasia Loop', 'Spiral railway loop with Himalayan view.', 27.0190, 88.2480, 'Winter' UNION ALL
 SELECT 'Kolkata', 'Victoria Memorial', 'Iconic Kolkata marble museum.', 22.5448, 88.3426, 'Winter' UNION ALL
 SELECT 'South 24 Parganas', 'Sundarbans Sajnekhali', 'Mangrove tiger reserve entry point.', 22.1200, 88.8200, 'Winter' UNION ALL
 SELECT 'Purba Medinipur', 'Digha Beach', 'Popular Bay of Bengal beach.', 21.6269, 87.5080, 'Winter') v ON v.dn = d.name
WHERE d.state_id = (SELECT id FROM states WHERE slug='west-bengal');

INSERT IGNORE INTO properties (spot_id, kind, name, tier, price_per_night, rating, amenities, is_verified)
SELECT s.id, 'hotel', CONCAT(s.name,' View Hotel'), 'deluxe', 2500, 4.2, 'WiFi,Parking,Restaurant,Room Service', 1 FROM tourist_spots s
WHERE NOT EXISTS (SELECT 1 FROM properties p WHERE p.spot_id = s.id AND p.kind='hotel');

INSERT IGNORE INTO properties (spot_id, kind, name, tier, price_per_night, rating, amenities, is_verified)
SELECT s.id, 'homestay', CONCAT(s.name,' Family Homestay'), 'budget', 1200, 4.6, 'Home food,WiFi,Local guide', 1 FROM tourist_spots s
WHERE NOT EXISTS (SELECT 1 FROM properties p WHERE p.spot_id = s.id AND p.kind='homestay');

INSERT IGNORE INTO vehicles (category, name, seats, price_per_day, is_verified) VALUES
('suv_muv','Toyota Innova Crysta',7,4500,1),
('suv_muv','Mahindra Xylo',7,3200,1),
('suv_muv','Mahindra Scorpio',7,3500,1),
('suv_muv','Mahindra Bolero',7,2800,1),
('group_luxury','Tata Winger',13,5000,1),
('group_luxury','Tempo Traveller 17S',17,6500,1),
('group_luxury','Luxury Sedan (Camry)',4,8000,1);

INSERT IGNORE INTO packages (agent_id, title, state_id, price, duration_days, itinerary) VALUES
(NULL,'Darjeeling Himalayan Classic 4N/5D',(SELECT id FROM states WHERE slug='west-bengal'),18500,5,'Day1 NJP-Darjeeling - Day2 Tiger Hill/Batasia - Day3 Kalimpong - Day4 Mall Road - Day5 Return'),
(NULL,'Sundarbans Mangrove Safari 2N/3D',(SELECT id FROM states WHERE slug='west-bengal'),9500,3,'Day1 Kolkata-Godkhali-Sajnekhali - Day2 Creek safari - Day3 Return');
