<?php
class ServerSideType{
    static public string $TypeText = "Text";
    static public string $TypePassword = "Password";
    static public string $TypeEmail = "Email";
}
class ServerSideInputText{
    private $attributes = array();
    private string $type = "Text";
    private bool $isRequired = false;
    private bool $isReadonly = false;
    function setId($id){
        $this->attributes['id'] = $id;
        return $this;
    }
    function setName(string $name){
        $this->attributes['name'] = $name;
        return $this;
    }
    function setType(string $type){
        $this->type = $type;
        return $this;
    }
    function isRequired(){
        $this->isRequired = true;
        return $this;
    }
    function readonly(){
        $this->isReadonly = true;
        return $this;
    }
    function setAttribute(array $attribute){
        $this->attributes = array_merge($this->attributes,$attribute);
        return $this;
    }
    function build(){
        echo '<input type="'.$this->type.'" ';
        if(isset($this->attributes['name'])){
            if(isset($_POST[$this->attributes['name']])){
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
?>