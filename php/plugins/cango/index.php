<?php
function cangoMain(){
$output = <<<EOF
<script src="jscript/brython/brython.js"></script>
<script type="text/python">
import html
import svg
import math

ray = 100
values = [20,10,30,15,25]

colors = ["C8E0A2","A6BED1","E4CC85","D7D7D7","90AF97","698EA8",
        "BFA166","A8ADB0","FF6600"]

panel = doc["panel"]
legend = None

title = svg.text('',x=150,y=25,
    font_size=22,text_anchor="middle",
    style={"stroke":"black"})
panel <= title

paths = {}

def pie_chart():
    global paths,legend
    # clear SVG doc
    for child in panel: # iteration on child nodes
        if child != title:
            panel.remove(child)

    # zone for legend
    legend = svg.text('',x=350,y=150,
        font_size=20,text_anchor="middle",
        style={"stroke":"black"})
    panel <= legend

    set_title()
        
    paths = {}
    data = {}
    for i,cell in enumerate(cells):
        data['Item %s' %(i+1)]=float(cell.text)
    style={"fill-opacity": 1,"stroke":"black","stroke-width": 1}
    width = 3.8*ray
    height = 2.2*ray
    x_center = 150
    y_center = 160
    x = x_center
    y = y_center-ray
    total = sum(data.values())
    items = list(data.items())
    cumul = 0
    for i,(key,value) in enumerate(items):
        angle1 = 2*math.pi*cumul
        cumul += float(value)/total
        angle = 2*math.pi*cumul
        x_end = x_center + ray*math.cos((math.pi/2)-angle)
        y_end = y_center - ray*math.sin((math.pi/2)-angle)
        path = "M%s,%s " %(x_center,y_center)
        path += "L%s,%s " %(int(x),int(y))
        if angle-angle1 <= math.pi:
            path += "A%s,%s 0 0,1 " %(ray,ray)
        else:
            path += "A%s,%s 0 1,1 " %(ray,ray)
        path += "%s,%s z" %(int(x_end),int(y_end))
        x,y = x_end,y_end
        color = colors[i % len(colors)]
        style["fill"]='#'+color
        path = svg.path(d=path,style=style,
            onMouseOver="show_legend('%s')" %key,
            onMouseOut="hide_legend()")
        panel <= path
        paths[key]=path

def set_title():
    title.text = title_input.value
    
def show_legend(key):
    legend.text = key

def hide_legend():
    legend.text = ''

def change(rank,offset):
    x = int(cells[int(rank)].text)
    cells[int(rank)].text = x+int(offset)
    pie_chart()

nb_cols = 2
nb_lines = 5

t = html.TABLE()
tb = html.TBODY()
cells = []

title_input = html.INPUT(value='Pie Chart',onchange="set_title()")
tb <= html.TD('Title')+html.TD(title_input,colspan=3)

for i in range(nb_lines):
    row = html.TR()
    row <= html.TD('Item %s' %(i+1))
    row <= html.TD(html.BUTTON('<',onclick="change(%s,-1)" %i))
    cell = html.SPAN(values[i])
    row <= html.TD(cell)
    row <= html.TD(html.BUTTON('>',onclick="change(%s,1)" %i))
    cells.append(cell)
    tb <= row
t <= tb
doc['data'] <= t

pie_chart()
</script>

</head>
<body onLoad="brython(1)">
<h1>SVG pie chart</h1>
<p>

<table>
<tr>
<td id="data"></td>
<td>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
    width="400" height="300" style="border-style:solid;border-width:1;border-color:#000;">
  <g id="panel">
  </g>
</svg>
</td>
</tr>
</table>
<script>
window.onload=function(){
brython();
}
</script>
<canvas id="cvs" width="640" height="400"></canvas>
EOF;
return $output;
}