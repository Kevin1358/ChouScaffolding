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
    case onInit;
}
class EventTargetPair{
    public EventConst $event;
    public string $target;
}

class ServerSideEventHandler{
    private array $eventTargetHandler = array();
    private array $previousTargetValues = array();
    private array $session;
    public function __construct(array &$session) {
        $this->session = &$session;
        return $this;
    }
    /**
     * Bind event handler
     */
    public function bind(ServerSideInputText $target, EventConst $eventConst, $handler):ServerSideEventHandler{
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target->getName();
        $eventTargetPair->event = $eventConst;
        $this->eventTargetHandler[serialize($eventTargetPair)] = $handler;
        switch ($eventConst) {
            case EventConst::onChange:
                $target->setAttribute(["onChange" => "this.form.submit()"]);
                break;
            default:
                break;
        }
        return $this;
    }
    private function onInit(EventTargetPair $eventTargetPair,string $target) : void {
        if($eventTargetPair->event != EventConst::onInit){
            return;
        }
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target;
        $eventTargetPair->event = EventConst::onInit;
        if(!isset($this->eventTargetHandler[serialize($eventTargetPair)])){
            return;
        }
        $this->eventTargetHandler[serialize($eventTargetPair)]();
        unset($this->previousTargetValues[$target]);
    }
    
    private function onChange(string $target,string $value,array $previous):void{
        if(!isset($previous[$target])){
            return;   
        }
        if($previous[$target] == $value){
            return;
        }
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target;
        $eventTargetPair->event = EventConst::onChange;
        if(!isset($this->eventTargetHandler[serialize($eventTargetPair)])){
            return;
        }
        $this->eventTargetHandler[serialize($eventTargetPair)]();
    }
    private function onSubmit(string $target,string $value) : void {
        $eventTargetPair = new EventTargetPair(); 
        $eventTargetPair->target = $target;
        $eventTargetPair->event = EventConst::onSubmit;
        if(!isset($this->eventTargetHandler[serialize($eventTargetPair)])){
            return;
        }
        $this->eventTargetHandler[serialize($eventTargetPair)]($value);
    }
    /**
     * Start event handler
     */
    public function start():void{
        $current = $_POST;
        if(isset($this->session['ServerSideEventHandler'])){
            $this->previousTargetValues = json_decode($this->session['ServerSideEventHandler'],true);
            foreach($current as $target=>$value){
                $this->onChange($target,$value,$this->previousTargetValues);
                $this->onSubmit($target,$value);
                $this->previousTargetValues[$target] = $value;
            }
        }else{
            foreach($this->eventTargetHandler as $eventTargetPair=>$handler){
                $eventTargetPair = unserialize($eventTargetPair,["allowed_classes"=>["EventTargetPair"]]);
                $target = $eventTargetPair->target;
                $this->previousTargetValues[$target] = "";
                $this->onInit($eventTargetPair,$target);
            }
            
        }
        $this->session['ServerSideEventHandler'] = json_encode($this->previousTargetValues);

    }
    /**
     * Clear all value form current form
     */
    public static function clearValue():void{
        $previous = json_decode($_SESSION['ServerSideEventHandler'],true);
        foreach($previous as $key){
            unset($_POST[$key]);
        }
    }
}
?>