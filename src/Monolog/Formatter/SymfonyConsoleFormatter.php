<?php
/**
 * Monolog Console Formatter
 *
 * Monolog Console Formatter
 *
 * PHP version 5.4
 *
 * LICENSE: License.txt New BSD License
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Reliv\Deploy\Monolog\Formatter;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

/**
 * Monolog Console Formatter
 *
 * Monolog Console Formatter.  Replaces Symfony's standard formatter to allow the correct console output
 * coloring for messages.  This allows the formatter to work in a more consistent way with Symfony Console logger
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class SymfonyConsoleFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context% %extra%\n";

    const INFO = 'info';
    const ERROR = 'error';
    const NOTICE = 'comment';
    const DEBUG  = null;

    /**
     * @var array Format Level Map. Used to match Monologs error level to color formatting.
     */
    private $formatLevelMap = array(
        Logger::EMERGENCY => self::ERROR,
        Logger::ALERT => self::ERROR,
        Logger::CRITICAL => self::ERROR,
        Logger::ERROR => self::ERROR,
        Logger::WARNING => self::NOTICE,
        Logger::NOTICE => self::NOTICE,
        Logger::INFO => self::INFO,
        Logger::DEBUG => self::DEBUG,
    );

    /**
     * Constructor
     *
     * @param string $format                     The format of the message
     * @param string $dateFormat                 The format of the timestamp: one supported by DateTime::format
     * @param bool   $allowInlineLineBreaks      Whether to allow inline line breaks in log entries
     * @param bool   $ignoreEmptyContextAndExtra Ignore the Context and Extra formatting tags.
     * @param array  $formatLevelMap             Custom Format Level mapping
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        $format = null,
        $dateFormat = null,
        $allowInlineLineBreaks = false,
        $ignoreEmptyContextAndExtra = true,
        Array $formatLevelMap = array()
    ) {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);

        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * Formats a log record.
     *
     * @param array $record A record to format
     *
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        if ($this->formatLevelMap[$record['level']] && !empty($this->formatLevelMap[$record['level']])) {
            $record['start_tag'] = '<'.$this->formatLevelMap[$record['level']].'>';
            $record['end_tag'] = '</'.$this->formatLevelMap[$record['level']].'>';
        } else {
            $record['start_tag'] = '';
            $record['end_tag'] = '';
        }

        return parent::format($record);
    }
}
