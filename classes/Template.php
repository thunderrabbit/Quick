<?php

class Template{
    protected $template_location;

    protected $vars;

    protected $mla_request;      // Encapsulates superglobals e.g. $SESSION, $REQUEST, etc (misspelled in this comment to keep searches clean)
    protected $di_dbase;

    public function __construct(\Mlaphp\Request $mla_request, \Database\Database $dbase) {

        $this->mla_request = $mla_request;
        $this->di_dbase = $dbase;

        $this->vars = array();
    }

    public function setTemplate($template_file) {
        $this->template_location = "/home/quill_dh_plasz3gi/quill.plasticaddy.com/templates/".$template_file;
    }

    /**
     * Set a template variable.
     */
    public function set(string $name, string $value) {
        $this->vars[$name] = $value;
    }

    public function echoToScreen() {
        echo $this->loadTemplate();// Return the contents
    }

     protected function loadTemplate() {
        $charEncode = "UTF-8";
        extract($this->vars);          	// Extract the vars to local namespace

        ob_start();                    	// Start output buffering

        if(!isset($this->template_location)) {
            return "No template file provided";
        }

        include($this->template_location);	// Include the file

        $ob_result = ob_get_clean();


        return $ob_result;
    }
}
