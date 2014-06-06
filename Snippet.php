<?php namespace Bliker;

class Snippet
{
    function __construct($class, $method, $params, $comment)
    {
        $this->class = $class;
        $this->method = $method;
        $this->params = $params;
        $comment = preg_replace('/(?<=\n)(\t|(    ))/', '', $comment);
    }

    /**
     * Transform the values into something like this
     * Form::email($param, $another)
     * or
     * Form::email(${1:param}, ${1:another})
     */
    public function full($tabs = false)
    {
        $out = $this->name().'(';
        foreach ($this->params as $i => $param) {
            if ($i != 0) $out .= ', ';

            // So you can jump over properties
            if ($tabs) {
                $out .= '${'.($i+1).':'.$param.'}';
            } else {
                $out .= '$'.$param;
            }
        }
        $out .= ')';

        return $out;
    }

    public function name($delim = '::')
    {
        return $this->class.$delim.$this->method;
    }

    public function filename()
    {
        return $this->name('-').'.sublime-snippet';
    }

    /**
     * Apply snippet template
     * @return string
     */
    public function make()
    {
        return "<snippet>\n".
            "    <content><![CDATA[".$this->full(true)."]]></content>\n".
            "    <tabTrigger>".$this->name('-')."</tabTrigger>\n".
            "    <scope>source.php</scope>\n".
            "    <description>Laravel4</description>\n".
            "</snippet>\n";
    }
}