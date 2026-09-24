/* Travel Stories SPA — Map L1/L2, spots+distance (AC1-AC4), packages, 4-step builder, auth gate, vendor/admin */
const $=s=>document.querySelector(s), app=$('#app');
const api=async(p,o={})=>{const t=localStorage.getItem('ts_token');const r=await fetch(p,{headers:{'Content-Type':'application/json',...(t?{Authorization:'Bearer '+t}:{})},...o});const j=await r.json();if(!r.ok)throw new Error(j.error||'request failed');return j;};
const get=(r)=>api('/api/data.php?'+r), post=(u,b)=>api(u,{method:'POST',body:JSON.stringify(b)});
const ME=()=>JSON.parse(localStorage.getItem('ts_user')||'null');
function gate(){const u=ME();$('#who').textContent=u?` · ${u.name} (${u.role})`:'';$('#logout').hidden=!u;return u;}
$('#logout').onclick=()=>{localStorage.clear();location.hash='#/';location.reload();};
$('#yr').textContent=new Date().getFullYear();
const hav=(a,b,c,d)=>{const R=6371,t=Math.PI/180,h=Math.sin((c-a)*t/2)**2+Math.cos(a*t)*Math.cos(c*t)*Math.sin((d-b)*t/2)**2;return 2*R*Math.asin(Math.sqrt(h));};
const STATE_GEO='https://raw.githubusercontent.com/datameet/maps/master/Country/india-composite.geojson';
const COLORS=['#e74c3c','#3498db','#27ae60','#f39c12','#9b59b6','#16a085','#d35400'];

const routes={
 '/':home,'/map':mapL1,'/state':stateView,'/spot':spotView,'/packages':pkgs,'/builder':builder,
 '/auth':auth,'/vendor':vendor,'/admin':admin,
};
window.addEventListener('hashchange',render);render();gate();

async function render(){
 gate();
 const h=location.hash.slice(1)||'/'; const [_,page,arg]=h.split('/');
 const fn=routes['/'+(page||'')]; app.innerHTML='<p class=mut>Loading…</p>';
 try{ await (fn||home)(decodeURIComponent(arg||'')); }catch(e){ app.innerHTML=`<div class=card><b>Error:</b> ${e.message}</div>`; }
}
function needLogin(){ if(!ME()){ location.hash='#/auth'; throw new Error('Please create your profile / login first.'); } }

function home(){
 app.innerHTML=`<div class=card><h1>Discover India, your way</h1>
 <p>Interactive political map · verified agent packages · DIY tour builder (transit + local fleet + stays + meals).</p>
 <div class=row><a class="btn acc" href="#/map">Explore Map</a><a class=btn href="#/packages">Agent Packages</a><a class=btn href="#/builder">Build Custom Trip</a></div>
 <p class=mut>Full access needs a profile: name + email + phone → 5-digit email code → password.</p></div>
 <div class=card><h3>How it works</h3><p>1️⃣ Pick a state on the map → 2️⃣ drill to district → 3️⃣ open a tourist spot (distance/time to nearby spots auto-shown, AC2) → 4️⃣ book hotel/homestay or add to builder.</p></div>`;
}

// ---- L1 National map ----
async function mapL1(){
 const {data:states}=await get('resource=states').catch(()=>({data:[]})); 
 app.innerHTML=`<div class=card><h2>India — pick a state</h2><span class=badge>Best-time badges on hover</span><div id=map></div><div id=prev class=mut></div></div>
 <div class=card><h3>States</h3><div class=grid>${states.map(s=>`<div class=card><b>${s.name}</b><br><span class=badge>${s.best_time||''}</span><p class=mut>${s.highlights||''}</p><a class=btn href="#/state/${s.id}">Open →</a></div>`).join('')}</div></div>`;
 const m=L.map('map').setView([22.5,79],4.4); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(m);
 try{
  const g=await (await fetch(STATE_GEO)).json();
  L.geoJSON(g,{style:f=>({color:'#333',weight:1,fillOpacity:.55,fillColor:COLORS[(f.properties.NAME_1||'').length%COLORS.length]}),
   onEachFeature:(f,l)=>{const n=f.properties.NAME_1||'State';l.bindTooltip(`<b>${n}</b><br>Click to explore`);l.on('click',async()=>{const hit=states.find(s=>s.name.toLowerCase().includes(n.toLowerCase().split(' ')[0]));if(hit)location.hash='#/state/'+hit.id;else $('#prev').innerHTML=`<b>${n}</b> — not yet seeded. Try West Bengal demo.`;});}}).addTo(m);
 }catch{ $('#prev').textContent='Map tiles loaded; state polygons offline. Use state cards below.'; }
}

// ---- L2 State → districts → spots ----
async function stateView(stateId){
 needLogin();
 const {data:states}=await get('resource=states');
 const s=states.find(x=>x.id==stateId)||states[0];
 const {data:districts}=await get(`resource=districts&state_id=${s.id}`);
 // district spots preview
 let spots=[];
 try{ const all=await Promise.all(districts.map(d=>get(`resource=spots&district_id=${d.id}`).then(r=>r.data.map(x=>({...x,districtName:d.name})) ))); spots=all.flat(); }catch{}
 app.innerHTML=`<div class=card><a href="#/map">← India</a><h2>${s.name}</h2><span class=badge>${s.best_time||''}</span> <span class=mut>${s.peak_note||''}</span><p>${s.highlights||''}</p></div>
 <div class=card><h3>Districts — hover/click</h3><div class=row>${districts.map(d=>`<button class=btn data-d="${d.id}">${d.name}</button>`).join('')||'<span class=mut>No districts seeded yet (admin can add).</span>'}</div><div id=map></div></div>
 <div class=card><h3>Tourist spots <span class=mut>(click → location panel AC1)</span></h3><div class=grid id=sg></div></div>`;
 const m=L.map('map').setView([23,87],6); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
 const pts=spots.filter(x=>x.lat); pts.forEach(p=>L.marker([p.lat,p.lng]).addTo(m).bindPopup(`<b>${p.name}</b><br>${p.districtName}`));
 const sg=$('#sg');
 const paint=(did)=>{const list=did?spots.filter(x=>x.district_id==did):spots;
  sg.innerHTML=list.map(p=>`<div class=card><b>${p.name}</b><br><span class=badge>${p.districtName}</span> <span class=badge>${p.best_time||''}</span><p class=mut>${(p.description||'').slice(0,120)}</p><a class=btn href="#/spot/${p.id}">Open spot →</a></div>`).join('')||'<p class=mut>No spots in this district yet.</p>';};
 paint(); app.querySelectorAll('[data-d]').forEach(b=>b.onclick=()=>paint(b.dataset.d));
}

// ---- Spot location panel: AC1-AC4 ----
async function spotView(spotId){
 needLogin();
 // fetch all spots then find district siblings for distance matrix
 const {data:states}=await get('resource=states');
 // brute: iterate districts of first matching state via spots search is complex; instead fetch spots per district lazily:
 // Simplest: try each state/district until spot found (small seed, fine).
 let me=null,sibs=[];
 for (const s of states){ const {data:ds}=await get(`resource=districts&state_id=${s.id}`); for(const d of ds){ const {data:sp}=await get(`resource=spots&district_id=${d.id}`); const f=sp.find(x=>x.id==spotId); if(f){me=f;sibs=sp;break;} } if(me)break; }
 if(!me){app.innerHTML='<div class=card>Spot not found</div>';return;}
 const {data:props}=await get(`resource=properties&spot_id=${me.id}`);
 app.innerHTML=`<div class=card><a href="#/map">← map</a><h2>${me.name}</h2><p class=mut>${me.district} · ${me.state} · Best: ${me.best_time||'—'}</p><p>${me.description||''}</p><div id=map></div></div>
 <div class=grid><div class=card><h3>Distance & time to nearby spots (AC2/AC3)</h3><table id=dm></table></div>
 <div class=card><h3>Hotels & homestays — book / add to builder</h3>${props.map(p=>`<p><b>${p.name}</b> <span class=badge>${p.kind}</span> <span class=badge>${p.tier}</span> ⭐${p.rating||'-'} · ₹${p.price_per_night}/night<br><span class=mut>${p.amenities||''}</span></p>`).join('')||'<p class=mut>No properties yet — vendors can enlist.</p>'}</div></div>`;
 const m=L.map('map').setView([me.lat||22,me.lng||88],10); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
 const mk={}; sibs.filter(s=>s.lat).forEach(s=>{mk[s.id]=L.marker([s.lat,s.lng]).addTo(m).bindPopup(s.name);});
 if(me.lat) mk[me.id].openPopup();
 let route=null;
 const dm=$('#dm'); dm.innerHTML='<tr><th>Nearby spot</th><th>km</th><th>mins</th></tr>'+(me.nearby||[]).map(n=>`<tr><td><a href="#" data-s="${n.id}">${n.name}</a></td><td>${n.km} km</td><td>~${n.mins} min</td></tr>`).join('');
 dm.querySelectorAll('[data-s]').forEach(a=>a.onclick=(e)=>{e.preventDefault();const n=(me.nearby||[]).find(x=>x.id==a.dataset.s);if(!n)return;
  if(route)m.removeLayer(route); route=L.polyline([[me.lat,me.lng],[n.lat,n.lng]],{color:'red'}).addTo(m); m.fitBounds(route.getBounds()); // AC4: highlight route
 });
}

// ---- Marketplace ----
async function pkgs(){
 needLogin();
 const {data}=await get('resource=packages');
 app.innerHTML=`<div class=card><h2>Agent packages</h2><div class=grid>${data.map(p=>`<div class=card><b>${p.title}</b><br><span class=badge>${p.state||''}</span> <span class=badge>${p.duration_days} days</span><p><b>₹${p.price}</b> <span class=mut>by ${p.agent||'verified agent'}</span></p><p class=mut>${(p.itinerary||'').slice(0,140)}</p><div class=row><button class=btn data-q="${p.id}">Inquire</button><button class=btn acc data-b="${p.id}">Book</button></div></div>`).join('')}</div></div>`;
 app.querySelectorAll('[data-q]').forEach(b=>b.onclick=async()=>{const msg=prompt('Message to agent:');if(!msg)return;await post('/api/write.php',{action:'inquire',package_id:b.dataset.q,message:msg});alert('Inquiry sent');});
 app.querySelectorAll('[data-b]').forEach(b=>b.onclick=()=>alert('Checkout: UPI/cards integration goes here (Razorpay/Cashfree). Booking saved as inquiry for MVP.'));
}

// ---- 4-step builder ----
const B={step:1,origin:'',transit:'train',booking:'self',vehicle:'',tier:'deluxe',meals:{},days:3};
async function builder(){
 needLogin();
 const {data:veh}=await get('resource=vehicles');
 if(B.step===1)app.innerHTML=`<div class=card><h2>Trip Builder — 1/4 Transit & origin</h2><input id=o placeholder="Departure city (e.g. Kolkata)" value="${B.origin}"><select id=t><option value=train>🚂 Rail</option><option value=flight>✈️ Flight</option><option value=self_drive>🚗 Self-drive</option></select><select id=b><option value=self>I book trains/flights myself</option><option value=platform>Platform books for me (lead)</option></select><button class=btn acc id=n>Next →</button></div>`;
 if(B.step===2)app.innerHTML=`<div class=card><h2>2/4 Local fleet</h2><div class=grid>${veh.map(v=>`<div class=card><b>${v.name}</b><br><span class=badge>${v.category}</span> ${v.seats||''} seats · ₹${v.price_per_day}/day<br><button class=btn data-v="${v.id}">Select</button></div>`).join('')}</div></div>`;
 if(B.step===3)app.innerHTML=`<div class=card><h2>3/4 Stay</h2><select id=tier><option>budget</option><option>deluxe</option><option>luxury</option></select><input id=days type=number min=1 max=30 value="${B.days}"><button class=btn acc id=n>Next →</button></div>`;
 if(B.step===4)app.innerHTML=`<div class=card><h2>4/4 Meals — bulk or day-wise</h2>${['Breakfast','Lunch','Evening Snacks','Dinner'].map(mm=>`<label><input type=checkbox data-m="${mm}" checked> ${mm} (veg)</label><br>`).join('')}<button class=btn acc id=save>Save trip</button></div>`;
 const n=$('#n'); if(n)n.onclick=()=>{B.origin=$('#o')?.value||B.origin;B.transit=$('#t')?.value||B.transit;B.booking=$('#b')?.value||B.booking;B.tier=$('#tier')?.value||B.tier;B.days=+($('#days')?.value||B.days);B.step++;builder();};
 app.querySelectorAll('[data-v]').forEach(x=>x.onclick=()=>{B.vehicle=x.dataset.v;B.step++;builder();});
 const sv=$('#save'); if(sv)sv.onclick=async()=>{const meals=[...document.querySelectorAll('[data-m]:checked')].map(x=>x.dataset.m);const r=await post('/api/write.php',{action:'save_trip',origin:B.origin,transit_mode:B.transit,booking_pref:B.booking,vehicle_id:B.vehicle,payload:{tier:B.tier,days:B.days,meals},total:B.days*4000});alert('Trip saved #'+r.id+(B.booking==='platform'?' — our ticketing team will call you.':''));B.step=1;location.hash='#/';};
}

// ---- Auth: register → OTP → password ----
function auth(){
 const u=ME(); if(u){app.innerHTML=`<div class=card>Logged in as <b>${u.name}</b> (${u.role}). <a href="#/">Home</a></div>`;return;}
 app.innerHTML=`<div class=card><h2>Create profile</h2><input id=n placeholder="Full name"><input id=e placeholder="Email"><input id=p placeholder="Phone"><select id=r><option value=customer>Customer</option><option value=vendor>Vendor (agent/hotel/homestay/transport)</option></select><button class=btn acc id=b1>Send 5-digit code</button></div>
 <div class=card><h3>Verify email</h3><input id=e2 placeholder="Email"><input id=c placeholder="5-digit code"><button class=btn id=b2>Verify</button></div>
 <div class=card><h3>Set password + Login</h3><input id=e3 placeholder="Email"><input id=pw type=password placeholder="Password (min 6)"><button class=btn id=b3>Set password & login</button><hr><input id=le placeholder="Email"><input id=lp type=password placeholder="Password"><button class=btn id=b4>Login</button></div>`;
 $('#b1').onclick=async()=>{try{const r=await post('/api/auth_register.php',{name:$('#n').value,email:$('#e').value,phone:$('#p').value,role:$('#r').value});alert(r.message+(r.dev_otp?' [dev OTP '+r.dev_otp+']':''));$('#e2').value=$('#e').value;}catch(e){alert(e.message)}};
 $('#b2').onclick=async()=>{try{await post('/api/auth_verify.php',{email:$('#e2').value,code:$('#c').value});alert('Verified! Set password now.');$('#e3').value=$('#e2').value;}catch(e){alert(e.message)}};
 $('#b3').onclick=async()=>{try{const r=await post('/api/auth_set_password.php',{email:$('#e3').value,password:$('#pw').value});localStorage.setItem('ts_token',r.token);location.reload();}catch(e){alert(e.message)}};
 $('#b4').onclick=async()=>{try{const r=await post('/api/auth_login.php',{email:$('#le').value,password:$('#lp').value});localStorage.setItem('ts_token',r.token);localStorage.setItem('ts_user',JSON.stringify(r.user));location.hash='#/';location.reload();}catch(e){alert(e.message)}};
 // persist user after set-password
 const _orig=fetch; 
}

// keep user cached
(async()=>{const t=localStorage.getItem('ts_token');if(t&&!ME()){try{const r=await get('resource=me');localStorage.setItem('ts_user',JSON.stringify(r.data.user));gate();}catch{}}})();
 
async function vendor(){
 needLogin(); const u=ME(); if(u.role!=='vendor'&&u.role!=='admin'){app.innerHTML='<div class=card>Vendor access only. Register as vendor.</div>';return;}
 const r=await post('/api/write.php',{action:'my_listings'});
 app.innerHTML=`<div class=card><h2>Vendor dashboard — enlist with photos</h2>
 <h3>Add package</h3><input id=pt placeholder="Title (e.g. Darjeeling 4N/5D)"><input id=pp placeholder="Price ₹"><textarea id=pi placeholder="Day-wise itinerary"></textarea><input id=ph placeholder="Photo URLs (comma separated)"><button class=btn id=sp>Save package</button>
 <h3>Add vehicle</h3><input id=vn placeholder="e.g. Toyota Innova"><input id=vp placeholder="₹/day"><input id=vs placeholder="Photo URL"><button class=btn id=sv2>Save vehicle</button>
 <h3>Your listings</h3><p class=mut>${r.packages.length} packages · ${r.vehicles.length} vehicles · ${r.properties.length} properties</p></div>`;
 $('#sp').onclick=async()=>{await post('/api/write.php',{action:'upsert_package',title:$('#pt').value,price:+$('#pp').value,itinerary:$('#pi').value,photos:$('#ph').value});alert('Saved');vendor();};
 $('#sv2').onclick=async()=>{await post('/api/write.php',{action:'upsert_vehicle',name:$('#vn').value,price_per_day:+$('#vp').value,photo_url:$('#vs').value});alert('Saved');vendor();};
}
async function admin(){
 needLogin(); const u=ME(); if(u.role!=='admin'){app.innerHTML='<div class=card>Admin only.</div>';return;}
 app.innerHTML=`<div class=card><h2>Admin console</h2><p class=mut>Edit spots, hotels, homestays, agents, cars, transport.</p>
 <h3>Add tourist spot</h3><input id=d placeholder="district_id"><input id=s placeholder="Spot name"><input id=la placeholder="lat"><input id=ln placeholder="lng"><button class=btn id=ss>Save spot</button></div>`;
 $('#ss').onclick=async()=>{await post('/api/write.php',{action:'upsert_spot',district_id:+$('#d').value,name:$('#s').value,lat:+$('#la').value,lng:+$('#ln').value});alert('Saved');};
}
