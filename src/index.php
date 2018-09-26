<?php
/**
 * fastcgi_finish_request()
 */
echo "<br/>fastcgi_finish_request(): начало работы в основном потоке<br/>";
fastcgi_finish_request();
sleep(5);
file_put_contents('log.txt', 'Хрен ты это в браузере увидешь');

/**
 *
 * pcntl_fork() !!!!!!! DOESN'T WORK THROUGH CGI
 *
 */
$tasks = [
    "fetch_remote_data",
    "post_async_updates",
    "clear_caches",
    "notify_admin",
];

foreach ($tasks as $task) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        exit("Ошибка...\n");
    } else if ($pid == 0) {
        echo "Выполняю таск: ${$task}\n";
        exit();
    }
}

while(pcntl_waitpid(0, $status) != -1);
echo "Все таски закончены.\n";

/**
 *
 * pcntl_fork() in popen()
 *
 */
echo "<br/>pcntl_fork() in popen(): начало работы<br/>";
$stdout = popen('php /src/cli.php', 'r');
while (!feof($stdout)) {
    echo fgets($stdout);
}
pclose($stdout);
echo "<br/>pcntl_fork() in popen(): конец работы<br/>";