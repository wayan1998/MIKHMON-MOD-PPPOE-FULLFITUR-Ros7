<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  // get MikroTik system clock
  $getclock = $API->comm("/system/clock/print");
  $clock = $getclock[0];
  $timezone = $getclock[0]['time-zone-name'];
  $_SESSION['timezone'] = $timezone;
  date_default_timezone_set($timezone);

  // get system resource MikroTik
  $getresource = $API->comm("/system/resource/print");
  $resource = $getresource[0];

  // get routeboard info
  $getrouterboard = $API->comm("/system/routerboard/print");
  $routerboard = $getrouterboard[0];

  // get & counting hotspot users
  $countallusers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));

  // get & counting hotspot active
  $counthotspotactive = $API->comm("/ip/hotspot/active/print", array("count-only" => ""));

  if ($livereport == "disable") {
    $logh = "457px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "350px";
    $lreport = "style='display:block;'";
  }

  // ---------- PPP ----------
  $pppprofiles = $API->comm("/ppp/profile/print");
  $countprofiles = is_array($pppprofiles) ? count($pppprofiles) : 0;

  $pppsecrets = $API->comm("/ppp/secret/print");
  if (!is_array($pppsecrets)) { $pppsecrets = array(); }
  $countsecrets = count($pppsecrets);

  $pppactive = $API->comm("/ppp/active/print");
  if (!is_array($pppactive)) { $pppactive = array(); }
  $countpppactive = count($pppactive);

  // PPPoE tidak aktif = secret yang aktif (enabled) tapi tidak ada di daftar /ppp/active
  $activenames = array();
  foreach ($pppactive as $pa) {
    if (isset($pa['name'])) {
      $activenames[strtolower($pa['name'])] = true;
    }
  }
  $pppinactive = array();
  $countdisabled = 0;
  foreach ($pppsecrets as $ps) {
    if (!isset($ps['name'])) { continue; }
    if (isset($ps['disabled']) && $ps['disabled'] == "true") {
      $countdisabled++;
      continue;
    }
    if (!isset($activenames[strtolower($ps['name'])])) {
      $pppinactive[] = $ps;
    }
  }
  $countpppinactive = count($pppinactive);
  $lbl_ppp_inactive = isset($_ppp_inactive) ? $_ppp_inactive : "PPPoE Tidak Aktif";
}
?>

<style>
/* ===== Tampilan baru dashboard Mikhmon (scoped ke #reloadHome) ===== */
#reloadHome{
  --bg:#0e1730; --surface:#15213f; --surface2:#1b2a4e; --line:#27396a;
  --text:#e9eefb; --muted:#8fa0c6;
  --on:#2ec4a6; --off:#ff6b6b; --warn:#ffb84d; --info:#4aa8ff; --vio:#9b8cff;
  font-family:"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif;
  color:var(--text);
}
#reloadHome .card{
  background:var(--surface); border:1px solid var(--line); border-radius:14px;
  box-shadow:none; overflow:hidden; margin-bottom:16px;
}
#reloadHome .card-header{
  background:var(--surface2); border-bottom:1px solid var(--line); padding:12px 16px;
}
#reloadHome .card-header h3, #reloadHome .card-header h3 a{
  color:var(--text); font-size:15px; font-weight:600; margin:0; letter-spacing:.2px;
}
#reloadHome .card-header h3 .fa{ color:var(--info); margin-right:6px; }
#reloadHome .card-body{ background:transparent; color:var(--text); padding:14px; }

/* kotak info sistem & kotak statistik bawaan */
#reloadHome .box{
  background:var(--surface); color:var(--text); border:1px solid var(--line);
  border-radius:12px; box-shadow:none;
}
#reloadHome .box-bordered{ border-left:4px solid var(--info); }
#reloadHome .box-group-icon{
  background:var(--surface2); color:var(--info); border-radius:10px 0 0 10px;
}
#reloadHome .box-group-area{ color:var(--text); line-height:1.6; }
#reloadHome .box a, #reloadHome .box a:hover{ color:inherit; text-decoration:none; display:block; }
#reloadHome .box h1{ color:#fff; font-weight:700; margin:0 0 4px; }
#reloadHome .box h1 span{ color:rgba(255,255,255,.75); font-weight:400; }
#reloadHome .bg-blue  { background:linear-gradient(135deg,#1f5fbf,#2f86e8); border-color:transparent; }
#reloadHome .bg-green { background:linear-gradient(135deg,#128a74,#2ec4a6); border-color:transparent; }
#reloadHome .bg-yellow{ background:linear-gradient(135deg,#c78a14,#f0a93a); border-color:transparent; }
#reloadHome .bg-red   { background:linear-gradient(135deg,#c2384a,#ff6b6b); border-color:transparent; }
#reloadHome .box.bg-blue, #reloadHome .box.bg-green,
#reloadHome .box.bg-yellow, #reloadHome .box.bg-red{ color:#fff; transition:transform .15s; }
#reloadHome .box.bg-blue:hover, #reloadHome .box.bg-green:hover,
#reloadHome .box.bg-yellow:hover, #reloadHome .box.bg-red:hover{ transform:translateY(-2px); }

/* tabel (log hotspot & daftar PPPoE) */
#reloadHome .table{ color:var(--text); background:transparent; border-color:var(--line); }
#reloadHome .table th{ background:var(--surface2); color:var(--muted); border-color:var(--line); font-weight:600; }
#reloadHome .table td{ border-color:var(--line); color:var(--text); }
#reloadHome .table-hover tbody tr:hover{ background:rgba(74,168,255,.08); }
#reloadHome .overflow{ scrollbar-width:thin; }

/* grid statistik PPP */
.mk-stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
.mk-stat{
  display:block; border-radius:12px; padding:14px 16px; color:#fff !important;
  text-decoration:none !important; transition:transform .15s;
}
.mk-stat:hover{ transform:translateY(-2px); }
.mk-stat b{ display:block; font-size:30px; line-height:1.1; font-weight:700; }
.mk-stat small{ display:block; margin-top:6px; font-size:13px; opacity:.92; }
.mk-on  { background:linear-gradient(135deg,#128a74,#2ec4a6); }
.mk-off { background:linear-gradient(135deg,#c2384a,#ff6b6b); }
.mk-sec { background:linear-gradient(135deg,#c78a14,#f0a93a); }
.mk-prof{ background:linear-gradient(135deg,#5b49d6,#9b8cff); }

/* kartu PPPoE tidak aktif */
.mk-inact-head{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px; }
.mk-badge{
  display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600;
  background:rgba(255,107,107,.15); color:var(--off); border:1px solid rgba(255,107,107,.4);
}
.mk-badge.dis{ background:rgba(143,160,198,.15); color:var(--muted); border-color:rgba(143,160,198,.4); }
#mk-search{
  background:var(--bg); border:1px solid var(--line); color:var(--text);
  border-radius:8px; padding:7px 12px; min-width:200px; outline:none;
}
#mk-search:focus{ border-color:var(--info); box-shadow:0 0 0 2px rgba(74,168,255,.25); }
.mk-scroll{ max-height:320px; overflow:auto; border:1px solid var(--line); border-radius:10px; }
.mk-scroll table{ margin:0; width:100%; }
.mk-scroll th{ position:sticky; top:0; z-index:1; }
.mk-dot{ display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--off); margin-right:8px; }
.mk-empty{ padding:22px; text-align:center; color:var(--on); }
.mk-empty .fa{ font-size:26px; display:block; margin-bottom:6px; }

@media (max-width:900px){ .mk-stats{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:480px){ #mk-search{ width:100%; } }
@media (prefers-reduced-motion:reduce){ .mk-stat,#reloadHome .box{ transition:none !important; } }
</style>

<div id="reloadHome">

    <div id="r_1" class="row">
      <div class="col-4">
        <div class="box bmh-75 box-bordered">
          <div class="box-group">
            <div class="box-group-icon"><i class="fa fa-calendar"></i></div>
              <div class="box-group-area">
                <span ><?= $_system_date_time ?><br>
                    <?php 
                    echo ucfirst($clock['date']) . " " . $clock['time'] . "<br>
                    ".$_uptime." : " . formatDTM($resource['uptime']);
                    $_SESSION[$session.'sdate'] = $clock['date'];
                    ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      <div class="col-4">
        <div class="box bmh-75 box-bordered">
          <div class="box-group">
          <div class="box-group-icon"><i class="fa fa-info-circle"></i></div>
              <div class="box-group-area">
                <span >
                    <?php
                    echo $_board_name." : " . $resource['board-name'] . "<br/>
                    ".$_model." : " . $routerboard['model'] . "<br/>
                    Router OS : " . $resource['version'];
                    ?>
                </span>
              </div>
            </div>
          </div>
        </div>
    <div class="col-4">
      <div class="box bmh-75 box-bordered">
        <div class="box-group">
          <div class="box-group-icon"><i class="fa fa-server"></i></div>
              <div class="box-group-area">
                <span >
                    <?php
                    echo $_cpu_load." : " . $resource['cpu-load'] . "%<br/>
                    ".$_free_memory." : " . formatBytes($resource['free-memory'], 2) . "<br/>
                    ".$_free_hdd." : " . formatBytes($resource['free-hdd-space'], 2)
                    ?>
                </span>
                </div>
              </div>
            </div>
          </div> 
      </div>

        <div class="row">
          <div  class="col-8">
            <div id="r_2" class="row">
            <div class="card">
              <div class="card-header"><h3><i class="fa fa-wifi"></i> Hotspot</h3></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-3 col-box-6">
                      <div class="box bg-blue bmh-75">
                        <a onclick="cancelPage()" href="./?hotspot=active&session=<?= $session; ?>">
                          <h1><?= $counthotspotactive; ?>
                              <span style="font-size: 15px;"><?= $counthotspotactive > 1 ? "items" : "item"; ?></span>
                            </h1>
                          <div>
                            <i class="fa fa-laptop"></i> <?= $_hotspot_active ?>
                          </div>
                        </a>
                      </div>
                    </div>
                    <div class="col-3 col-box-6">
                    <div class="box bg-green bmh-75">
                      <a onclick="cancelPage()" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
                            <h1><?= $countallusers; ?>
                              <span style="font-size: 15px;"><?= $countallusers > 1 ? "items" : "item"; ?></span>
                            </h1>
                      <div>
                            <i class="fa fa-users"></i> <?= $_hotspot_users ?>
                          </div>
                      </a>
                    </div>
                  </div>
                  <div class="col-3 col-box-6">
                    <div class="box bg-yellow bmh-75">
                      <a onclick="cancelPage()" href="./?hotspot-user=add&session=<?= $session; ?>">
                        <div>
                          <h1><i class="fa fa-user-plus"></i>
                              <span style="font-size: 15px;"><?= $_add ?></span>
                          </h1>
                        </div>
                        <div>
                            <i class="fa fa-user-plus"></i> <?= $_hotspot_users ?>
                        </div>
                      </a>
                    </div>
                  </div>
                  <div class="col-3 col-box-6">
                    <div class="box bg-red bmh-75">
                      <a onclick="cancelPage()" href="./?hotspot-user=generate&session=<?= $session; ?>">
                        <div>
                          <h1><i class="fa fa-user-plus"></i>
                              <span style="font-size: 15px;"><?= $_generate ?></span>
                          </h1>
                        </div>
                        <div>
                            <i class="fa fa-user-plus"></i> <?= $_hotspot_users ?>
                        </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div>

          <!-- ================= PPP ================= -->
          <div id="r_ppp" class="row">
            <div class="card">
              <div class="card-header">
                <h3><i class="fa fa-plug"></i> PPP</h3>
              </div>
              <div class="card-body">
                <div class="mk-stats">
                  <a class="mk-stat mk-on" onclick="cancelPage()" href="./?ppp=active&session=<?= $session; ?>">
                    <b><?= $countpppactive; ?></b>
                    <small><i class="fa fa-laptop"></i> <?= $_ppp_active ?></small>
                  </a>
                  <a class="mk-stat mk-off" href="#ppp-inactive">
                    <b><?= $countpppinactive; ?></b>
                    <small><i class="fa fa-chain-broken"></i> <?= htmlspecialchars($lbl_ppp_inactive); ?></small>
                  </a>
                  <a class="mk-stat mk-sec" onclick="cancelPage()" href="./?ppp=secrets&session=<?= $session; ?>">
                    <b><?= $countsecrets; ?></b>
                    <small><i class="fa fa-user-secret"></i> <?= $_ppp_secrets ?></small>
                  </a>
                  <a class="mk-stat mk-prof" onclick="cancelPage()" href="./?ppp=profiles&session=<?= $session; ?>">
                    <b><?= $countprofiles; ?></b>
                    <small><i class="fa fa-users"></i> <?= $_ppp_profiles ?></small>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- ============ PPPoE TIDAK AKTIF ============ -->
          <div id="ppp-inactive" class="row">
            <div class="card">
              <div class="card-header">
                <h3><i class="fa fa-chain-broken"></i> <?= htmlspecialchars($lbl_ppp_inactive); ?></h3>
              </div>
              <div class="card-body">
                <div class="mk-inact-head">
                  <div>
                    <span class="mk-badge"><?= $countpppinactive; ?> offline</span>
                    <?php if ($countdisabled > 0) { ?>
                      <span class="mk-badge dis"><?= $countdisabled; ?> disabled</span>
                    <?php } ?>
                  </div>
                  <input type="text" id="mk-search" placeholder="Cari nama / profil..." autocomplete="off">
                </div>

                <?php if ($countpppinactive == 0) { ?>
                  <div class="mk-empty">
                    <i class="fa fa-check-circle"></i>
                    Semua pelanggan PPPoE sedang online.
                  </div>
                <?php } else { ?>
                  <div class="mk-scroll">
                    <table class="table table-sm table-hover" id="mk-inact-table" style="font-size:13px;">
                      <thead>
                        <tr>
                          <th>Nama</th>
                          <th>Profil</th>
                          <th>Service</th>
                          <th>Terakhir putus</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($pppinactive as $row) { ?>
                        <tr>
                          <td><span class="mk-dot"></span><?= htmlspecialchars($row['name']); ?></td>
                          <td><?= htmlspecialchars(isset($row['profile']) ? $row['profile'] : '-'); ?></td>
                          <td><?= htmlspecialchars(isset($row['service']) ? $row['service'] : '-'); ?></td>
                          <td><?= htmlspecialchars(isset($row['last-logged-out']) && $row['last-logged-out'] != '' ? $row['last-logged-out'] : '-'); ?></td>
                        </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
          <script type="text/javascript">
            (function () {
              var input = document.getElementById('mk-search');
              if (!input) return;
              input.addEventListener('input', function () {
                var q = input.value.toLowerCase();
                var rows = document.querySelectorAll('#mk-inact-table tbody tr');
                for (var i = 0; i < rows.length; i++) {
                  rows[i].style.display = rows[i].textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
                }
              });
            })();
          </script>

			<div class="card">
				<div class="card-header">
					<h3><i class="fa fa-area-chart"></i> <?= $_traffic ?> </h3>
				</div>


              <div class="card-body">
  
                  <?php $getinterface = $API->comm("/interface/print");
                  $interface = $getinterface[$iface - 1]['name']; 
                  ?>
                  
                  <script type="text/javascript"> 
                    var chart;
                    var sessiondata = "<?= $session ?>";
                    var interface = "<?= $interface ?>";
                    var n = 3000;
                    function requestDatta(session,iface) {
                      $.ajax({
                        url: './traffic/traffic.php?session='+session+'&iface='+iface,
                        datatype: "json",
                        success: function(data) {
                          var midata = JSON.parse(data);
                          if( midata.length > 0 ) {
                            var TX=parseInt(midata[0].data);
                            var RX=parseInt(midata[1].data);
                            var x = (new Date()).getTime(); 
                            shift=chart.series[0].data.length > 19;
                            chart.series[0].addPoint([x, TX], true, shift);
                            chart.series[1].addPoint([x, RX], true, shift);
                          }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) { 
                          console.error("Status: " + textStatus + " request: " + XMLHttpRequest); console.error("Error: " + errorThrown); 
                        }       
                      });
                    }	

                    $(document).ready(function() {
                        Highcharts.setOptions({
                          global: {
                            useUTC: false
                          }
                        });

                        Highcharts.addEvent(Highcharts.Series, 'afterInit', function () {
	                        this.symbolUnicode = {
    	                    circle: '●',
                          diamond: '♦',
                          square: '■',
                          triangle: '▲',
                          'triangle-down': '▼'
                          }[this.symbol] || '●';
                        });

                          chart = new Highcharts.Chart({
                          chart: {
                          renderTo: 'trafficMonitor',
                          animation: Highcharts.svg,
                          type: 'areaspline',
                          backgroundColor: 'transparent',
                          events: {
                            load: function () {
                              setInterval(function () {
                                requestDatta(sessiondata,interface);
                              }, 8000);
                            }				
                          }
                        },
                        title: {
                          text: '<?= $_interface ?> ' + interface,
                          style: { color: '#e9eefb', fontSize: '14px' }
                        },
                        colors: ['#4aa8ff', '#2ec4a6'],
                        legend: { itemStyle: { color: '#8fa0c6' } },
                        credits: { enabled: false },
                        xAxis: {
                          type: 'datetime',
                          tickPixelInterval: 150,
                          maxZoom: 20 * 1000,
                          labels: { style: { color: '#8fa0c6' } },
                          lineColor: '#27396a',
                          tickColor: '#27396a'
                        },
                        yAxis: {
                            minPadding: 0.2,
                            maxPadding: 0.2,
                            gridLineColor: '#27396a',
                            title: {
                              text: null
                            },
                            labels: {
                              style: { color: '#8fa0c6' },
                              formatter: function () {      
                                var bytes = this.value;                          
                                var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
                                if (bytes == 0) return '0 bps';
                                var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                                return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];                    
                              },
                            },       
                        },
                        
                        series: [{
                          name: 'Tx',
                          data: [],
                          marker: {
                            symbol: 'circle'
                          }
                        }, {
                          name: 'Rx',
                          data: [],
                          marker: {
                            symbol: 'circle'
                          }
                        }],

                        tooltip: {
                          formatter: function () { 
                            var _0x2f7f=["\x70\x6F\x69\x6E\x74\x73","\x79","\x62\x70\x73","\x6B\x62\x70\x73","\x4D\x62\x70\x73","\x47\x62\x70\x73","\x54\x62\x70\x73","\x3C\x73\x70\x61\x6E\x20\x73\x74\x79\x6C\x65\x3D\x22\x63\x6F\x6C\x6F\x72\x3A","\x63\x6F\x6C\x6F\x72","\x73\x65\x72\x69\x65\x73","\x3B\x20\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x2E\x35\x65\x6D\x3B\x22\x3E","\x73\x79\x6D\x62\x6F\x6C\x55\x6E\x69\x63\x6F\x64\x65","\x3C\x2F\x73\x70\x61\x6E\x3E\x3C\x62\x3E","\x6E\x61\x6D\x65","\x3A\x3C\x2F\x62\x3E\x20\x30\x20\x62\x70\x73","\x70\x75\x73\x68","\x6C\x6F\x67","\x66\x6C\x6F\x6F\x72","\x3A\x3C\x2F\x62\x3E\x20","\x74\x6F\x46\x69\x78\x65\x64","\x70\x6F\x77","\x20","\x65\x61\x63\x68","\x3C\x62\x3E\x4D\x69\x6B\x68\x6D\x6F\x6E\x20\x54\x72\x61\x66\x66\x69\x63\x20\x4D\x6F\x6E\x69\x74\x6F\x72\x3C\x2F\x62\x3E\x3C\x62\x72\x20\x2F\x3E\x3C\x62\x3E\x54\x69\x6D\x65\x3A\x20\x3C\x2F\x62\x3E","\x25\x48\x3A\x25\x4D\x3A\x25\x53","\x78","\x64\x61\x74\x65\x46\x6F\x72\x6D\x61\x74","\x3C\x62\x72\x20\x2F\x3E","\x20\x3C\x62\x72\x2F\x3E\x20","\x6A\x6F\x69\x6E"];var s=[];$[_0x2f7f[22]](this[_0x2f7f[0]],function(_0x3735x2,_0x3735x3){var _0x3735x4=_0x3735x3[_0x2f7f[1]];var _0x3735x5=[_0x2f7f[2],_0x2f7f[3],_0x2f7f[4],_0x2f7f[5],_0x2f7f[6]];if(_0x3735x4== 0){s[_0x2f7f[15]](_0x2f7f[7]+ this[_0x2f7f[9]][_0x2f7f[8]]+ _0x2f7f[10]+ this[_0x2f7f[9]][_0x2f7f[11]]+ _0x2f7f[12]+ this[_0x2f7f[9]][_0x2f7f[13]]+ _0x2f7f[14])};var _0x3735x2=parseInt(Math[_0x2f7f[17]](Math[_0x2f7f[16]](_0x3735x4)/ Math[_0x2f7f[16]](1024)));s[_0x2f7f[15]](_0x2f7f[7]+ this[_0x2f7f[9]][_0x2f7f[8]]+ _0x2f7f[10]+ this[_0x2f7f[9]][_0x2f7f[11]]+ _0x2f7f[12]+ this[_0x2f7f[9]][_0x2f7f[13]]+ _0x2f7f[18]+ parseFloat((_0x3735x4/ Math[_0x2f7f[20]](1024,_0x3735x2))[_0x2f7f[19]](2))+ _0x2f7f[21]+ _0x3735x5[_0x3735x2])});return _0x2f7f[23]+ Highcharts[_0x2f7f[26]](_0x2f7f[24], new Date(this[_0x2f7f[25]]))+ _0x2f7f[27]+ s[_0x2f7f[29]](_0x2f7f[28])
                          },
                          shared: true                                                      
                        },
                      });
                    });
                  </script>
                  <div id="trafficMonitor"></div>
                </div> 
              </div>
            </div>  
            <div class="col-4">
            <div id="r_4" class="row">
              <div <?= $lreport; ?> class="box bmh-75 box-bordered">
                <div class="box-group">
                  <div class="box-group-icon"><i class="fa fa-money"></i></div>
                    <div class="box-group-area">
                      <span >
                        <div id="reloadLreport">
                          <?php 
                          if ($_SESSION[$session.'sdate'] == $_SESSION[$session.'idhr']){
                            echo $_income." <br/>" . "
                          ".$_today." " . $_SESSION[$session.'totalHr'] . "vcr : " . $currency . " " . $_SESSION[$session.'dincome']. "<br/>
                          ".$_this_month." " . $_SESSION[$session.'totalBl'] . "vcr : " . $currency . " " . $_SESSION[$session.'mincome']; 
                          }else{
                            echo "<div id='loader' ><i><span> <i class='fa fa-circle-o-notch fa-spin'></i> ". $_processing." </i></div>";
                          }
                          ?>                       
                        </div>
                    </span>
                </div>
              </div>
            </div>
            </div>
            <div id="r_3" class="row">
            <div class="card">
              <div class="card-header">
                <h3><a onclick="cancelPage()" href="./?hotspot=log&session=<?= $session; ?>" title="Open Hotspot Log" ><i class="fa fa-align-justify"></i> <?= $_hotspot_log ?></a></h3></div>
                  <div class="card-body">
                    <div style="padding: 5px; height: <?= $logh; ?> ;" class="mr-t-10 overflow">
                      <table class="table table-sm table-bordered table-hover" style="font-size: 12px; td.padding:2px;">
                        <thead>
                          <tr>
                            <th><?= $_time ?></th>
                            <th><?= $_users ?> (IP)</th>
                            <th><?= $_messages ?></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td colspan="3" class="text-center">
                            <div id="loader" ><i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?> </i></div>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              </div>
            </div>
</div>
</div>