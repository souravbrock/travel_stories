#!/bin/bash
# Travel Stories end-to-end self-test (runs ON the server, no quoting pain)
set -u
BASE="https://tstory.reddevils.co.in"
E="tstory-selftest@test.local"
export MYSQL_PWD=$(cat ~/.tstory_dbpw)
Q="mysql -u reddevil_tstory reddevil_tstory"

echo "--- 1. register ---"
cat > /tmp/tst_reg.json <<EOF
{"name":"Self Test","email":"$E","phone":"+91-9000000001","role":"customer"}
EOF
curl -sk -m 25 -X POST "$BASE/api/auth_register.php" -H "Content-Type: application/json" --data @/tmp/tst_reg.json
echo
CODE=$($Q -N -s -e "SELECT code FROM email_otps WHERE email='$E' ORDER BY id DESC LIMIT 1;")
echo "otp-from-db=[$CODE]"
echo "--- 2. verify ---"
curl -sk -m 25 -X POST "$BASE/api/auth_verify.php" -H "Content-Type: application/json" -d "{\"email\":\"$E\",\"code\":\"$CODE\"}"
echo
echo "--- 3. set password ---"
TOKEN=$(curl -sk -m 25 -X POST "$BASE/api/auth_set_password.php" -H "Content-Type: application/json" -d "{\"email\":\"$E\",\"password\":\"Test@12345\"}" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo $j["token"]??("ERR:".json_encode($j));')
echo "token-len=${#TOKEN}"
echo "--- 4. me ---"
curl -sk -m 25 "$BASE/api/data.php?resource=me" -H "Authorization: Bearer $TOKEN"
echo
echo "--- 5. gated catalogue (spots for district 1) ---"
curl -sk -m 25 "$BASE/api/data.php?resource=spots&district_id=1" -H "Authorization: Bearer $TOKEN" | head -c 300
echo
echo "--- 6. save custom trip ---"
curl -sk -m 25 -X POST "$BASE/api/write.php" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"action":"save_trip","origin":"Kolkata","transit_mode":"train","booking_pref":"self","payload":{"tier":"deluxe","days":3},"total":12000}'
echo
echo "--- 7. cleanup test user ---"
$Q -e "DELETE FROM users WHERE email='$E';"
$Q -e "SELECT COUNT(*) AS remaining_test_users FROM users WHERE email='$E';"
rm -f /tmp/tst_reg.json
echo DONE
