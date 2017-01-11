<?php
namespace App\Shell\Helper;

use Cake\Console\Helper;

class ColorfulHelper extends Helper
{
    public function setBg($color) {
        $bgColors = [
            'black' => '40',
            'red' => '41',
            'green' => '42',
            'yellow' => '43',
            'blue' => '44',
            'magenta' => '45',
            'cyan' => '46',
            'light_gray' => '47'
        ];
        return "\033[".$bgColors[$color]."m";
    }

    public function setFg($color) {
        $fgColors = [
            'black' => '0;30',
            'dark_gray' => '1;30',
            'blue' => '0;34',
            'light_blue' => '1;34',
            'green' => '0;32',
            'light_green' => '1;32',
            'cyan' => '0;36',
            'light_cyan' => '1;36',
            'red' => '0;31',
            'light_red' => '1;31',
            'purple' => '0;35',
            'light_purple' => '1;35',
            'brown' => '0;33',
            'yellow' => '1;33',
            'light_gray' => '0;37',
            'white' => '1;37'
        ];
        return "\033[".$fgColors[$color]."m";
    }

    public function output($args)
    {
        list($type, $msg) = $args;
        switch ($type) {
            case 'error':
                $styles = $this->setBg('red');
                break;
            case 'success':
                $styles = $this->setBg('green');
                break;
            case 'import-insert':
                $styles = $this->setFg('green');
                break;
            case 'import-overwrite':
                $styles = $this->setFg('light_green');
                break;
            case 'import-overwrite-blocked':
                $styles = $this->setBg('yellow');
                break;
            case 'import-redundant':
                $styles = $this->setBg('yellow');
                break;
            default:
                throw new InternalErrorException('Unrecognized message style: '.$type);
        }
        return $styles.$msg."\033[0m";
    }

    public function success($msg)
    {
        return $this->output(['success', $msg]);
    }

    public function error($msg)
    {
        return $this->output(['error', $msg]);
    }

    public function importInsert($msg)
    {
        return $this->output(['import-insert', $msg]);
    }

    public function importOverwrite($msg)
    {
        return $this->output(['import-overwrite', $msg]);
    }

    public function importOverwriteBlocked($msg)
    {
        return $this->output(['import-overwrite-blocked', $msg]);
    }

    public function importRedundant($msg)
    {
        return $this->output(['import-redundant', $msg]);
    }
}
