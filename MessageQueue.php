<?php
class MessageQueueProducer extends MessageQueueBase{
    public function Produce(string $message):void{
        $insertQuery = "INSERT INTO `$this->Table`(`message`, `status`) VALUES (?,?)";
        $insertStmt = $this->Db->prepare($insertQuery);
        $insertStmt->execute([$message,MessageQueueBase::$STATUS_INIT]);
    }
}
class MessageQueueConsumer extends MessageQueueBase{
    public function Consume():array{
        try{
            $this->Db->beginTransaction();
            $getAvailableQuery = "SELECT `id`,`message`,`status` FROM `message_queue` WHERE `status` = 'INIT' order by id desc limit 1";
            $selectStmt = $this->Db->prepare($getAvailableQuery);
            $selectStmt->execute();
            if($selectStmt->rowCount() == 0){
                throw new NoMesssageException();
            }
            $selectResult = $selectStmt->fetch(PDO::FETCH_ASSOC);
            $this->UpdateStatus($selectResult["message"],MessageQueueBase::$STATUS_CONSUMED);
            $this->Db->commit();
            return $selectResult;
        }catch(MessageQueueException $mex){
            $this->Db->rollBack();
        }catch(Throwable $e){
            $this->Db->rollBack();
            throw $e;
        }
    }
}
interface MessageQueueException{}
class NoMesssageException extends Exception implements MessageQueueException{}
class MessageQueueBase{
    static string $STATUS_INIT = 'INIT';
    static string $STATUS_CONSUMED = 'CONSUMED';
    static string $STATUS_PUKED = 'PUKED';
    protected PDO $Db;
    protected string $Table;
    public function __construct(PDO $db, string $table) {
        $this->Db = $db;
        $this->Table = $table;
    }
    protected function UpdateStatus(int $messageId, string $status){
        $updateStats = "UPDATE `message_queue` SET `status`=? WHERE `id` = ?";
        $updateStmt = $this->Db->prepare($updateStats);
        $updateStmt->execute([$status,$messageId]);
    }
}
?>