<?php
require __DIR__ . '/vendor/autoload.php';
include('todoist_api.php');

const TODOIST_API_KEY = "REPLACE WITH YOUR API KEY";

const TODOIST_PROJECT = "";

function update_display(Todoist_API $todoist, YDisplay $display): void
{
    // clear all layer on top of layer 0 an 1
    $layer_count = $display->get_layerCount();
    for ($i = 2; $i < $layer_count; $i++) {
        $layer = $display->get_displayLayer($i);
        $layer->clear();
    }
    $todoist->fiter_project(TODOIST_PROJECT);
    $tasks = $todoist->get_active_tasks();

    $layer0 = $display->get_displayLayer(0);
    $layer0->hide();
    $layer0->clear();

    if (sizeof($tasks) == 0) {
        $layer0->selectFont("Medium.yfm");
        $layer0->drawText(64, 32, YDisplayLayer::ALIGN_CENTER, "empty :-)");
    } else {
        $is_first = true;
        $v_pos = 0;
        foreach ($tasks as $t) {
            if ($is_first) {
                $layer0->selectFont("Medium.yfm");
                $layer0->drawText(2, $v_pos, YDisplayLayer::ALIGN_TOP_LEFT, $t['content']);
                //$layer0->drawBar(0, $v_pos + 15, 127, $v_pos + 15);
                $layer0->drawRect(0, 0, 127, 15);
                $is_first = false;
                $v_pos += 18;
            } else {
                $layer0->selectFont("Small.yfm");
                $layer0->drawCircle(3, $v_pos + 4, 2);
                $layer0->drawText(8, $v_pos, YDisplayLayer::ALIGN_TOP_LEFT, $t['content']);
                $v_pos += 10;
            }
            if ($v_pos > 64) {
                break;
            }
        }
    }
    $display->swapLayerContent(0, 1);
    $layer1 = $display->get_displayLayer(1);
    $layer1->unhide();
}

$error = "";
if (YAPI::TestHub("callback", 10, $error) == YAPI::SUCCESS) {
    YAPI::RegisterHub("callback");
    $debug_msg = "\ndebugLogs:\n";

    $todoist = new Todoist_API(TODOIST_API_KEY);

    $display = YDisplay::FirstDisplay();
    while ($display) {
        update_display($todoist, $display);
        $display = $display->nextDisplay();
    }
    print($debug_msg);
    die();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Yoctopuce HTTP Callback</title>
</head>
<body>
<b>This example need to be run by a VirtualHub or a YoctoHub.</b><br/>
<ol>
    <li>Connect to the web interface of the VirtualHub or YoctoHub that will run this script.</li>
    <li>Click on the <em>configure</em> button of the VirtualHub or YoctoHub.</li>
    <li>Click on the <em>edit</em> button of "Callback URL" settings.</li>
    <li>Set the <em>type of Callback</em> to <b>Yocto-API Callback</b>.</li>
    <li>Set the <em>callback URL</em> to
        http://<b><?php print($_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['SCRIPT_NAME']); ?></b>.
    </li>
    <li>Click on the <em>test</em> button.</li>
</ol>
<p>
    Yoctopuce library: <?php print(YAPI::GetAPIVersion()) ?>
</p>

</body>
</html>