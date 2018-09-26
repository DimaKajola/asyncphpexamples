<?php
/**
 * немного вспомогательного кода
 */
function execute_task($task_id, $sleep = 2)
{
    echo "Начинаю таск: ${task_id}\n";
    sleep($sleep);
    echo "Такс {$task_id} выполнен за {$sleep} секунд\n";
}

$tasks = [
    "fetch_remote_data",
    "post_async_updates",
    "clear_caches",
    "notify_admin"
];


/**
 * exec()
 */
$time_start = microtime(true);
echo "exec(): начало работы в основном потоке\n";
exec("php child-process.php -s5 > /dev/null &");
sleep(1);
echo "exec(): работа в основном потоке закончена через " . ($time_end - microtime(true)) . "секунд\n";


/**
 *
 * popen()
 *
 */
$time_start = microtime(true);
echo "popen(): начало работы в основном потоке\n";
$stdout = popen('php child-process.php -s5', 'r');
sleep(6);
while (!feof($stdout)) {
    echo(fgets($stdout));
}
pclose($stdout);
echo "popen(): работа в основном потоке закончена через " . (microtime(true) - $time_start) . "секунд\n";


/**
 * proc_open()
 */
$time_start = microtime(true);
echo "proc_open(): начало работы в основном потоке\n";
$descriptorspec = [
    ['pipe', 'r'],
    ['pipe', 'w'],
    ['pipe', 'w'],
];
$pipes   = [];
$process = proc_open('php child-process.php -s5', $descriptorspec, $pipes);
sleep(3);

echo stream_get_contents($pipes[1]);

foreach ($pipes as $pipe) {
    if (is_resource($pipe)) {
        fclose($pipe);
    }
}
proc_close($process);
echo "proc_open(): работа в основном потоке закончена через " . (microtime(true) - $time_start) . "секунд\n";


/**
 *
 * pcntl_fork()
 *
 */

/*********** pcntl_fork(): BASE *********/
$time_start = microtime(true);
echo "pcntl_fork() BASE: начало работы в основном потоке\n";
foreach ($tasks as $task) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit("Ошибка\n");
    } else if ($pid == 0) {
        execute_task($task);
        exit();
    }
}

while(pcntl_waitpid(0, $status) != -1);

$time_end = microtime(true);
echo "pcntl_fork() BASE: работа в основном потоке закончена через " . (microtime(true) - $time_start) . "секунд\n";

/*********** pcntl_fork(): BASE SHARE DATA FAILURE *********/
$data = [];
foreach ($tasks as $task) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit("Ошибка\n");
    } else if ($pid == 0) {
        $data[] = $task;
        exit();
    }
}

while(pcntl_waitpid(0, $status) != -1);
echo 'pcntl_fork() BASE SHARE DATA FAILURE: данные родительского процесса: ' . json_encode($data) . "\n";

/*********** pcntl_fork(): BASE SHARE DATA WITH PARENT SUCCESS *********/
$pids = [];
foreach ($tasks as $task) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit("Ошибка\n");
    } else if ($pid == 0) {
        $result = $task;
        $shm_id = shmop_open(getmypid(), "c", 0644, strlen($result));
        shmop_write($shm_id, $result, 0);
        exit();
    } else {
        $pids[] = $pid;
    }
}

while(pcntl_waitpid(0, $status) != -1);

$data = [];
foreach ($pids as $pid) {
    $shm_id = shmop_open($pid, "a", 0, 0);
    $data[] = shmop_read($shm_id, 0, shmop_size($shm_id));
    shmop_delete($shm_id);
    shmop_close($shm_id);
}
echo 'pcntl_fork() BASE SHARE DATA WITH PARENT SUCCESS: данные родительского процесса: ' . json_encode($data) . "\n";

/*********** pcntl_fork(): BASE SHARE DATA BETWEEN CHILDREN SUCCESS *********/
$pids = [];
foreach ($tasks as $task) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit("Ошибка\n");
    } else if ($pid == 0) {
        $result = $task;

        if ('notify_admin' == $task) {
            while(pcntl_waitpid($pids[2], $status) != -1);
            $shm_id = shmop_open($pids[2], "a", 0, 0);
            $from_parallel_process = shmop_read($shm_id, 0, shmop_size($shm_id));
            $result .= '---' . $from_parallel_process;
            shmop_close($shm_id);
        }

        $shm_id = shmop_open(getmypid(), "c", 0644, strlen($result));
        shmop_write($shm_id, $result, 0);
        exit();
    } else {
        $pids[] = $pid;
    }
}

while(pcntl_waitpid(0, $status) != -1);

$data = [];
foreach ($pids as $pid) {
    $shm_id = shmop_open($pid, "a", 0, 0);
    $data[] = shmop_read($shm_id, 0, shmop_size($shm_id));
    shmop_delete($shm_id);
    shmop_close($shm_id);
}
echo 'pcntl_fork() BASE SHARE DATA BETWEEN CHILDREN SUCCESS: данные родительского процесса: ' . json_encode($data) . "\n";