<?php
namespace App\Shell\Helper;

use Cake\Console\Helper;
use Cake\Network\Exception\InternalErrorException;

class ColorfulHelper extends Helper
{
    /**
     * Returns a string that defines a message's background color
     *
     * @param string $color Color
     * @return string
     */
    public function setBg($color)
    {
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

        return "\033[" . $bgColors[$color] . "m";
    }

    /**
     * Returns a string that defines a message's foreground color
     *
     * @param string $color Color
     * @return string
     */
    public function setFg($color)
    {
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

        return "\033[" . $fgColors[$color] . "m";
    }

    /**
     * Returns a styled string
     *
     * @param array $args Array of [style type, message]
     * @return string
     */
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
                $styles = $this->setFg('yellow');
                break;
            case 'import-redundant':
                $styles = $this->setFg('yellow');
                break;
            case 'menu-option':
                $styles = $this->setFg('light_green');
                break;
            default:
                throw new InternalErrorException('Unrecognized message style: ' . $type);
        }

        return $styles . $msg . "\033[0m";
    }

    /**
     * Returns a success message
     *
     * @param string $msg Message
     * @return string
     */
    public function success($msg)
    {
        return $this->output(['success', $msg]);
    }

    /**
     * Returns an error message
     *
     * @param string $msg Message
     * @return string
     */
    public function error($msg)
    {
        return $this->output(['error', $msg]);
    }

    /**
     * Returns an import message, styled as "data inserted"
     *
     * @param string $msg Message
     * @return string
     */
    public function importInsert($msg)
    {
        return $this->output(['import-insert', $msg]);
    }

    /**
     * Returns an import message, styled as "data overwritten"
     *
     * @param string $msg Message
     * @return string
     */
    public function importOverwrite($msg)
    {
        return $this->output(['import-overwrite', $msg]);
    }

    /**
     * Returns an import message, styled as "potential overwrite was blocked"
     *
     * @param string $msg Message
     * @return string
     */
    public function importOverwriteBlocked($msg)
    {
        return $this->output(['import-overwrite-blocked', $msg]);
    }

    /**
     * Returns an import message, styled as "redundant"
     *
     * @param string $msg Message
     * @return string
     */
    public function importRedundant($msg)
    {
        return $this->output(['import-redundant', $msg]);
    }

    /**
     * Returns a message, styled as a menu option
     *
     * @param string $msg Message
     * @return string
     */
    public function menuOption($msg)
    {
        return $this->output(['menu-option', $msg]);
    }
}
