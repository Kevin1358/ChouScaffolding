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
enum PageTypeConst{
    case Page;
    case Service;
}
class PageTypePair{
    public string $page;
    public PageTypeConst $type;
    public $restrictionFunction;
    public function __construct(string $page, PageTypeConst $type, $restrictionFunction) {
        $this->page = $page;
        $this->type = $type;
        $this->restrictionFunction = $restrictionFunction;
    }
}
interface PageRenderExceptionBase{};
class PageRenderRestrictionException extends Exception implements PageRenderExceptionBase{};
class PageRender{
    public string $pageFolder;
    private array $endpointTargetPair;
    public $body;
    public $header;
    public string $title = "Kamijaga Account";
    public function __construct(string $pageFolder = "/page/") {
        $this->header = function(){?>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
        <?php };
        $this->body = function(){?>
            <body>
                
            </body>
        <?php };
        $this->pageFolder = $pageFolder;
        $this->endpointTargetPair = array();
        return $this;
    }
    public function bind(array $endpoints,string $target, PageTypeConst $type,$restrictionFunction){
        $pageControllerPair = new PageTypePair($target,$type,$restrictionFunction);
        foreach($endpoints as $endpoint){
            $this->endpointTargetPair[$endpoint] = $pageControllerPair;
        }
    }
    public function start(){
        $endpoint = strtolower(explode("?",$_SERVER["REQUEST_URI"])[0]);
        if(isset($this->endpointTargetPair[$endpoint])){
            $pageTypePair = $this->endpointTargetPair[$endpoint];
            if($pageTypePair->type == PageTypeConst::Page){
                try{
                    if($pageTypePair->restrictionFunction != null){
                        if(!($pageTypePair->restrictionFunction)()) throw new PageRenderRestrictionException("Restricted");
                    }
                    $body = function()use($endpoint){
                        require_once $_SERVER["DOCUMENT_ROOT"].$this->pageFolder.$this->endpointTargetPair[$endpoint]->page;
                    };
                    $this->html($body,$this->header);
                }
                catch(PageRenderRestrictionException $pex){
                    http_response_code(401);
                }
                catch(Throwable $ex){
                    http_response_code(500);
                    throw new LogException("Internal Error",LogException::$MODE_LOG_ERROR,$ex);
                }
            }else if($pageTypePair->type == PageTypeConst::Service){
                try{
                    if($pageTypePair->restrictionFunction != null){
                        if(!($pageTypePair->restrictionFunction)()) throw new PageRenderRestrictionException("Restricted");
                    }
                    $body = function()use($endpoint){
                        require_once $_SERVER["DOCUMENT_ROOT"].$this->pageFolder.$this->endpointTargetPair[$endpoint]->page;
                    };
                    $this->service($body);
                    
                }
                catch(PageRenderRestrictionException $pex){
                    http_response_code(401);
                }
                catch(Throwable $ex){
                    http_response_code(500);
                    throw new LogException("Internal Error",LogException::$MODE_LOG_ERROR,$ex);
                }
            }
        }else{
            http_response_code(404);
        }
    }
    
    private function html($body,$header){?>
        <!DOCTYPE html>
        <html lang="en">
        <?php $header()?>
        <?php $body()?>
        </html>
    <?php }
    private function service($body){
        $body();
    }
}
?>