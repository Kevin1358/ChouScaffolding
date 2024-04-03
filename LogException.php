<?php
/**
 * CertiApp standart exception
 */
class LogException extends Exception{
    static $MODE_LOG_ERROR = 1;
    static $MODE_DEFAULT = 0;
    /**
    *   Construct the exception. Note: The message is NOT binary safe.
    *   @param string $message — [optional] The Exception message to throw.
    *   @param int $code — [optional] The Exception code.
    *   @param null|Throwable $previous
    *   [optional] The previous throwable used for the exception chaining.
    */
    public function __construct(string $message = "", int $mode = 0, Throwable|null $previous = null){
        parent::__construct($message,0,$previous);
        if($mode == LogException::$MODE_LOG_ERROR){
            if($previous == null ){
                $logString = date("Y-m-d H:i:s").":".$message."\n".$this->getTraceAsString()."\n";
                error_log($logString, 3, $_SERVER['DOCUMENT_ROOT']."/error_log/error_log.txt");
            } else {
                $logString = date("Y-m-d H:i:s").":".$previous->getMessage()."\n".$previous->getTraceAsString()."\n";
                error_log($logString, 3, $_SERVER['DOCUMENT_ROOT']."/error_log/error_log.txt");
            }
        }
    }
}

?>