<?php
require __DIR__ . '/vendor/autoload.php';
include('todoist_api.php');

const TODOIST_API_KEY = "aaaa";
const TODOIST_PROJECT = "test";

class AppContext
{
    private $serial;
    private $lastUptime;
    private $taskPos;

    static function Get($serial): AppContext
    {
        $context = new AppContext($serial);
        $filename = $context->_getFilename();
        if (file_exists($filename)) {
            $context->loadFromFile($context->_getFilename());
        }
        return $context;
    }

    public function __construct(string $serial)
    {
        $this->serial = $serial;
    }

    public function save()
    {
        $var = ['serial' => $this->serial,
            'uptime' => $this->getLastUptime(),
            'pos' => $this->getTaskPos()
        ];
        $json = json_encode($var);
        file_put_contents($this->_getFilename(), $json);
    }

    /**
     * @return mixed
     */
    public function getLastUptime()
    {
        return $this->lastUptime;
    }

    /**
     * @param mixed $lastUptime
     */
    public function setLastUptime($lastUptime): void
    {
        $this->lastUptime = $lastUptime;
    }

    /**
     * @return mixed
     */
    public function getTaskPos()
    {
        return $this->taskPos;
    }

    /**
     * @param mixed $taskPos
     */
    public function setTaskPos($taskPos): void
    {
        $this->taskPos = $taskPos;
    }

    private function _getFilename(): string
    {
        if (!is_dir('contexts')) {
            mkdir('contexts');
        }
        return sprintf("contexts/%s.json", $this->serial);
    }

    private function loadFromFile(string $filename)
    {
        $obj = json_decode(file_get_contents($filename), true);
        $this->lastUptime = $obj['uptime'];
        $this->taskPos = $obj['pos'];
    }
}


function testForButtonPress(YAnButton $button, $last_uptime): bool
{
    return $button->lastTimePressed() > $last_uptime;
}

/**
 * @param YAnButton $button
 * @param YModule $module
 * @param $last_uptime
 * @return bool
 */
function testForLongButtonPress(YAnButton $button, YModule $module, $last_uptime): bool
{
    $res = false;
    $long_press = 2000;
    $rt = $button->lastTimeReleased();
    $pt = $button->lastTimePressed();
    $upTime = $module->upTime();
    if ($rt > $pt && ($rt - $pt) > $long_press && ($rt > $last_uptime)) {
        //long press happened still last callback
        print("long press\n");
        $res = true;
    } else if ($rt < $pt && ($pt + $long_press) < $upTime) {
        //still pressed
        print("Still pressed\n");
        $res = true;
    }
    return $res;
}



function draw_task(YDisplayLayer $layer0, $cur_task)
{

    $layer0->selectFont("Medium.yfm");
    $layer0->drawText(2, 0, YDisplayLayer::ALIGN_TOP_LEFT, $cur_task['content']);
    $layer0->drawBar(0, 15, 127, 15);

    $layer0->selectFont("Small.yfm");
    $margin = 20;
    if ($cur_task['due']) {
        $layer0->drawText(2, 20, YDisplayLayer::ALIGN_TOP_LEFT, sprintf("for %s", $cur_task['due']['string']));
        $margin += 10;
    }
    if ($cur_task['description'] != '') {
        $layer0->drawText(2, $margin, YDisplayLayer::ALIGN_TOP_LEFT, $cur_task['description']);
        //$layer0->setConsoleMargins(2, $margin, 2, 0);
        //$layer0->consoleOut($cur_task['description']);
    }
}


/**
 * @param YDisplayLayer $layer0
 * @param string $text
 * @return int
 */
function draw_message(YDisplayLayer $layer0, string $text): int
{
    $layer0->selectFont("Medium.yfm");
    return $layer0->drawText(64, 32, YDisplayLayer::ALIGN_CENTER, $text);
}


function update_display(Todoist_API $todoist, YDisplay $display)
{
    $context = AppContext::Get($display->get_serialNumber());
    // clear all layer on top of layer 0 an 1
    $layer_count = $display->get_layerCount();
    for ($i = 2; $i < $layer_count; $i++) {
        $layer = $display->get_displayLayer($i);
        $layer->clear();
    }

    $layer0 = $display->get_displayLayer(0);
    $layer0->hide();
    $layer0->clear();
    $h = $display->get_displayHeight();
    $w = $display->get_displayWidth();
    $layer0->selectGrayPen(0);
    $layer0->drawBar(0, 0, $w - 1, $h - 1);
    $layer0->selectGrayPen(255);

    $module = $display->get_module();
    $next_button = YAnButton::FindAnButton("{$module->get_serialNumber()}.anButton1");
    $done_button = YAnButton::FindAnButton("{$module->get_serialNumber()}.anButton2");


    $last_uptime = $context->getLastUptime();
    $cur_task = $context->getTaskPos();
    if (testForButtonPress($next_button, $last_uptime)) {
        if (testForButtonPress($next_button, $last_uptime)) {
            $cur_task++;
        }
        draw_message($layer0, "Loading...");
        print("\n@YoctoAPI:%\n");
    } else {

        $todoist->fiter_project(TODOIST_PROJECT);
        $tasks = $todoist->get_active_tasks();
        $nb_tasks = sizeof($tasks);
        if ($cur_task < 0 || $cur_task >= $nb_tasks) {
            $cur_task = 0;
        }
        $task_done = testForLongButtonPress($done_button, $module, $last_uptime);
        if ($task_done) {
            $todoist->makeTaskDone($tasks[$cur_task]['id']);
            draw_message($layer0, "Task Done");
            print("\n@YoctoAPI:%\n");
        } else {
            if (sizeof($tasks) > 0) {
                $layer0->drawText($w - 2, 0, YDisplayLayer::ALIGN_TOP_RIGHT, sprintf("%d/%d", $cur_task + 1, $nb_tasks));
                draw_task($layer0, $tasks[(int)$cur_task]);
            } else {
                draw_message($layer0, "No tasks");
            }
        }
    }
    $context->setTaskPos($cur_task);
    $context->setLastUptime($module->get_upTime());
    $context->save();
    $display->swapLayerContent(0, 1);
    $layer1 = $display->get_displayLayer(1);
    $layer1->unhide();
}

$error = "";
if (YAPI::TestHub("callback", 10, $error) == YAPI::SUCCESS) {
    YAPI::RegisterHub("callback");
    $debug_msg = "\ndebugLogs:\n";

    $toodist = new Todoist_API(TODOIST_API_KEY);

    $display = YDisplay::FirstDisplay();
    while ($display) {
        update_display($toodist, $display);
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