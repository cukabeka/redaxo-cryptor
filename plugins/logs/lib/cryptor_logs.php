<?php

/**
 * Description of cryptor_logs
 *
 * @author Marshall
 */
class cryptor_logs {

    const LOG_FILE_TYPES = ['gz'];

    /**
     * Execute the log file manipulation
     * @return <int>
     */
    public static function executeIpReplacement() {
        
        // Delete old log files
        self::_deleteLogFilesOlderThan(self::getLogFileDeleteMaxAge());
        
        // Loop over log files and process them
        $files = self::getLogFileList(null, self::getLogFileMinAge());
        foreach ($files as $file) {
            self::_processLogFile($file);
        }
        return count($files);
    }

    /**
     * Returns the log file path
     * @return <string>
     */
    public static function getLogFilePath() {
        return trim(self::getConfig('path'));
    }

    /**
     * Returns the log file name part
     * @return <string>
     */
    public static function getLogFileNamePart() {
        return trim(self::getConfig('filename'));
    }

    /**
     * Returns the log file date format pattern
     * @return <string>
     */
    public static function getLogFileDateFormat() {
        return trim(self::getConfig('dateformat'));
    }

    /**
     * Returns the log file path
     * @return <string>
     */
    public static function getLogFileMinAge() {
        return intval(self::getConfig('minage'));
    }

    /**
     * Returns the log file extension
     * @return <string>
     */
    public static function getLogFileExtension() {
        return trim(self::getConfig('extension'));
    }

    /**
     * Returns the log file cryptor suffix
     * @return <string>
     */
    public static function getLogFileCryptorSuffix() {
        return trim(self::getConfig('suffix'));
    }

    /**
     * Returns the entry match pattern
     * @return <string>
     */
    public static function getLogEntryMatchPattern() {
        return trim(self::getConfig('pattern'));
    }

    /**
     * Returns the entry replacement pattern
     * @return <string>
     */
    public static function getLogEntryReplacementPattern() {
        return trim(self::getConfig('replacement'));
    }

    /**
     * Returns true if delete flag is checked
     * @return <boolean>
     */
    public static function getLogFileDelete() {
        return (intval(self::getConfig('delete')) === 1);
    }
    
    /**
     * Returns the max age of a log file
     * @return <string>
     */
    public static function getLogFileDeleteMaxAge() {
        return intval(self::getConfig('delete_maxage'));
    }

    /**
     * Returns the default log path
     * @return <string>
     */
    public static function getDefaultLogPath() {
        return '/' . rex_path::absolute(rex_path::frontend('../')) . '/logs/';
    }

    public static function getConfig($key) {
        return rex_plugin::get('cryptor', 'logs')->getConfig($key);
    }

    /**
     * Validates the configs
     * @return <array>
     */
    public static function validateConfig() {
        $response = [];

        // Log file path
        if (empty(self::getLogFilePath())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_filepath_not_set');
        } else if (!file_exists(self::getLogFilePath())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_filepath_not_exists');
        } else if (rex_dir::isWritable(!self::getLogFilePath())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_filepath_not_writeable');
        }

        // Log file name
        if (empty(self::getLogFileNamePart())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_name_part_not_set');
        }

        // Log date format
        if (!empty(self::getLogFileDateFormat()) && !self::_validateDateFormat(self::getLogFileDateFormat())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_date_format_invalid');
        }

        // Log file extension
        if (empty(self::getLogFileExtension())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_extension_not_set');
        } else if (!self::_validateFileExtension(self::getLogFileExtension())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_extension_not_supported');
        }

        // Log cryptor suffix
        if (empty(self::getLogFileCryptorSuffix())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_suffix_not_set');
        }

        // Log match pattern
        if (empty(self::getLogEntryMatchPattern())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_regex_pattern_not_set');
        } else if (@preg_match(self::getLogEntryMatchPattern(), null) === false) {
            $response[] = rex_i18n::msg('cryptor_logs_error_regex_pattern_invalid');
        }

        // Log replacement pattern
        if (empty(self::getLogEntryReplacementPattern())) {
            $response[] = rex_i18n::msg('cryptor_logs_error_replacement_pattern_not_set');
        }

        return $response;
    }
    
    /**
     * Delete old log files
     * @return <int> $deletedFilesCount
     */
    private static function _deleteLogFilesOlderThan($days = 0) {
        
        // Get max age in days
        if (!$days) {
            return;
        }
        
        // Loop over each log file and verify file date
        $dateNow = new \DateTime();
        foreach (glob(self::getLogFilePath() . self::getLogFileNamePart() . '*.' . self::getLogFileExtension()) as $filepath) {
            $date = self::_getFileDate($filepath);
            if (!$date) {
                continue;
            }
            $dateDiff = $date->diff($dateNow);
            $fileAge = (int) $dateDiff->days;
            if ($days > $fileAge) {
                continue;
            }
            @unlink($filepath);
        }
    }

    /**
     * Returns a file list of log files, which have to be crypted
     * @return <array>
     */
    public static function getLogFileList($flat = false, $minAge = false) {
        $filelist = [];
        if (count(self::validateConfig())) {
            return $filelist;
        }

        $dateNow = new \DateTime();
        $dateFormatPattern = self::getLogFileDateFormat();

        // For example: access.log.(.+[^\.cryptor])\.gz // non processed files!
        $filenamePattern = '/' . self::getLogFileNamePart() . '(.+[^\.' . self::getLogFileCryptorSuffix() . '])\.' . self::getLogFileExtension() . '/i';
        
        // Loop over each log file and check the validity of filename
        foreach (glob(self::getLogFilePath() . self::getLogFileNamePart() . '*.' . self::getLogFileExtension()) as $filepath) {
            $fileInfo = pathinfo($filepath);
            
            if (preg_match($filenamePattern, $fileInfo['basename'])) {
                $fileDate = self::_getFileDate($filepath);
                if (!$fileDate) {
                    continue;
                }

                // Calc the date difference 
                $dateDiff = $fileDate->diff($dateNow);
                $age = (int) $dateDiff->days;
                if ($minAge > 0 && $minAge > $age) {
                    continue;
                }

                // Add it to "flat" collection
                if ($flat === true) {
                    $filelist[] = $fileInfo['basename'] . ' ' . rex_i18n::msg('cryptor_logs_config_logfile_age', $age);
                } 
                // Add it to "full" collection
                else {
                    $fileInfo['age'] = $age;
                    $fileInfo['readpath'] = $filepath;
                    $fileInfo['writepath'] = self::getLogFilePath() . $fileInfo['filename'] . '.' . self::getLogFileCryptorSuffix() . '.' . $fileInfo['extension'];
                    $filelist[$fileInfo['basename']] = $fileInfo;
                }
            }
        }
        ksort($filelist);
        return $filelist;
    }

    /**
     * Returns a file list of given path
     * @param <string> $path
     * @return <array>
     */
    public static function getFileList($path) {
        $filelist = [];
        if (file_exists($path) === TRUE && rex_dir::isWritable($path)) {
            foreach (rex_finder::factory($path) as $file) {
                $fileinfo = pathinfo($file);
                if (!empty($fileinfo['extension'])) {
                    $filelist[] = $fileinfo['basename'];
                }
            }
        }
        sort($filelist);
        return $filelist;
    }
    
    private static function _getFileDate($filepath) {
        $dateFormatPattern = self::getLogFileDateFormat();
        if ($dateFormatPattern) {
            $fileInfo = pathinfo($filepath);
            //access_log_([^\.]+)([\.cryptor]+)?.gz
            $filenamePattern = '/' . self::getLogFileNamePart() . '([^\.]+)([\.' . self::getLogFileCryptorSuffix() . ']+)?.' . self::getLogFileExtension() . '/i';
            $datePart = preg_replace($filenamePattern, '$1', $fileInfo['basename']);
            return DateTime::createFromFormat($dateFormatPattern, $datePart);
        } 

        // Date from creation file date
        else {
            return DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', filemtime($filepath)));
        }
    }

    /**
     * Validates file extension
     * @param <string> $extension
     * @return <boolean>
     */
    private static function _validateFileExtension($extension) {
        return in_array(strtolower($extension), self::LOG_FILE_TYPES);
    }

    /**
     * Validates date format
     * @param <string> $pattern
     * @return <boolean>
     */
    private static function _validateDateFormat($pattern) {
        $date = DateTime::createFromFormat($pattern, date($pattern));
        if ($pattern === $date->format($pattern)) {
            return false;
        }
        return true;
    }

    /**
     * Replace ip addresses within a log file
     * @param <array> $file
     * @return <int> $lineCount
     */
    private static function _processLogFile($file) {
        @unlink($file['writepath']);
        $lineCount = 0;
        $readHandle = gzopen($file['readpath'], 'r');
        $writeHandle = gzopen($file['writepath'], 'w9');
        $matchPattern = self::getLogEntryMatchPattern();
        $replacementPattern = self::getLogEntryReplacementPattern();

        // Loop over the read/write handle and replace ip addresses
        while (!gzeof($readHandle)) {
            $logEntry = gzgets($readHandle, 4096);
            gzwrite($writeHandle, self::_maskIpAddress($logEntry, $matchPattern, $replacementPattern));
            ++$lineCount;
        }
        gzclose($readHandle);
        gzclose($writeHandle);

        // Delete original file after
        if (self::getLogFileDelete()) {
            @unlink($file['readpath']);
        }

        return $lineCount;
    }

    /**
     * Replace the ip address based on given pattern
     * @param <string> $logEntry
     * @return <string>
     */
    private static function _maskIpAddress($logEntry, $matchPattern, $replacementPattern) {
        return preg_replace($matchPattern, $replacementPattern, $logEntry);
    }

}
