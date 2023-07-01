<?

$options = '';
foreach (scandir('./skin') as $name) {
    if ($name == '.' || $name == '..') continue;
    if (!isset($_GET['name'])) $_GET['name'] = $name;
    $select = $name===$_GET['name']?' selected':'';
    $options .= "<option{$select}>{$name}</option>";
}

$filename = $_GET['name'];

$data = file_get_contents("skin/{$filename}");

function getArrayTheme ($data)
{
    $format = "a4head/Iversion";
    $r = unpack($format, $data);

    $format = "Iparams/Ibuttons/Ibitmaps";
    $pos = unpack($format, $data, 8);

    if ($r['head'] != 'SKIN') return false;

    $format = "sright/sleft/sbottom/stop";
    $r += ['margin' => unpack($format, $data, $pos['params'] + 4)];

    $format = "H6inner/xx/H6outer/xx/H6frame";
    $r += ['active' => unpack($format, $data, $pos['params'] + 12)];

    $format = "H6inner/xx/H6outer/xx/H6frame";
    $r += ['inactive' => unpack($format, $data, $pos['params'] + 24)];

    $format = "Isize/H6taskbar/xx/H6taskbar_text/xx/H6work_dark/xx/H6work_light/xx/H6window_title/xx/H6work/xx/H6work_button/xx/H6work_button_text/xx/H6work_text/xx/H6work_graph";
    $r += ['dtp' => unpack($format, $data, $pos['params'] + 36)];

    $position = $pos['buttons'];
    $button = [];
    while (true) {
        if (!unpack('Lchk', $data, $position)['chk']) break;
        $format = "Ltype/sleft/stop/swidth/sheight";
        $button[] = unpack($format, $data, $position);
        $position += 12;
    }

    $position = $pos['bitmaps'];
    $bitmap = [];

    while (true) {
        if (!unpack('Lchk', $data, $position)['chk']) break;
        $format = "Skind/Stype";
        $bit = unpack($format, $data, $position);

        $format = "Lposition";
        $posbm = unpack($format, $data, $position + 4);

        $format = "Lwidth/Lheight";
        $bit += unpack($format, $data, $posbm['position']);

        $size = $bit['width'] * $bit['height'] * 3;
        $format = "C{$size}";
        $bits = unpack($format, $data, $posbm['position'] + 8);

        $w = $bit['width'];
        $h = $bit['height'];
        $img = imagecreatetruecolor($w, $h);

        // Iterate over all pixels
        $i = 1;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $bb = $bits[$i++];
                $gg = $bits[$i++];
                $rr = $bits[$i++];
                imagesetpixel($img, $x, $y, ($rr << 16 | $gg << 8 | $bb));
            }
        }
        ob_start();
        imagepng($img);
        $bit['base64'] = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($img);
        $bitmap[] = $bit;
        $position += 8;
    }
    return $r + ['bitmap' => $bitmap] + ['button' => $button];
}

$array = getArrayTheme($data);
?>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }
        .window {
            border: 1px solid;
            border-top: none;
            width: 300px;
            display: none;
            font-family: monospace;
            font-size: 14px;
            user-select: none;
            margin-bottom: 10px;
        }
        .window .inner {
            border: 1px solid;
            height: 250px;
            margin: 4px;
            margin-top: 0;
        }
        .window .panel {
            position: relative;
            background-repeat: repeat-x;
        }
        .window .panel .left {
            position: absolute;
        }
        .window .panel .button {
            position: absolute;
        }
        .window .panel .close {
            position: absolute;
            border: 1px dotted red;
        }
        .window .panel .min {
            position: absolute;
            border: 1px dotted red;
        }

        .window .panel .title {
            position: absolute;
            left: 5px;
            top: 4px;
            font-weight: bold;
        }
        .window button {
            background: white;
            color: #000;
            padding: 3px;
            margin: 5px;
        }

        .block {
            display: flex;
            align-items: center;
            justify-content: space-around;
            max-width: 800px;
            margin: auto;
            flex-wrap: wrap;
        }
        select {
            padding: 5px;
        }
    </style>
</head>
<body>
<center>
    <select onchange="location.href='?name='+this.value;">
        <?php
        echo $options;
        ?>
    </select>
</center>
<br>
<div class="block">
    <div class="window active">
        <div class="panel">
            <div class="left"></div>
            <div class="title">Активное окно</div>
            <div class="button"></div>
            <div class="close"></div>
            <div class="min"></div>
        </div>
        <div class="inner">
            <button>Text on button</button>
            <br>
            <span>Text in window</span>
        </div>
    </div>
    <div class="window inactive">
        <div class="panel">
            <div class="left"></div>
            <div class="title">Неактивное окно</div>
            <div class="button"></div>
            <div class="close"></div>
            <div class="min"></div>
        </div>
        <div class="inner">
            <button>Text on button</button>
            <br>
            <span>Text in window</span>
        </div>
    </div>
</div>

<script>var themeStructure = <?=json_encode($array)?></script>
<script>
    function htmlColor(color) {
        return '#'+color.substr(4,2)+color.substr(2,2)+color.substr(0,2)
    }
    function loadStructureTheme(structure) {
        var active = document.querySelector('.window.active');
        var activeInner = active.querySelector('.inner');
        active.style.borderColor = htmlColor(structure.active.outer);
        active.style.backgroundColor = htmlColor(structure.active.frame);
        activeInner.style.borderColor = htmlColor(structure.active.inner);
        activeInner.style.backgroundColor = htmlColor(structure.dtp.work);

        var inactive = document.querySelector('.window.inactive');
        var inactiveInner = inactive.querySelector('.inner');
        inactive.style.borderColor = htmlColor(structure.inactive.outer);
        inactive.style.backgroundColor = htmlColor(structure.inactive.frame);
        inactiveInner.style.borderColor = htmlColor(structure.inactive.inner);
        inactiveInner.style.backgroundColor = htmlColor(structure.dtp.work);

        var title_list = document.querySelectorAll('.title');
        for (let i=0; i<title_list.length; i++) {
            let item = title_list[i];
            item.style.color = htmlColor(structure.dtp.window_title);
        }

        for (let i=0; i<structure.bitmap.length; i++) {
            let item = structure.bitmap[i];
            let element = item.type?active:inactive;
            let panel = element.querySelector('.panel');
            let button = element.querySelector('.button');
            let left = element.querySelector('.left');
            let title = element.querySelector('.title');
            if (item.kind === 3) {
                panel.style.backgroundImage = "url(data:image/png;base64,"+item.base64+')';
                panel.style.height = item.height+'px';
            }
            else if (item.kind === 2) {
                button.style.backgroundImage = "url(data:image/png;base64,"+item.base64+')';
                button.style.height = item.height+'px';
                button.style.width = item.width+'px';
                button.style.right = '-1px';
            }
            else if (item.kind === 1) {
                left.style.backgroundImage = "url(data:image/png;base64,"+item.base64+')';
                left.style.height = item.height+'px';
                left.style.width = item.width+'px';
                left.style.left = '-1px';
                //title.style.left = (item.width-1)+'px';
            }

        }

        for (let i=0; i<structure.button.length; i++) {
            let item = structure.button[i];
            if (item.type === 1) {
                let inactive_min = inactive.querySelector('.min');
                let active_min = active.querySelector('.min');

                active_min.style.top = inactive_min.style.top = item.top+'px';
                active_min.style.right = inactive_min.style.right = -structure.margin.left+structure.margin.right+item.left+'px';
                active_min.style.height = inactive_min.style.height = item.height+'px';
                active_min.style.width = inactive_min.style.width = item.width+'px';
            } else {
                let inactive_close = inactive.querySelector('.close');
                let active_close = active.querySelector('.close');

                active_close.style.top = inactive_close.style.top = item.top+'px';
                active_close.style.right = inactive_close.style.right = -structure.margin.left+structure.margin.right+item.left+'px';
                active_close.style.height = inactive_close.style.height = item.height+'px';
                active_close.style.width = inactive_close.style.width = item.width+'px';
            }

        }

        var guibutton = document.querySelectorAll('.window button');
        for (let i=0; i<guibutton.length; i++) {
            let item = guibutton[i];
            item.style.backgroundColor = htmlColor(structure.dtp.work_button);
            item.style.color = htmlColor(structure.dtp.work_button_text);
        }

        var guitext = document.querySelectorAll('.window span');
        for (let i=0; i<guitext.length; i++) {
            let item = guitext[i];
            item.style.color = htmlColor(structure.dtp.work_text);
        }

    }

    window.onload = function() {
        loadStructureTheme(themeStructure);
        let list = document.getElementsByClassName('window');
        for (let i=0; i<list.length; i++) {
            list[i].style.display = 'block';
        }
    }


</script>
</body></html>