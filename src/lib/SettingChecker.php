<?php namespace Ipol\DPD;

class SettingChecker
{
    public function getTests()
    {
        return [
            'version' => [
                'title' => 'Версия PHP',
                'desc'  => 'Требуется версия php 5.6 и выше',
                'valid' => '5.6',
            ],

            'memmory' => [
                'title' => 'max_execution_time',
                'desc'  => 'Для работы модуля требуется, чтобы он мог корректно определить максимальное время выполнения скриптов. За это отвечает параметр <a href="https://www.php.net/manual/ru/info.configuration.php#ini.max-execution-time" target="_blank">max_execution_time</a>.',
                'valid' => 'Установлен',
            ],

            'socketTimeout' => [
                'title' => 'default_socket_timeout',
                'desc'  => 'Для работы модуля требуется чтобы значение времени ожидания для потоков, использующих сокеты выделяемой памяти было более 600 секунд. За это отвечает параметр <a href="https://www.php.net/manual/ru/filesystem.configuration.php#ini.default-socket-timeout" target="_blank">default_socket_timeout</a>'
                'valid' => '600',
            ],

            'memoryLimit' => [
                'title' => 'memory_limit',
                'desc'  => 'Для работы модуля требуется 512М выделяемой памяти. За это отвечает параметр <a href="https://www.php.net/manual/ru/ini.core.php#ini.memory-limit" target="_blank">memory_limit</a>'
                'valid' => '512M',
            ],

            'soap' => [
                'title' => 'Поддержка SOAP',
                'desc'  => 'Для работы модуля требуется установленное расширение <a href="https://www.php.net/manual/ru/book.soap.php" target="_blank">PHP-SOAP</a> и доступен класс <a href="https://www.php.net/manual/ru/class.soapclient.php" target="_blank">\SoapClient</a>'
                'valid' => 'Да',
            ],

            'externalLink' => [
                'title' => 'Есть доступ к внешним ресурсам',
                'desc'  => 'Для работы модуля необходимо чтобы запросы на внешние ресурсы не приводили к ошибкам. В случае проблем необходимо обратиться к хостеру.'
                'valid' => 'Да',
            ]
        ];
    }

    public function getVersionValue()        { return phpversion(); }
    public function testVersionValue()       { return version_compare(PHP_VERSION, '5.6') >= 0; }

    public function getMemmoryValue()        { return (int) ini_get('memory_limit'); }
    public function testMemmoryValue()       { return (int) ini_get('memory_limit') >= 512; }

    public function getSocketTimeoutValue()  { return (int) ini_get('default_socket_timeout'); }
    public function testSocketTimeoutValue() { return (int) ini_get('default_socket_timeout') >= 600; }

    public function getSoapValue()           { return class_exists('\SoapClient') && extension_loaded('soap') ? 'Да' : 'Нет'; }
    public function testSoapValue()          { return class_exists('\SoapClient') && extension_loaded('soap'); }

    public function getExternalLinkValue()   { return simplexml_load_string(file_get_contents('https://ws.dpd.ru/services/calculator2?wsdl')) !== false ? 'Да' : 'Нет'; }
    public function testExternalLinkValue()  { return simplexml_load_string(file_get_contents('https://ws.dpd.ru/services/calculator2?wsdl')) !== false; }

    public function getValue($testName)
    {
        $method = 'get'. ucfirst($testName) .'Value';

        return method_exists($this, $method)
            ? $this->$method
            : null
        ;
    }

    public function checkTest($testName)
    {
        $method = 'test'. ucfirst($testName) .'Value';

        return method_exists($this, $method)
            ? $this->$method
            : null
        ;
    }

    public function itOkay()
    {
        $tests = $this->getTests();

        foreach($tests as $test => $data) {
            if ($this->checkTest($test) !== true) {
                return false;
            }
        }

        return true;
    }

    public function print()
    {
        $body = '';

        $tests = $this->getTests();
        foreach($tests as $test => $data) {
            $checked = $this->checkTest($test);
            $value   = $this->getValue($test);

            $body .= ''
                . '<tr>'
                . ' <td class="e">'. $data['title'] .'</td>'
                . ' <td class="v">'. $data['valid'] .'</td>'
                . ' <td class="v"><p class="'. ($checked ? 'green' : 'red') .'">'. $value .'</p></td>'
                . ' <td class="m">'. $data['desc'] .'</td>'
                . '</tr>'
            ;
        }
        
        return str_replace('#BODY#', $body, file_get_contents(__DIR__ .'/../../data/server-settings.tpl.html'));
    }
}