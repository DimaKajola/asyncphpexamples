<?php
echo "Начало работы в дочернем процессе\n";
$options = getopt('s:');
$sleep = empty($options['s']) ? 5 : $options['s'];
sleep($sleep);
echo "Дочерний процесс закончен через {$sleep} секунд\n";