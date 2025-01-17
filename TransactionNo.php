<?php
namespace ChouScaffolding\TransactionNo;

use ChouScaffolding\Database\Database;
use DateTime;
use DateTimeZone;

Class TransactionNo{
    protected Database $Db;
    protected string $table;
    protected string $SeqNoCode;
    public function __construct(Database $db,string $seqNoCode, string $table) {
        $this->SeqNoCode = $seqNoCode;
        $this->Db = $db;
        $this->table = $table;
    }
    public function GetIncrementTransactionNo(){
        $seqNo = 0;
        $this->Db->transaction(function()use(&$seqNo){
            $seqNo = $this->Db->query("SELECT current_seq_no FROM $this->table WHERE seq_code = ?",[$this->SeqNoCode])->fetch(\PDO::FETCH_ASSOC)["current_seq_no"]+1;
            $this->Db->query("UPDATE $this->table SET current_seq_no = ? where seq_code = ?",[$seqNo,$this->SeqNoCode]);
        });
        $date = new DateTime("now",new DateTimeZone("asia/jakarta"));
        return str_pad($seqNo,6,"0",STR_PAD_LEFT)."/".$this->SeqNoCode."/".str_pad($date->format("n"),2,"0",STR_PAD_LEFT)."/".$date->format("o");
    }
}
?>