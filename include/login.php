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


?>

<style>
/* ===== Halaman login Mikhmon (mod by Yudha Saputra) ===== */
body{
  margin:0; min-height:100vh;
  background:
    radial-gradient(900px 500px at 15% 10%, rgba(74,168,255,.18), transparent 60%),
    radial-gradient(700px 480px at 90% 95%, rgba(46,196,166,.16), transparent 60%),
    #0a1124 !important;
  font-family:"Segoe UI",system-ui,-apple-system,Roboto,"Helvetica Neue",Arial,sans-serif;
}
.mk-login{
  min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; box-sizing:border-box;
}
.mk-panel{
  width:100%; max-width:400px; background:#15213f; border:1px solid #27396a;
  border-radius:18px; padding:34px 30px 26px; box-shadow:0 24px 60px rgba(0,0,0,.45);
}
.mk-brand{ text-align:center; margin-bottom:26px; }
.mk-brand img{
  width:64px; height:64px; padding:10px; border-radius:16px;
  background:#1b2a4e; border:1px solid #27396a; box-sizing:border-box;
}
.mk-brand h1{ margin:14px 0 2px; font-size:26px; font-weight:700; color:#e9eefb; letter-spacing:.5px; }
.mk-brand p{ margin:0; font-size:13px; color:#8fa0c6; }
.mk-title{ margin:0 0 16px; font-size:15px; font-weight:600; color:#b8c4e3; text-align:center; }
.mk-field{ position:relative; margin-bottom:14px; }
.mk-field .fa{
  position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#6f84b5; width:16px; text-align:center;
}
.mk-field input{
  width:100%; height:46px; box-sizing:border-box; padding:0 44px 0 42px; font-size:15px;
  color:#e9eefb; background:#0e1730; border:1px solid #27396a; border-radius:10px; outline:none;
  transition:border-color .15s, box-shadow .15s;
}
.mk-field input::placeholder{ color:#6f84b5; }
.mk-field input:focus{ border-color:#4aa8ff; box-shadow:0 0 0 3px rgba(74,168,255,.22); }
.mk-field input:-webkit-autofill{ -webkit-text-fill-color:#e9eefb; -webkit-box-shadow:0 0 0 40px #0e1730 inset; }
.mk-eye{
  position:absolute; right:6px; top:50%; transform:translateY(-50%); width:36px; height:36px;
  border:0; background:transparent; color:#6f84b5; cursor:pointer; border-radius:8px;
}
.mk-eye:hover{ color:#e9eefb; background:#1b2a4e; }
.mk-eye .fa{ position:static; transform:none; }
.mk-submit{
  width:100%; height:48px; margin-top:6px; border:0; border-radius:10px; cursor:pointer;
  font-size:16px; font-weight:700; color:#fff;
  background:linear-gradient(135deg,#1f5fbf,#2f9be8); transition:filter .15s, transform .1s;
}
.mk-submit:hover{ filter:brightness(1.1); }
.mk-submit:active{ transform:translateY(1px); }
.mk-submit:focus-visible, .mk-eye:focus-visible{ outline:2px solid #4aa8ff; outline-offset:2px; }
.mk-error{ margin-top:14px; text-align:center; color:#ff8a8a; font-size:14px; }
.mk-error:empty{ display:none; }
.mk-foot{
  margin-top:24px; padding-top:16px; border-top:1px solid #27396a; text-align:center;
  font-size:12.5px; color:#8fa0c6; line-height:1.6;
}
.mk-foot b{ color:#e9eefb; font-weight:600; }
@media (max-width:420px){ .mk-panel{ padding:28px 20px 22px; } }
@media (prefers-reduced-motion:reduce){ .mk-field input,.mk-submit{ transition:none; } }
</style>

<div class="mk-login">
  <div class="mk-panel">
    <div class="mk-brand">
      <img src="img/favicon.png" alt="MIKHMON Logo">
      <h1>MIKHMON</h1>
      <p>Mod by Yudha Saputra</p>
    </div>

    <h3 class="mk-title"><?= $_please_login ?></h3>

    <form autocomplete="off" action="" method="post">
      <div class="mk-field">
        <i class="fa fa-user"></i>
        <input type="text" name="user" id="_username" placeholder="Username" required="1" autofocus>
      </div>
      <div class="mk-field">
        <i class="fa fa-lock"></i>
        <input type="password" name="pass" id="_password" placeholder="Password" required="1">
        <button type="button" class="mk-eye" id="mkEye" title="Tampilkan password" aria-label="Tampilkan password">
          <i class="fa fa-eye"></i>
        </button>
      </div>
      <input class="mk-submit" type="submit" name="login" value="Login">
      <div class="mk-error"><?= $error; ?></div>
    </form>

    <div class="mk-foot">
      <b>Mikhmon</b> mod by <b>Yudha Saputra</b>
    </div>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('mkEye');
  var pass = document.getElementById('_password');
  if (!btn || !pass) return;
  btn.addEventListener('click', function () {
    var show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    btn.firstElementChild.className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
    btn.title = btn.ariaLabel = show ? 'Sembunyikan password' : 'Tampilkan password';
  });
})();
</script>

</body>
</html>