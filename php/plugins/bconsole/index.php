<?php
// 因為 import foo 要導入 foo.py 必須放到 Lib 目錄下, 因此將 local_storage.py 放到 Lib 必須 import local_storage.storage 來呼叫 storage
$pyprogram = <<<EOF
def 執行函式(次數, 字串):
    for 索引 in range(次數):
        print(字串)

執行函式(5, "Brython 可以執行")
EOF;

function bconsoleMain($input){
global $pyprogram;
if($input == NULL) $input = $pyprogram;
$output=<<<EOF
<script src="jscript/brython/brython.js"></script>
<script>
window.onload = function(){
    brython(1);
}
</script>
<script type="text/python">
import sys
import time
import dis

if sys.has_local_storage:
    from local_storage import storage
else:
    storage = False

def reset_src():
    if storage and "py_src" in storage:
       editor.setValue(storage["py_src"])

def write(data):
    doc["console"].value += str(data)

#sys.stdout = object()    #not needed when importing sys via src/Lib/sys.py
sys.stdout.write = write

def to_str(xx):
    return str(xx)

doc['version'].text = '.'.join(map(to_str,sys.version_info))

class cons_out:

    def __init__(self,target):
        self.target = doc[target]
    def write(self,data):
        self.target.value += str(data)

sys.stdout = cons_out("console")
sys.stderr = cons_out("console")

output = ''

def show_console():
    doc["console"].value = output
    doc["console"].cols = 60

def clear_text():
    log(" event clear")
    doc['console'].value=''
    #doc['src'].value=''

def run():
    global output
    doc["console"].value=''
    doc["console"].cols = 60
    src = doc["src"].value
    if storage:
        storage["py_src"]=src
    t0 = time.time()
    exec(src)
    output = doc["console"].value
    print('<done in %s ms>' %(time.time()-t0))

def show_js():
    src = doc["src"].value
    doc["console"].cols = 90
    doc["console"].value = dis.dis(src)
</script>
<table width=80%>
<tr><td style="text-align:center"><b>Python</b>
</td>
<td>&nbsp;</td>
<th><button onClick="show_console()">Console</button></th>
<th><button onClick="show_js()">Javascript</button></th>
</tr>
<tr>
EOF;
$output .= "<td colspan><textarea id=\"src\" cols=\"60\" rows=\"20\">".$input."</textarea></td>";
$output .= <<<EOF
<td><button onClick="run()">run</button></td>
<td><button onClick="clear_text()">clear</button></td>
<td colspan=2><textarea id="console" cols=70 rows=20></textarea></td>
</tr>
<tr><td colspan=2>
<p>Brython version <span id="version"></span>
</table>

EOF;
return $output;
}