<?php

//include '../config/constants.php';

class functions {

    //styles
    public function styles($style_value) {

        $style_array = explode(',', $style_value);
        $css = '';

        foreach ($style_array as $style) {
            $css .= '<link rel="stylesheet" type="text/css" href="' . ASSETS_PATH . $style . '" media="all" />' . "\n\r";
        }
        return $css;
    }

    //js
    public function js($js_value) {
        $js_array = explode(',', $js_value);
        $javascripts = '';
        foreach ($js_array as $js) {
            $javascripts .= '<script type="text/javascript" src="' . ASSETS_PATH . $js . '"></script>' . "\n\r";
        }
        return $javascripts;
    }

}

?>