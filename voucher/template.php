<?php
// Copy Paste ke template editor [Settings -> Template Editor].

// Format Masa Aktif
if (substr($validity, -1) == "d") {
    $validity = "MASA AKTIF : " . substr($validity, 0, -1) . " HARI";
} else if (substr($validity, -1) == "h") {
    $validity = "MASA AKTIF : " . substr($validity, 0, -1) . " Jam";
}

// Format Time Limit / Durasi
if (substr($timelimit, -1) == "d" & strlen($timelimit) > 3) {
    $timelimit = "Durasi: " . ((substr($timelimit, 0, -1) * 7) + substr($timelimit, 2, 1)) . " HARI";
} else if (substr($timelimit, -1) == "d") {
    $timelimit = "Durasi: " . substr($timelimit, 0, -1) . " HARI";
} else if (substr($timelimit, -1) == "h") {
    $timelimit = "Durasi: " . substr($timelimit, 0, -1) . " Jam";
} else if (substr($timelimit, -1) == "w") {
    $timelimit = "Durasi: " . (substr($timelimit, 0, -1) * 7) . " HARI";
}            

// Pengaturan Warna Premium Berdasarkan Harga ($getsprice)
if ($getsprice == "1000") { $color = "#E91E63"; $accent = "#FCE4EC"; }       
elseif ($getsprice == "2000") { $color = "#9C27B0"; $accent = "#F3E5F5"; } 
elseif ($getsprice == "3000") { $color = "#00ACC1"; $accent = "#E0F7FA"; } 
elseif ($getsprice == "5000") { $color = "#FF6F00"; $accent = "#FFF8E1"; } 
elseif ($getsprice == "10000") { $color = "#F4511E"; $accent = "#FBE9E7"; } 
elseif ($getsprice == "15000") { $color = "#43A047"; $accent = "#E8F5E9"; } 
elseif ($getsprice == "20000") { $color = "#2E7D32"; $accent = "#E8F5E9"; } 
elseif ($getsprice == "30000") { $color = "#C2185B"; $accent = "#FCE4EC"; } 
elseif ($getsprice == "60000") { $color = "#D32F2F"; $accent = "#FFEBEE"; } 
elseif ($getsprice == "70000") { $color = "#B71C1C"; $accent = "#FFEBEE"; } 
else { $color = "#37474F"; $accent = "#ECEFF1"; }                         
?>  

<!--mks-mulai-->    
<style>
.mks-voucher-card {
    display: inline-block;
    border-collapse: collapse;
    width: 195px;
    margin: 2px;
    background: #ffffff;
    border: 1px solid <?php echo $color; ?>;
    border-radius: 5px;
    overflow: hidden;
    font-family: 'Tahoma', Arial, sans-serif;
    -webkit-print-color-adjust: exact;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.mks-price-badge {
    background-color: <?php echo $color; ?>;
    color: #ffffff;
    text-align: right;
    padding: 3px 6px;
    font-weight: bold;
    font-size: 12px;
}
.mks-body {
    padding: 6px 8px;
    text-align: center;
}
.mks-code-box {
    background: <?php echo $accent; ?>;
    border: 1px dashed <?php echo $color; ?>;
    text-align: center;
    padding: 5px;
    margin: 4px 0;
    border-radius: 3px;
}
.mks-footer {
    background-color: <?php echo $color; ?>;
    color: #ffffff;
    font-size: 7.5px;
    font-weight: bold;
    padding: 2.5px 6px;
    text-align: left;
    letter-spacing: 0.5px;
}
</style>

<div class="mks-voucher-card">
    <!-- Header: Logo & Harga -->
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:55%; padding:3px 5px;" valign="middle">
                <img style="max-width:65px; height:auto; display:block;" src="<?php echo $logo;?>" alt="logo">
            </td>
            <td style="width:45%;" class="mks-price-badge" valign="middle">
                <span style="font-size:7.5px; opacity:0.85;"><?= @explode(" ",$price)[0]?></span>
                <span><?= @explode(" ",$price)[1]?></span>
            </td>		
        </tr>
    </table>

    <!-- Body: Kata-kata Khas Voucher Fisik -->
    <div class="mks-body">
        <?php if($v_opsi != 'up'){ ?>
            <div style="font-size:6.5px; font-weight:bold; color:#555; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:1px;">KODE VOUCHER WIFI</div>
            
            <!-- Kotak Kode Voucher -->
            <div class="mks-code-box">
                <span style="font-size:13px; font-weight:bold; color:#1a1a1a; letter-spacing:1.5px;"><?php echo $username;?></span>
            </div>
            
            <div style="font-size:5.5px; color:#777; font-style:italic; margin-bottom:4px;">
                *Gunakan username di atas untuk login
            </div>
        <?php } ?>

        <!-- Info Masa Aktif, Durasi, & Panduan Login -->
        <table style="width:100%; border-collapse:collapse; border-top: 1px solid #eee; padding-top: 3px;">
            <tr>
                <td style="text-align:center; font-size:6px; color:#333333; line-height:1.3; padding-top:3px;">
                    <div style="font-weight:bold; color:<?php echo $color; ?>; margin-bottom:1px;"><?php echo $validity;?> | <?php echo $timelimit;?></div>
                    <div><?php echo $datalimit;?></div>
                    <div style="color:#444; margin-top:3px; font-size:6px; background:#f8f9fa; padding:2px; border-radius:2px; font-weight:bold;">
                        1. Hubungkan WiFi MKS<br>2. Buka Browser & Ketik: mks.net
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer: Customer Service -->
    <div class="mks-footer">
        CS: <b>0822 4072 4572</b>
    </div>
</div>
<!--mks-akhir-->