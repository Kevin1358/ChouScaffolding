<?php
class ServerSideElementBase{
    protected array $attributes = array();
    function setId($id){
        $this->attributes['id'] = $id;
        return $this;
    }
    function setAttribute(array $attribute){
        $this->attributes = array_merge($this->attributes,$attribute);
        return $this;
    }
    
}
class ServerSideElement extends ServerSideElementBase{
    protected string $HTMLTag;
    function HTMLTag(string $tag):ServerSideElement{
        $this->HTMLTag = $tag;
        return $this;
    }

    function build():void{
        echo "<{$this->HTMLTag}";
        foreach($this->attributes as $attribute => $value){
            echo htmlspecialchars($attribute).'="'.htmlspecialchars($value).'" ';
        }
        echo "></{$this->HTMLTag}"; 
    }
}
class ServerSideInputType{
    static public string $TypeText = "Text";
    static public string $TypePassword = "Password";
    static public string $TypeEmail = "Email";
    static public string $TypeSubmit = "Submit";
}
class ServerSideInputText extends ServerSideElementBase{
    private string $type = "Text";
    private bool $isRequired = false;
    private bool $isReadonly = false;
    function setName(string $name):ServerSideInputText{
        $this->attributes['name'] = $name;
        return $this;
    }

    function setType(string $type):ServerSideInputText{
        $this->type = $type;
        return $this;
    }

    function isRequired():ServerSideInputText{
        $this->isRequired = true;
        return $this;
    }

    function readonly(bool $isReadonly = true):ServerSideInputText{
        $this->isReadonly = $isReadonly;
        return $this;
    }
    
    function getName():string{
        return $this->attributes['name'];
    }

    function build():void{
        echo '<input type="'.$this->type.'" ';
        if(isset($this->attributes['name'])){
            if(isset($_POST[$this->attributes['name']]) && !isset($this->attributes['value'])){
                $this->attributes['value'] = $_POST[$this->attributes['name']];
            }
        }
        foreach($this->attributes as $attribute => $value){
            echo htmlspecialchars($attribute).'="'.htmlspecialchars($value).'" ';
        }
        if($this->isRequired) echo "required ";
        if($this->isReadonly) echo "readonly ";
        
        echo '>'; 
    }
}

enum EventConst{
    case onChange;
    case onSubmit;
}
class EventTargetPair{
    public int $event;
    public string $target;
}

class ServerSideEventHandler{
    private array $eventTargetHandler = array();
    public function __construct() {
        return $this;
    }
    public function bind(ServerSideInputText $target, EventConst $eventConst, $handler):ServerSideEventHandler{
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target->getName();
        $eventTargetPair->event = $eventConst;
        $this->eventTargetHandler[md5(serialize($eventTargetPair))] = $handler;
        switch ($eventConst) {
            case EventConst::onChange:
                $target->setAttribute(["onChange" => "this.form.submit()"]);
                break;
            default:
                break;
        }
        return $this;
    }
    private function onChange($target,$value,$previous):void{
        if(!isset($previous[$target])){
            return;
        }
        if($previous[$target] == $value){
            return;
        }
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target;
        $eventTargetPair->event = EventConst::onChange;
        if(!isset($this->eventTargetHandler[md5(serialize($eventTargetPair))])){
            return;
        }
        $this->eventTargetHandler[md5(serialize($eventTargetPair))]($value);
    }
    private function onSubmit($target,$value) : void {
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target;
        $eventTargetPair->event = EventConst::onSubmit;
        if(!isset($this->eventTargetHandler[md5(serialize($eventTargetPair))])){
            return;
        }
        $this->eventTargetHandler[md5(serialize($eventTargetPair))]($value);
    }
    public function start():void{
        if(isset($_COOKIE['ServerSideEventHandler'])){
            $previous = json_decode($_COOKIE['ServerSideEventHandler'],true);
        }else{
            $previous = array();
        }
        $current = $_POST;
        foreach($current as $target=>$value){
            $this->onChange($target,$value,$previous);
            $this->onSubmit($target,$value);
        }
        setcookie('ServerSideEventHandler',json_encode($_POST));

    }
}
?>