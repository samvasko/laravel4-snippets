<?php namespace Bliker;

class Snippet
{
    function __construct($class, $method, $params)
    {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
    }

    /**
     * Transform the values into something like this
     * Form::email($param, $another)
     * @return string [description]
     */
    public function __toString()
    {
        $out = $this->class.'::'.$this->method.'(';
        foreach ($this->params as $i => $param) {
            if ($i != 0) $out .= ', ';
            $out .= '${'.($i+1).':'.$param.'}';
        }
        $out .= ')';

        return $out;
    }

    public function filename()
    {
        return $this->class.'-'.$this->method.'.sublime-snippet';
    }

    /**
     * Apply snippet template
     * @return string
     */
    public function make()
    {
        return "<snippet>\n".
            "    <content><![CDATA[".$this."]]></content>\n".
            "    <tabTrigger>".$this->class.'-'.$this->method."</tabTrigger>\n".
            "    <scope>source.php</scope>\n".
            "</snippet>\n";
    }
}