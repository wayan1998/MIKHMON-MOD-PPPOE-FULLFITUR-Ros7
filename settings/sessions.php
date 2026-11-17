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

// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

// array color
  $color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

  if (isset($_POST['save'])) {

    $suseradm = ($_POST['useradm']);
    $spassadm = encrypt($_POST['passadm']);
    $logobt = ($_POST['logobt']);
    $qrbt = ($_POST['qrbt']);

    $cari = array('1' => "mikhmon<|<$useradm", "mikhmon>|>$passadm");
    $ganti = array('1' => "mikhmon<|<$suseradm", "mikhmon>|>$spassadm");

    for ($i = 1; $i < 3; $i++) {
      $file = file("./include/config.php");
      $content = file_get_contents("./include/config.php");
      $newcontent = str_replace((string)$cari[$i], (string)$ganti[$i], "$content");
      file_put_contents("./include/config.php", "$newcontent");
    }

  
  $gen = '<?php $qrbt="' . $qrbt . '";?>';
          $key = './include/quickbt.php';
          $handle = fopen($key, 'w') or die('Cannot open file:  ' . $key);
          $data = $gen;
          fwrite($handle, $data);
    echo "<script>window.location='./admin.php?id=sessions'</script>";
  }

}
?>
<script>
  function Pass(id){
    var x = document.getElementById(id);
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
</script>

<style>
/* ===== Tampilan baru Admin Settings (scoped ke #mk-admin) ===== */
#main{ background:#0a1124; }
#mk-admin{
  --bg:#0e1730; --surface:#15213f; --surface2:#1b2a4e; --line:#27396a;
  --text:#e9eefb; --muted:#8fa0c6; --accent:#4aa8ff; --danger:#ff6b6b;
  color:var(--text);
  font-family:"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif;
}
#mk-admin .card{
  background:var(--surface); border:1px solid var(--line); border-radius:14px; box-shadow:none; overflow:hidden;
}
#mk-admin .card-header{ background:var(--surface2); border-bottom:1px solid var(--line); padding:12px 16px; }
#mk-admin .card-title{ color:var(--text); font-size:15px; font-weight:600; margin:0; }
#mk-admin .card-title .fa{ color:var(--accent); margin-right:6px; }
#mk-admin .card-body{ background:transparent; color:var(--text); }

/* daftar router */
.mk-router{
  display:flex; align-items:stretch; margin-bottom:12px; background:var(--bg);
  border:1px solid var(--line); border-radius:12px; overflow:hidden; transition:border-color .15s;
}
.mk-router:hover{ border-color:var(--accent); }
.mk-r-ico{
  display:flex; align-items:center; justify-content:center; width:64px; flex:0 0 64px;
  background:var(--surface2); color:var(--accent); font-size:22px; cursor:pointer;
}
.mk-r-body{ padding:12px 16px; line-height:1.7; min-width:0; }
.mk-r-body .mk-name{ font-size:15px; font-weight:600; color:var(--text); }
.mk-r-body .mk-sess{ font-size:13px; color:var(--muted); }
.mk-actions{ margin-top:6px; display:flex; flex-wrap:wrap; gap:8px; }
.mk-actions a, .mk-actions .connect{
  display:inline-block; padding:4px 12px; border-radius:999px; font-size:12.5px;
  background:var(--surface2); border:1px solid var(--line); color:var(--text); text-decoration:none; cursor:pointer;
}
.mk-actions a:hover, .mk-actions .connect:hover{ background:rgba(74,168,255,.15); border-color:var(--accent); color:#fff; }
.mk-actions a.mk-del:hover{ background:rgba(255,107,107,.15); border-color:var(--danger); color:#fff; }

/* form admin */
#mk-admin .table{ color:var(--text); background:transparent; border-color:var(--line); }
#mk-admin .table td{ border-color:var(--line); color:var(--text); vertical-align:middle; }
#mk-admin input[type=text], #mk-admin input[type=password], #mk-admin select.form-control, #mk-admin .form-control{
  background:var(--bg); color:var(--text); border:1px solid var(--line); border-radius:8px; padding:8px 12px; outline:none;
}
#mk-admin .group-item{ background:var(--bg); color:var(--text); border:1px solid var(--line); }
#mk-admin .group-item-l{ border-radius:8px 0 0 8px; }
#mk-admin .group-item-r{ border-radius:0 8px 8px 0; }
#mk-admin input:focus, #mk-admin select:focus{ border-color:var(--accent); box-shadow:0 0 0 2px rgba(74,168,255,.25); }
#mk-admin input[type=submit].group-item{
  background:linear-gradient(135deg,#1f5fbf,#2f9be8); border-color:transparent; color:#fff; font-weight:600;
}
#mk-admin input[type=submit].group-item:hover{ filter:brightness(1.1); }
#mk-admin .group-item-r:hover{ background:var(--surface2); }
#mk-admin #loadV{ color:var(--muted); font-size:13px; margin-top:6px; }
#mk-admin .text-green, #mk-admin .text-blue{ color:#2ec4a6; }

@media (max-width:900px){ #mk-admin .col-6{ width:100%; flex:0 0 100%; max-width:100%; } }
@media (prefers-reduced-motion:reduce){ .mk-router{ transition:none; } }
</style>

<div id="mk-admin" class="row">
	<div class="col-12">
  	<div class="card">
  		<div class="card-header">
  			<h3 class="card-title"><i class="fa fa-gear"></i> <?= $_admin_settings ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload();" class="fa fa-refresh pointer " title="Reload data"></i></h3>
  		</div>
      <div class="card-body">
        <div class="row">
          <div class="col-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-server"></i> <?= $_router_list ?></h3>
              </div>
            <div class="card-body">
            <div class="row">
              <?php
              foreach (file('./include/config.php') as $line) {
                $value = explode("'", $line)[1];
                if ($value == "" || $value == "mikhmon") {
                } else { ?>
                    <div class="col-12">
                        <div class="mk-router">
                          <div class="mk-r-ico connect pointer" id="<?= $value; ?>" title="<?= $_open ?>">
                            <i class="fa fa-server"></i>
                          </div>
                          <div class="mk-r-body">
                            <div class="mk-name"><?= explode('%', $data[$value][4])[1]; ?></div>
                            <div class="mk-sess"><?= $_session_name ?> : <?= $value; ?></div>
                            <div class="mk-actions">
                              <span class="connect pointer" id="<?= $value; ?>"><i class="fa fa-external-link"></i> <?= $_open ?></span>
                              <a href="./admin.php?id=settings&session=<?= $value; ?>"><i class="fa fa-edit"></i> <?= $_edit ?></a>
                              <a class="mk-del" href="javascript:void(0)" onclick="if(confirm('Are you sure to delete data <?= $value;
                              echo " (" . explode('%', $data[$value][4])[1] . ")"; ?>?')){loadpage('./admin.php?id=remove-session&session=<?= $value; ?>')}else{}"><i class="fa fa-remove"></i> <?= $_delete ?></a>
                            </div>
                          </div>
                        </div>
                    </div>
              <?php
            }
          }
          ?>
              </div>
            </div>
          </div>
        </div>
			    <div class="col-6">
          <form autocomplete="off" method="post" action="">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><i class="fa fa-user-circle"></i> <?= $_admin ?></h3>
              </div>
            <div class="card-body">
      <table class="table table-sm">
        <tr>
          <td class="align-middle"><?= $_user_name ?> </td><td><input class="form-control" id="useradm" type="text" size="10" name="useradm" title="User Admin" value="<?= $useradm; ?>" required="1"/></td>
        </tr>
        <tr>
          <td class="align-middle"><?= $_password ?> </td>
          <td>
          <div class="input-group">
          <div class="input-group-11 col-box-10">
                <input class="group-item group-item-l" id="passadm" type="password" size="10" name="passadm" title="Password Admin" value="<?= decrypt($passadm); ?>" required="1"/>
              </div>
                <div class="input-group-1 col-box-2">
                  <div class="group-item group-item-r pd-2p5 text-center align-middle">
                      <input title="Show/Hide Password" type="checkbox" onclick="Pass('passadm')">
                  </div>
                </div>
            </div>
          </td>
        </tr>
        <tr>
          <td class="align-middle"><?= $_quick_print ?> QR</td>
          <td>
            <select class="form-control" name="qrbt">
            <option><?= $qrbt ?></option>
              <option>enable</option>
              <option>disable</option>
            </select>
          </td>
        </tr>
        <tr>
          <td></td><td class="text-right">
              <div class="input-group-4">
                  <input class="group-item group-item-l" type="submit" style="cursor: pointer;" name="save" value="<?= $_save ?>"/>
                </div>
                <div class="input-group-2">
                  <div style="cursor: pointer;" class="group-item group-item-r pd-2p5 text-center" onclick="location.reload();" title="Reload Data"><i class="fa fa-refresh"></i></div>
                </div>
                </div>
          </td>
        </tr>
        
      </table>
      <div id="loadV">v<?= $_SESSION['v']; ?> </div>
      <div><b id="newVer" class="text-green"></b></div>
    </div>
    </div>
    </form>
  </div>
</div>
</div>
</div>
</div>
</div>
<script>
  var _0x7470=["\x68\x6F\x73\x74\x6E\x61\x6D\x65","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x2E","\x73\x70\x6C\x69\x74","\x6D\x69\x6B\x68\x6D\x6F\x6E\x2E\x6F\x6E\x6C\x69\x6E\x65","\x78\x62\x61\x6E\x2E\x78\x79\x7A","\x6C\x6F\x67\x61\x6D\x2E\x69\x64","\x6D\x69\x6E\x69\x73\x2E\x69\x64","\x69\x6E\x64\x65\x78\x4F\x66","\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x68\x74\x6D\x6C","\x23\x6E\x65\x77\x56\x65\x72","\x68\x74\x74\x70\x73\x3A\x2F\x2F\x72\x61\x77\x2E\x67\x69\x74\x68\x75\x62\x75\x73\x65\x72\x63\x6F\x6E\x74\x65\x6E\x74\x2E\x63\x6F\x6D\x2F\x6C\x61\x6B\x73\x61\x31\x39\x2F\x6D\x69\x6B\x68\x6D\x6F\x6E\x76\x33\x2F\x6D\x61\x73\x74\x65\x72\x2F\x76\x65\x72\x73\x6F\x6E\x2E\x74\x78\x74\x3F\x74\x3D","\x72\x61\x6E\x64\x6F\x6D","\x66\x6C\x6F\x6F\x72","\x76","\x76\x65\x72\x73\x69\x6F\x6E","","\x72\x65\x70\x6C\x61\x63\x65","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x6C\x6F\x61\x64\x56","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x20","\x75\x70\x64\x61\x74\x65\x64","\x2D","\x4E\x65\x77\x20\x56\x65\x72\x73\x69\x6F\x6E\x20","\x3C\x62\x72\x3E\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x67\x65\x74\x4A\x53\x4F\x4E"];var hname=window[_0x7470[1]][_0x7470[0]];var dom=hname[_0x7470[3]](_0x7470[2])[1]+ _0x7470[2]+ hname[_0x7470[3]](_0x7470[2])[2];var domArray=[_0x7470[4],_0x7470[5],_0x7470[6],_0x7470[7]];var a=domArray[_0x7470[8]](hname);var b=domArray[_0x7470[8]](dom);if(dom== _0x7470[4]){$(_0x7470[11])[_0x7470[10]](_0x7470[9])}else {if(a> 0|| b> 0){}else {$[_0x7470[27]](_0x7470[12]+ (Math[_0x7470[14]]((Math[_0x7470[13]]()* 999999999)+ 1))* 128,function(_0xc1b4x6){getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewD= (_0xc1b4x6[_0x7470[23]])[_0x7470[3]](_0x7470[22])[0];newD= parseInt((getNewD)[_0x7470[3]](_0x7470[24])[2]+ (getNewD)[_0x7470[3]](_0x7470[24])[0]+ (getNewD)[_0x7470[3]](_0x7470[24])[1]);var _0xc1b4xc=parseInt((_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[2]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[0]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1][_0x7470[3]](_0x7470[24]))[1]);var _0xc1b4xd=(newD- _0xc1b4xc);if(_0xc1b4xb> 0|| _0xc1b4xd> 0){$(_0x7470[11])[_0x7470[10]](_0x7470[25]+ _0xc1b4x6[_0x7470[16]]+ _0x7470[22]+ _0xc1b4x6[_0x7470[23]]+ _0x7470[26])}})}}
</script>