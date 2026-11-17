<?php
/*
 *  Halaman Traffic Pelanggan PPPoE (tambahan untuk Mikhmon v3)
 *  Menampilkan pemakaian download/upload (GB) dan jumlah logout per pelanggan.
 *
 *  Cara kerja: RouterOS tidak menyimpan total pemakaian per pelanggan. Setiap halaman ini
 *  dibuka/direfresh, halaman membaca counter byte dari interface PPPoE yang sedang aktif,
 *  lalu menjumlahkannya ke file data di ./include/ppp-traffic-NAMASESI.php
 */

// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  if (isset($_SESSION['timezone']) && $_SESSION['timezone'] != '') {
    date_default_timezone_set($_SESSION['timezone']);
  }

  // ---------- helper ----------
  if (!function_exists('mk_gb')) {
    function mk_gb($b) {
      return number_format($b / 1073741824, 2, '.', '');
    }
    function mk_mb($b) {
      return number_format($b / 1048576, 1, '.', '');
    }
    function mk_default_state() {
      return array('started' => time(), 'updated' => 0, 'users' => array());
    }
    function mk_load($f, $head) {
      if (!is_file($f)) { return mk_default_state(); }
      $raw = file_get_contents($f);
      $raw = substr($raw, strlen($head));
      $d = json_decode($raw, true);
      if (!is_array($d) || !isset($d['users']) || !is_array($d['users'])) { return mk_default_state(); }
      return $d;
    }
    function mk_save($f, $head, $d) {
      file_put_contents($f, $head . json_encode($d), LOCK_EX);
    }
    function mk_fmt($b) {
      return ($b >= 1073741824) ? number_format($b / 1073741824, 2, '.', '') . ' GB' : number_format($b / 1048576, 1, '.', '') . ' MB';
    }
    // riwayat pemakaian: d = harian (62 hari), m = bulanan (24 bulan), h = per jam 0-23 (akumulasi)
    function mk_hist_load($f, $head) {
      if (!is_file($f)) { return array('users' => array()); }
      $raw = substr(file_get_contents($f), strlen($head));
      $d = json_decode($raw, true);
      if (!is_array($d) || !isset($d['users']) || !is_array($d['users'])) { return array('users' => array()); }
      return $d;
    }
    function mk_hist_save($f, $head, $d) {
      file_put_contents($f, $head . json_encode($d), LOCK_EX);
    }
    function mk_hist_add(&$hs, $u, $rx, $tx, $now) {
      if (!isset($hs['users'][$u]) || !isset($hs['users'][$u]['h']) || count($hs['users'][$u]['h']) != 24) {
        $hs['users'][$u] = array('d' => array(), 'm' => array(), 'h' => array_fill(0, 24, array(0, 0)));
      }
      $dk = date('Y-m-d', $now);
      $mk = date('Y-m', $now);
      $hr = (int)date('G', $now);
      if (!isset($hs['users'][$u]['d'][$dk])) {
        $hs['users'][$u]['d'][$dk] = array(0, 0);
        if (count($hs['users'][$u]['d']) > 62) {
          ksort($hs['users'][$u]['d']);
          $hs['users'][$u]['d'] = array_slice($hs['users'][$u]['d'], -62, 62, true);
        }
      }
      if (!isset($hs['users'][$u]['m'][$mk])) {
        $hs['users'][$u]['m'][$mk] = array(0, 0);
        if (count($hs['users'][$u]['m']) > 24) {
          ksort($hs['users'][$u]['m']);
          $hs['users'][$u]['m'] = array_slice($hs['users'][$u]['m'], -24, 24, true);
        }
      }
      $hs['users'][$u]['d'][$dk][0] += $rx;
      $hs['users'][$u]['d'][$dk][1] += $tx;
      $hs['users'][$u]['m'][$mk][0] += $rx;
      $hs['users'][$u]['m'][$mk][1] += $tx;
      $hs['users'][$u]['h'][$hr][0] += $rx;
      $hs['users'][$u]['h'][$hr][1] += $tx;
    }
  }

  $mk_sess = preg_replace('/[^A-Za-z0-9_-]/', '', $session);
  $mk_file = './include/ppp-traffic-' . $mk_sess . '.php';
  $mk_head = "<?php http_response_code(404); exit; ?>\n";
  $mk_hfile = './include/ppp-hist-' . $mk_sess . '.php';

  // ---------- reset data ----------
  if (isset($_POST['mk_reset'])) {
    if (is_file($mk_file)) { @unlink($mk_file); }
    if (is_file($mk_hfile)) { @unlink($mk_hfile); }
    echo "<script>window.location='./?ppp=traffic&session=" . $session . "'</script>";
  }

  $st = mk_load($mk_file, $mk_head);
  $hs = mk_hist_load($mk_hfile, $mk_head);
  $hsDirty = false;

  // ---------- ambil data dari router ----------
  $secrets = $API->comm("/ppp/secret/print");
  if (!is_array($secrets)) { $secrets = array(); }
  $active = $API->comm("/ppp/active/print");
  if (!is_array($active)) { $active = array(); }

  $ifs = $API->comm("/interface/print", array("?type" => "pppoe-in"));
  if (!is_array($ifs) || count($ifs) == 0) {
    $all = $API->comm("/interface/print");
    $ifs = array();
    if (is_array($all)) {
      foreach ($all as $i) {
        if (isset($i['name']) && strpos($i['name'], '<pppoe-') === 0) { $ifs[] = $i; }
      }
    }
  }
  $ifmap = array();
  foreach ($ifs as $i) {
    $n = isset($i['name']) ? $i['name'] : '';
    if (substr($n, 0, 7) == '<pppoe-' && substr($n, -1) == '>') {
      $ifmap[substr($n, 7, -1)] = $i;
    }
  }

  // ---------- perbarui total pemakaian & hitungan logout ----------
  $now = time();
  $onlineNow = array();
  foreach ($active as $a) {
    if (!isset($a['name'])) { continue; }
    if (isset($a['service']) && $a['service'] != 'pppoe') { continue; }
    $u = $a['name'];
    $onlineNow[$u] = $a;

    // rx interface = upload pelanggan, tx interface = download pelanggan
    $rx = isset($ifmap[$u]['rx-byte']) ? (float)$ifmap[$u]['rx-byte'] : null;
    $tx = isset($ifmap[$u]['tx-byte']) ? (float)$ifmap[$u]['tx-byte'] : null;
    $sid = isset($a['session-id']) ? $a['session-id'] : (isset($a['.id']) ? $a['.id'] : '');

    $firstSeen = !isset($st['users'][$u]);
    if (!isset($st['users'][$u])) {
      $st['users'][$u] = array('rx' => 0, 'tx' => 0, 'lrx' => 0, 'ltx' => 0, 'sid' => '', 'out' => 0, 'on' => 0, 'lo' => 0);
    }
    $s = $st['users'][$u];

    $brx = $s['lrx'];
    $btx = $s['ltx'];
    if ($s['on']) {
      $changed = ($s['sid'] !== '' && $sid !== '' && $s['sid'] !== $sid);
      $reset = ($rx !== null && ($rx < $s['lrx'] || $tx < $s['ltx']));
      if ($changed || $reset) {
        // sempat logout dan login lagi di antara dua pembacaan
        $s['out']++;
        $s['lo'] = $now;
        $brx = 0;
        $btx = 0;
      }
    }
    if ($rx !== null && $tx !== null) {
      $dRx = max(0, $rx - $brx);
      $dTx = max(0, $tx - $btx);
      $s['rx'] += $dRx;
      $s['tx'] += $dTx;
      // pemakaian yang sudah ada sebelum pemantauan dimulai tidak dimasukkan ke grafik
      if (!$firstSeen && ($dRx > 0 || $dTx > 0)) {
        mk_hist_add($hs, $u, $dRx, $dTx, $now);
        $hsDirty = true;
      }
      $s['lrx'] = $rx;
      $s['ltx'] = $tx;
    }
    $s['sid'] = $sid;
    $s['on'] = 1;
    $st['users'][$u] = $s;
  }
  foreach ($st['users'] as $u => $s) {
    if (!isset($onlineNow[$u]) && $s['on']) {
      $s['out']++;
      $s['lo'] = $now;
      $s['on'] = 0;
      $s['lrx'] = 0;
      $s['ltx'] = 0;
      $s['sid'] = '';
      $st['users'][$u] = $s;
    }
  }
  $st['updated'] = $now;
  mk_save($mk_file, $mk_head, $st);
  if ($hsDirty) { mk_hist_save($mk_hfile, $mk_head, $hs); }

  // ---------- susun baris tabel ----------
  $rows = array();
  $seen = array();
  foreach ($secrets as $sc) {
    if (!isset($sc['name'])) { continue; }
    $u = $sc['name'];
    $seen[$u] = 1;
    $s = isset($st['users'][$u]) ? $st['users'][$u] : null;
    $dis = (isset($sc['disabled']) && $sc['disabled'] == 'true');
    $isOn = isset($onlineNow[$u]);
    if (isset($sc['last-logged-out']) && $sc['last-logged-out'] != '') {
      $lo = $sc['last-logged-out'];
    } elseif ($s && $s['lo']) {
      $lo = strtolower(date('M/d/Y H:i:s', $s['lo']));
    } else {
      $lo = '-';
    }
    $rows[] = array(
      'name' => $u,
      'profile' => isset($sc['profile']) ? $sc['profile'] : '-',
      'status' => $dis ? 'disabled' : ($isOn ? 'online' : 'offline'),
      'ip' => ($isOn && isset($onlineNow[$u]['address'])) ? $onlineNow[$u]['address'] : '-',
      'uptime' => ($isOn && isset($onlineNow[$u]['uptime'])) ? $onlineNow[$u]['uptime'] : '',
      'rx' => $s ? $s['rx'] : 0,
      'tx' => $s ? $s['tx'] : 0,
      'out' => $s ? $s['out'] : 0,
      'lo' => $lo
    );
  }
  foreach ($st['users'] as $u => $s) {
    if (isset($seen[$u])) { continue; }
    $isOn = isset($onlineNow[$u]);
    $rows[] = array(
      'name' => $u,
      'profile' => '-',
      'status' => $isOn ? 'online' : 'offline',
      'ip' => ($isOn && isset($onlineNow[$u]['address'])) ? $onlineNow[$u]['address'] : '-',
      'uptime' => ($isOn && isset($onlineNow[$u]['uptime'])) ? $onlineNow[$u]['uptime'] : '',
      'rx' => $s['rx'],
      'tx' => $s['tx'],
      'out' => $s['out'],
      'lo' => $s['lo'] ? strtolower(date('M/d/Y H:i:s', $s['lo'])) : '-'
    );
  }
  usort($rows, function ($x, $y) {
    $a = $x['rx'] + $x['tx'];
    $b = $y['rx'] + $y['tx'];
    if ($a == $b) { return strcmp($x['name'], $y['name']); }
    return ($a < $b) ? 1 : -1;
  });

  $sumRx = 0; $sumTx = 0; $sumOut = 0; $cntOn = 0; $maxTot = 0;
  foreach ($rows as $r) {
    $sumRx += $r['rx'];
    $sumTx += $r['tx'];
    $sumOut += $r['out'];
    if ($r['status'] == 'online') { $cntOn++; }
    if ($r['rx'] + $r['tx'] > $maxTot) { $maxTot = $r['rx'] + $r['tx']; }
  }
  $cntAll = count($rows);

  // ---------- data halaman detail pelanggan ----------
  $mk_detail = isset($_GET['user']) ? (string)$_GET['user'] : '';
  if ($mk_detail !== '') {
    $hu = isset($hs['users'][$mk_detail]) ? $hs['users'][$mk_detail] : array('d' => array(), 'm' => array(), 'h' => array());
    if (!isset($hu['d'])) { $hu['d'] = array(); }
    if (!isset($hu['m'])) { $hu['m'] = array(); }
    $drow = null;
    foreach ($rows as $r) {
      if ($r['name'] === $mk_detail) { $drow = $r; break; }
    }
    $k_today = date('Y-m-d');
    $k_month = date('Y-m');
    $td = isset($hu['d'][$k_today]) ? $hu['d'][$k_today] : array(0, 0);
    $tm = isset($hu['m'][$k_month]) ? $hu['m'][$k_month] : array(0, 0);
    $hours = (isset($hu['h']) && is_array($hu['h']) && count($hu['h']) == 24) ? $hu['h'] : array_fill(0, 24, array(0, 0));
    $hourTot = array();
    $peakHour = -1;
    $peakVal = 0;
    for ($i = 0; $i < 24; $i++) {
      $hourTot[$i] = $hours[$i][0] + $hours[$i][1];
      if ($hourTot[$i] > $peakVal) { $peakVal = $hourTot[$i]; $peakHour = $i; }
    }
    $peakDay = '';
    $peakDayVal = 0;
    foreach ($hu['d'] as $k => $v) {
      if ($v[0] + $v[1] > $peakDayVal) { $peakDayVal = $v[0] + $v[1]; $peakDay = $k; }
    }
    $daysData = count($hu['d']);
  }
}
?>

<style>
/* ===== Halaman Traffic PPPoE (scoped ke #mk-traf) ===== */
#main{ background:#0a1124; }
#mk-traf{
  --bg:#0e1730; --surface:#15213f; --surface2:#1b2a4e; --line:#27396a;
  --text:#e9eefb; --muted:#8fa0c6; --on:#2ec4a6; --off:#ff6b6b; --warn:#ffb84d; --info:#4aa8ff; --vio:#9b8cff;
  color:var(--text);
  font-family:"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif;
}
#mk-traf .card{
  background:var(--surface); border:1px solid var(--line); border-radius:14px; box-shadow:none; overflow:hidden; margin-bottom:14px;
}
#mk-traf .card-header{ background:var(--surface2); border-bottom:1px solid var(--line); padding:12px 16px; }
#mk-traf .card-title{ color:var(--text); font-size:15px; font-weight:600; margin:0; }
#mk-traf .card-title .fa{ color:var(--info); margin-right:6px; }
#mk-traf .card-body{ background:transparent; color:var(--text); }

.mkt-stats{ display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:14px; }
.mkt-stat{ border-radius:12px; padding:14px 16px; color:#fff; }
.mkt-stat b{ display:block; font-size:26px; line-height:1.15; font-weight:700; }
.mkt-stat small{ display:block; margin-top:6px; font-size:13px; opacity:.92; }
.mkt-c1{ background:linear-gradient(135deg,#5b49d6,#9b8cff); }
.mkt-c2{ background:linear-gradient(135deg,#128a74,#2ec4a6); }
.mkt-c3{ background:linear-gradient(135deg,#1f5fbf,#2f86e8); }
.mkt-c4{ background:linear-gradient(135deg,#c78a14,#f0a93a); }
.mkt-c5{ background:linear-gradient(135deg,#c2384a,#ff6b6b); }

.mkt-tools{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:12px; }
.mkt-tools .mkt-grow{ flex:1 1 auto; }
.mkt-tools input[type=text], .mkt-tools select{
  background:var(--bg); color:var(--text); border:1px solid var(--line); border-radius:8px; padding:8px 12px; outline:none; height:38px; box-sizing:border-box;
}
.mkt-tools input[type=text]{ min-width:220px; }
.mkt-tools input:focus, .mkt-tools select:focus{ border-color:var(--info); box-shadow:0 0 0 2px rgba(74,168,255,.25); }
.mkt-btn{
  height:38px; padding:0 14px; border-radius:8px; border:1px solid var(--line); background:var(--surface2); color:var(--text);
  cursor:pointer; font-size:13.5px;
}
.mkt-btn:hover{ background:rgba(74,168,255,.15); border-color:var(--info); }
.mkt-btn.mkt-danger:hover{ background:rgba(255,107,107,.15); border-color:var(--off); }
.mkt-auto{ color:var(--muted); font-size:13px; display:flex; align-items:center; gap:6px; }

.mkt-wrap{ overflow:auto; border:1px solid var(--line); border-radius:10px; max-height:560px; }
#mkt-table{ width:100%; border-collapse:collapse; font-size:13.5px; color:var(--text); }
#mkt-table th{
  position:sticky; top:0; z-index:1; background:var(--surface2); color:var(--muted); text-align:left;
  padding:10px 12px; border-bottom:1px solid var(--line); cursor:pointer; white-space:nowrap; user-select:none;
}
#mkt-table th:hover{ color:#fff; }
#mkt-table th .fa{ margin-left:4px; opacity:.5; }
#mkt-table td{ padding:9px 12px; border-bottom:1px solid var(--line); white-space:nowrap; }
#mkt-table tbody tr:hover{ background:rgba(74,168,255,.08); }
#mkt-table td.num{ text-align:right; font-variant-numeric:tabular-nums; }
.mkt-pill{ display:inline-block; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:600; }
.mkt-online{ background:rgba(46,196,166,.15); color:var(--on); border:1px solid rgba(46,196,166,.4); }
.mkt-offline{ background:rgba(255,107,107,.15); color:var(--off); border:1px solid rgba(255,107,107,.4); }
.mkt-disabled{ background:rgba(143,160,198,.15); color:var(--muted); border:1px solid rgba(143,160,198,.4); }
.mkt-up{ color:var(--info); } .mkt-down{ color:var(--on); }
.mkt-sub{ display:block; font-size:11.5px; color:var(--muted); }
.mkt-bar{ width:90px; height:5px; background:var(--bg); border-radius:99px; margin-top:5px; overflow:hidden; }
.mkt-bar i{ display:block; height:100%; background:linear-gradient(90deg,#4aa8ff,#2ec4a6); }
.mkt-note{ margin-top:12px; color:var(--muted); font-size:12.5px; line-height:1.6; }
.mkt-empty{ padding:24px; text-align:center; color:var(--muted); }

.mkt-link{ color:var(--text); text-decoration:none; }
.mkt-link .fa{ color:var(--info); opacity:.6; margin-left:4px; font-size:12px; }
.mkt-link:hover b{ color:var(--info); text-decoration:underline; }
.mkt-link:hover .fa{ opacity:1; }
.mkt-dhead{ display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:14px; }
.mkt-dhead h2{ margin:0; font-size:22px; font-weight:700; color:var(--text); }
.mkt-dhead .mkt-sub{ display:inline; margin-left:6px; }
.mkc{ position:relative; padding-top:22px; }
.mkc-max{ position:absolute; top:0; left:0; font-size:11.5px; color:var(--muted); }
.mkc-plot{
  display:flex; align-items:flex-end; gap:3px; height:180px; border-bottom:1px solid var(--line);
  background:repeating-linear-gradient(to top, transparent 0, transparent 44px, rgba(39,57,106,.7) 44px, rgba(39,57,106,.7) 45px);
}
.mkc-col{ flex:1; min-width:0; height:100%; display:flex; align-items:flex-end; }
.mkc-stack{ width:100%; display:flex; flex-direction:column-reverse; border-radius:3px 3px 0 0; overflow:hidden; cursor:default; }
.mkc-col:hover .mkc-stack{ filter:brightness(1.25); }
.mkc-down{ background:#2ec4a6; } .mkc-up{ background:#4aa8ff; }
.mkc-hr{ background:#4a6fb5; } .mkc-hot{ background:#ffb84d; }
.mkc-x{ display:flex; gap:3px; margin-top:5px; }
.mkc-x span{ flex:1; min-width:0; text-align:center; font-size:11px; color:var(--muted); overflow:hidden; white-space:nowrap; }
.mkc-legend{ display:flex; flex-wrap:wrap; gap:14px; margin-top:10px; font-size:12.5px; color:var(--muted); }
.mkc-legend i{ display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:6px; vertical-align:-1px; }
.mkc-empty{ padding:30px 10px; text-align:center; color:var(--muted); font-size:13.5px; }
.mkc-head{ display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:6px; }
.mkc-head select{
  background:var(--bg); color:var(--text); border:1px solid var(--line); border-radius:8px; padding:6px 10px; outline:none;
}
@media (max-width:1000px){ .mkt-stats{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:480px){ .mkt-tools input[type=text]{ width:100%; } }
</style>

<?php if ($mk_detail === '') { ?>
<div id="mk-traf" class="row">
  <div class="col-12">

    <div class="mkt-stats">
      <div class="mkt-stat mkt-c1"><b><?= $cntAll; ?></b><small><i class="fa fa-users"></i> Total pelanggan</small></div>
      <div class="mkt-stat mkt-c2"><b><?= $cntOn; ?></b><small><i class="fa fa-wifi"></i> Sedang online</small></div>
      <div class="mkt-stat mkt-c3"><b><?= mk_gb($sumTx); ?> GB</b><small><i class="fa fa-arrow-down"></i> Total download</small></div>
      <div class="mkt-stat mkt-c4"><b><?= mk_gb($sumRx); ?> GB</b><small><i class="fa fa-arrow-up"></i> Total upload</small></div>
      <div class="mkt-stat mkt-c5"><b><?= $sumOut; ?>x</b><small><i class="fa fa-chain-broken"></i> Total logout</small></div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="fa fa-exchange"></i> Traffic Pelanggan PPPoE</h3>
      </div>
      <div class="card-body">

        <div class="mkt-tools">
          <input type="text" id="mkt-search" placeholder="Cari nama / profil / IP..." autocomplete="off">
          <select id="mkt-filter">
            <option value="">Semua status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="disabled">Disabled</option>
          </select>
          <span class="mkt-grow"></span>
          <label class="mkt-auto"><input type="checkbox" id="mkt-auto" checked> Auto refresh 60 dtk</label>
          <button type="button" class="mkt-btn" id="mkt-csv"><i class="fa fa-download"></i> Export CSV</button>
          <form method="post" action="" style="margin:0" onsubmit="return confirm('Reset semua data pemakaian dan hitungan logout? Penghitungan akan mulai dari nol.');">
            <button type="submit" name="mk_reset" value="1" class="mkt-btn mkt-danger"><i class="fa fa-trash"></i> Reset data</button>
          </form>
        </div>

        <?php if ($cntAll == 0) { ?>
          <div class="mkt-empty">Belum ada data PPPoE secret / sesi aktif pada router ini.</div>
        <?php } else { ?>
        <div class="mkt-wrap">
          <table id="mkt-table">
            <thead>
              <tr>
                <th data-type="text">Pelanggan <i class="fa fa-sort"></i></th>
                <th data-type="text">Status <i class="fa fa-sort"></i></th>
                <th data-type="text">Profil <i class="fa fa-sort"></i></th>
                <th data-type="text">IP <i class="fa fa-sort"></i></th>
                <th data-type="num">Download <i class="fa fa-sort"></i></th>
                <th data-type="num">Upload <i class="fa fa-sort"></i></th>
                <th data-type="num">Total <i class="fa fa-sort"></i></th>
                <th data-type="num">Logout <i class="fa fa-sort"></i></th>
                <th data-type="text">Terakhir logout <i class="fa fa-sort"></i></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r) {
                $tot = $r['rx'] + $r['tx'];
                $pct = ($maxTot > 0) ? round($tot / $maxTot * 100) : 0;
              ?>
              <tr data-status="<?= htmlspecialchars($r['status']); ?>">
                <td data-v="<?= htmlspecialchars(strtolower($r['name'])); ?>"><a class="mkt-link" onclick="cancelPage()" href="./?ppp=traffic&user=<?= urlencode($r['name']); ?>&session=<?= $session; ?>" title="Lihat grafik pemakaian"><b><?= htmlspecialchars($r['name']); ?></b> <i class="fa fa-line-chart"></i></a></td>
                <td data-v="<?= htmlspecialchars($r['status']); ?>">
                  <span class="mkt-pill mkt-<?= htmlspecialchars($r['status']); ?>"><?= htmlspecialchars($r['status']); ?></span>
                  <?php if ($r['uptime'] != '') { ?><span class="mkt-sub">uptime <?= htmlspecialchars($r['uptime']); ?></span><?php } ?>
                </td>
                <td data-v="<?= htmlspecialchars(strtolower($r['profile'])); ?>"><?= htmlspecialchars($r['profile']); ?></td>
                <td data-v="<?= htmlspecialchars($r['ip']); ?>"><?= htmlspecialchars($r['ip']); ?></td>
                <td class="num mkt-down" data-v="<?= $r['tx']; ?>" title="<?= mk_mb($r['tx']); ?> MB"><?= mk_gb($r['tx']); ?> GB</td>
                <td class="num mkt-up" data-v="<?= $r['rx']; ?>" title="<?= mk_mb($r['rx']); ?> MB"><?= mk_gb($r['rx']); ?> GB</td>
                <td class="num" data-v="<?= $tot; ?>" title="<?= mk_mb($tot); ?> MB">
                  <b><?= mk_gb($tot); ?> GB</b>
                  <div class="mkt-bar"><i style="width:<?= $pct; ?>%"></i></div>
                </td>
                <td class="num" data-v="<?= (int)$r['out']; ?>"><?= (int)$r['out']; ?>x</td>
                <td data-v="<?= htmlspecialchars($r['lo']); ?>"><?= htmlspecialchars($r['lo']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php } ?>

        <div class="mkt-note">
          Dipantau sejak <b><?= date('d M Y H:i', $st['started']); ?></b> &middot; diperbarui <?= date('H:i:s', $st['updated']); ?>.
          Download = data yang dikirim router ke pelanggan, Upload = data yang dikirim pelanggan ke router.
          Total dan hitungan logout dikumpulkan setiap halaman ini dibuka atau direfresh, jadi pemakaian yang terjadi
          saat halaman tidak dibuka hanya terhitung sebagai sisa counter sesi aktif. Angka akan lebih akurat jika
          halaman dibiarkan terbuka dengan auto refresh.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var table = document.getElementById('mkt-table');
  var search = document.getElementById('mkt-search');
  var filter = document.getElementById('mkt-filter');

  function applyFilter() {
    if (!table) return;
    var q = search.value.toLowerCase();
    var f = filter.value;
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
      var okText = rows[i].textContent.toLowerCase().indexOf(q) > -1;
      var okStat = (f === '' || rows[i].getAttribute('data-status') === f);
      rows[i].style.display = (okText && okStat) ? '' : 'none';
    }
  }
  search.addEventListener('input', applyFilter);
  filter.addEventListener('change', applyFilter);

  // urutkan kolom
  if (table) {
    var ths = table.tHead.rows[0].cells;
    var dir = {};
    Array.prototype.forEach.call(ths, function (th, idx) {
      th.addEventListener('click', function () {
        var isNum = th.getAttribute('data-type') === 'num';
        dir[idx] = (dir[idx] === 'asc') ? 'desc' : 'asc';
        var tbody = table.tBodies[0];
        var rows = Array.prototype.slice.call(tbody.rows);
        rows.sort(function (a, b) {
          var x = a.cells[idx].getAttribute('data-v');
          var y = b.cells[idx].getAttribute('data-v');
          var r;
          if (isNum) { r = parseFloat(x) - parseFloat(y); }
          else { r = x < y ? -1 : (x > y ? 1 : 0); }
          return dir[idx] === 'asc' ? r : -r;
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
      });
    });
  }

  // export CSV (baris yang tampil)
  function q(v) { return '"' + String(v).replace(/"/g, '""') + '"'; }
  document.getElementById('mkt-csv').addEventListener('click', function () {
    if (!table) return;
    var gb = function (b) { return (parseFloat(b) / 1073741824).toFixed(3); };
    var out = [['Pelanggan', 'Status', 'Profil', 'IP', 'Download_GB', 'Upload_GB', 'Total_GB', 'Logout', 'Terakhir_logout'].map(q).join(',')];
    var rows = table.tBodies[0].rows;
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].style.display === 'none') continue;
      var c = rows[i].cells;
      out.push([
        c[0].textContent.trim(), c[1].getAttribute('data-v'), c[2].textContent.trim(), c[3].textContent.trim(),
        gb(c[4].getAttribute('data-v')), gb(c[5].getAttribute('data-v')), gb(c[6].getAttribute('data-v')),
        c[7].getAttribute('data-v'), c[8].textContent.trim()
      ].map(q).join(','));
    }
    var blob = new Blob(['\ufeff' + out.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'traffic-pppoe-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  });

  // auto refresh
  var auto = document.getElementById('mkt-auto');
  var timer = null;
  function schedule() {
    clearTimeout(timer);
    if (auto.checked) {
      timer = setTimeout(function () { location.reload(); }, 60000);
    }
  }
  auto.addEventListener('change', schedule);
  schedule();
})();
</script>
<?php } else { ?>

<div id="mk-traf" class="row">
  <div class="col-12">

    <div class="mkt-dhead">
      <a class="mkt-btn" style="display:inline-flex;align-items:center;text-decoration:none" onclick="cancelPage()" href="./?ppp=traffic&session=<?= $session; ?>"><i class="fa fa-arrow-left" style="margin-right:6px"></i> Kembali</a>
      <h2><?= htmlspecialchars($mk_detail); ?></h2>
      <?php if ($drow) { ?>
        <span class="mkt-pill mkt-<?= htmlspecialchars($drow['status']); ?>"><?= htmlspecialchars($drow['status']); ?></span>
        <span style="color:var(--muted);font-size:13px">
          Profil <b style="color:var(--text)"><?= htmlspecialchars($drow['profile']); ?></b>
          &middot; IP <b style="color:var(--text)"><?= htmlspecialchars($drow['ip']); ?></b>
          &middot; Logout <b style="color:var(--text)"><?= (int)$drow['out']; ?>x</b>
          &middot; Terakhir <b style="color:var(--text)"><?= htmlspecialchars($drow['lo']); ?></b>
        </span>
      <?php } ?>
    </div>

    <?php if (!$drow) { ?>
      <div class="card"><div class="card-body"><div class="mkc-empty">Pelanggan "<?= htmlspecialchars($mk_detail); ?>" tidak ditemukan.</div></div></div>
    <?php } else { ?>

    <div class="mkt-stats">
      <div class="mkt-stat mkt-c3"><b><?= mk_fmt($drow['tx']); ?></b><small><i class="fa fa-arrow-down"></i> Total download</small></div>
      <div class="mkt-stat mkt-c2"><b><?= mk_fmt($drow['rx']); ?></b><small><i class="fa fa-arrow-up"></i> Total upload</small></div>
      <div class="mkt-stat mkt-c1"><b><?= mk_fmt($td[0] + $td[1]); ?></b><small><i class="fa fa-calendar-o"></i> Hari ini</small></div>
      <div class="mkt-stat mkt-c4"><b><?= mk_fmt($tm[0] + $tm[1]); ?></b><small><i class="fa fa-calendar"></i> Bulan ini</small></div>
      <div class="mkt-stat mkt-c5">
        <b><?= ($peakHour >= 0) ? sprintf('%02d:00', $peakHour) . '&ndash;' . sprintf('%02d:59', $peakHour) : '-'; ?></b>
        <small><i class="fa fa-clock-o"></i> Jam tersibuk</small>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fa fa-bar-chart"></i> Pemakaian harian</h3></div>
      <div class="card-body">
        <div class="mkc-head">
          <span style="color:var(--muted);font-size:13px">
            <?php if ($peakDay != '') { ?>Hari terpadat: <b style="color:var(--text)"><?= htmlspecialchars($peakDay); ?></b> (<?= mk_fmt($peakDayVal); ?>)<?php } ?>
          </span>
          <select id="mkc-range">
            <option value="7">7 hari terakhir</option>
            <option value="14">14 hari terakhir</option>
            <option value="30" selected>30 hari terakhir</option>
            <option value="60">60 hari terakhir</option>
          </select>
        </div>
        <div class="mkc" id="mkc-daily"></div>
        <div class="mkc-legend"><span><i class="mkc-down"></i>Download</span><span><i class="mkc-up"></i>Upload</span></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fa fa-calendar"></i> Pemakaian bulanan</h3></div>
      <div class="card-body">
        <div class="mkc" id="mkc-month"></div>
        <div class="mkc-legend"><span><i class="mkc-down"></i>Download</span><span><i class="mkc-up"></i>Upload</span></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fa fa-clock-o"></i> Jam sibuk</h3></div>
      <div class="card-body">
        <div class="mkc" id="mkc-hour"></div>
        <div class="mkc-legend"><span><i class="mkc-hr"></i>Total per jam</span><span><i class="mkc-hot"></i>3 jam tersibuk</span></div>
        <div class="mkt-note" id="mkc-hour-note"></div>
      </div>
    </div>

    <div class="mkt-note">
      Data riwayat baru terkumpul sejak pemantauan dimulai (saat ini <b><?= $daysData; ?></b> hari terekam untuk pelanggan ini).
      Harian disimpan 62 hari, bulanan 24 bulan, dan jam sibuk adalah akumulasi seluruh riwayat.
      Pemakaian dicatat pada saat halaman Traffic PPPoE dibuka atau direfresh, jadi pembagian per jam dan per hari bersifat perkiraan.
    </div>

    <?php } ?>
  </div>
</div>

<?php if ($drow) { ?>
<script>
(function () {
  var D = <?= json_encode($hu['d'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var M = <?= json_encode($hu['m'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var H = <?= json_encode($hourTot); ?>;
  var TODAY = "<?= $k_today; ?>";
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var GB = 1073741824, MB = 1048576;

  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function fmt(b) { return b >= GB ? (b / GB).toFixed(2) + ' GB' : (b / MB).toFixed(1) + ' MB'; }
  function val(o, k, i) { return (o && o[k] && o[k][i]) ? o[k][i] : 0; }

  // items: [{label, tip, segs:[[nilai, kelas], ...]}]  (segs pertama berada paling bawah)
  function draw(id, items, every) {
    var el = document.getElementById(id);
    var max = 0, any = false;
    items.forEach(function (it) {
      var t = 0;
      it.segs.forEach(function (s) { t += s[0]; });
      it.total = t;
      if (t > max) { max = t; }
      if (t > 0) { any = true; }
    });
    if (!any) {
      el.innerHTML = '<div class="mkc-empty">Belum ada data pemakaian. Biarkan halaman Traffic PPPoE terbuka agar data mulai terkumpul.</div>';
      return;
    }
    var H_PX = 180;
    var plot = '', xs = '';
    items.forEach(function (it, i) {
      var h = it.total > 0 ? Math.max(2, Math.round(it.total / max * H_PX)) : 0;
      var segs = '';
      it.segs.forEach(function (s) {
        var sh = it.total > 0 ? (s[0] / it.total * h) : 0;
        if (sh > 0) { segs += '<div class="' + s[1] + '" style="height:' + sh.toFixed(1) + 'px"></div>'; }
      });
      plot += '<div class="mkc-col" title="' + it.tip + '"><div class="mkc-stack" style="height:' + h + 'px">' + segs + '</div></div>';
      xs += '<span>' + ((i % every === 0) ? it.label : '&nbsp;') + '</span>';
    });
    el.innerHTML = '<div class="mkc-max">maks ' + fmt(max) + '</div><div class="mkc-plot">' + plot + '</div><div class="mkc-x">' + xs + '</div>';
  }

  // ----- harian -----
  function daily(n) {
    var p = TODAY.split('-');
    var base = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    var items = [];
    for (var i = n - 1; i >= 0; i--) {
      var d = new Date(base.getFullYear(), base.getMonth(), base.getDate() - i);
      var key = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
      var up = val(D, key, 0), down = val(D, key, 1);
      items.push({
        label: String(d.getDate()),
        tip: d.getDate() + ' ' + MON[d.getMonth()] + ' ' + d.getFullYear() + ' — Download ' + fmt(down) + ', Upload ' + fmt(up) + ', Total ' + fmt(up + down),
        segs: [[down, 'mkc-down'], [up, 'mkc-up']]
      });
    }
    draw('mkc-daily', items, n <= 14 ? 1 : (n <= 30 ? 2 : 5));
  }
  var range = document.getElementById('mkc-range');
  range.addEventListener('change', function () { daily(parseInt(range.value, 10)); });
  daily(parseInt(range.value, 10));

  // ----- bulanan (12 bulan terakhir) -----
  (function () {
    var p = TODAY.split('-');
    var y = parseInt(p[0], 10), m = parseInt(p[1], 10) - 1;
    var items = [];
    for (var i = 11; i >= 0; i--) {
      var d = new Date(y, m - i, 1);
      var key = d.getFullYear() + '-' + pad(d.getMonth() + 1);
      var up = val(M, key, 0), down = val(M, key, 1);
      items.push({
        label: MON[d.getMonth()],
        tip: MON[d.getMonth()] + ' ' + d.getFullYear() + ' — Download ' + fmt(down) + ', Upload ' + fmt(up) + ', Total ' + fmt(up + down),
        segs: [[down, 'mkc-down'], [up, 'mkc-up']]
      });
    }
    draw('mkc-month', items, 1);
  })();

  // ----- jam sibuk -----
  (function () {
    var sum = 0, order = [];
    for (var i = 0; i < 24; i++) { sum += H[i]; order.push(i); }
    order.sort(function (a, b) { return H[b] - H[a]; });
    var hot = {};
    if (sum > 0) { order.slice(0, 3).forEach(function (h) { if (H[h] > 0) { hot[h] = true; } }); }
    var items = [];
    for (var h = 0; h < 24; h++) {
      var share = sum > 0 ? (H[h] / sum * 100).toFixed(1) : '0.0';
      items.push({
        label: pad(h),
        tip: pad(h) + ':00–' + pad(h) + ':59 — ' + fmt(H[h]) + ' (' + share + '%)',
        segs: [[H[h], hot[h] ? 'mkc-hot' : 'mkc-hr']]
      });
    }
    draw('mkc-hour', items, 1);
    var note = document.getElementById('mkc-hour-note');
    if (sum > 0) {
      var top = order.slice(0, 3).filter(function (h) { return H[h] > 0; }).map(function (h) {
        return pad(h) + ':00 (' + (H[h] / sum * 100).toFixed(0) + '%)';
      });
      note.innerHTML = 'Paling ramai: <b style="color:var(--text)">' + top.join(', ') + '</b>.';
    }
  })();
})();
</script>
<?php } ?>

<?php } ?>