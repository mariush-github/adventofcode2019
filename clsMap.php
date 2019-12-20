<?php

class clsMap {
    private $data;
    private $width;
    private $height;

    private $player_x;
    private $player_y;
    private $player_c;

    private $default;

    private $min;
    private $max;
    private $characters;

    public function __construct($width=1,$height=1, $default = 0,$player_x=-1,$player_y=-1,$player_c='P') {
        if ($width < 1) die('width <1');
        if ($height < 1) die('height <1');
        $this->default = $default;
        $this->width = $width;
        $this->height = $height;
        $this->max = array(0,0);
        $this->min = array($this->width,$this->height);
        $this->characters = array();
        $this->player_x = $player_x;
        $this->player_y = $player_y;
        $this->player_c = $player_c;
        
    }
    public function get($x,$y) {
        return $this->get_any($x,$y,0,$this->default);
    }
    public function set($x,$y,$value) {
        $this->set_any($x,$y,0,$value);
        $this->update_minmax($x,$y);
    }
    public function get_ox($x,$y) {
        return $this->get_any($x,$y,1,0);
    }
    public function set_ox($x,$y,$value) {
        $this->set_any($x,$y,1,$value);
    }

    public function get_any($x,$y,$z,$default) {
        if ($y > $this->height) return $default;
        if ($x > $this->width) return $default;
        $offset = $y*$this->width + $x;
        if (isset($this->data[$offset])==false) return $default;
        if (isset($this->data[$offset][$z])==false) return $default;
        return $this->data[$offset][$z];
    }
    public function set_any($x,$y,$z,$value) {
        if ($y > $this->height) return ;
        if ($x > $this->width) return ;
        $offset = $y*$this->width + $x;
        if (isset($this->data[$offset])==false) $this->data[$offset] = array(); 
        $this->data[$offset][$z] = $value;
        
    }

    public function set_player($x=-1,$y=-1,$char=''){
        $this->player_x = $x;
        $this->player_y = $y;
        if ($char!='') $this->player_c = $char;
        if ($x!=-1 && $y!=-1) $this->update_minmax($x,$y);
    }
    public function resize($width_new,$height_new) {
        $can_resize=false;
        if ($width_new > $this->width || $height_new > $this->height) $can_resize=true;
        if ($can_resize==false) return;
        $buffer = array();
        foreach ($this->data as $offset => $value) {
            $y = intdiv($offset,$this->width);
            $x = $offset % $this->width;
            $this->buffer[$y*$width_new + $x] = $value;
        }
        $this->data = $buffer;
        $this->width = $width_new;
        $this->height = $height_new;
    }
    public function char($id,$value) {
        $this->characters[$id] = $value;
    }
    private function update_minmax($x,$y) {
        if ($x<$this->min[0]) $this->min[0] = $x;
        if ($y<$this->min[1]) $this->min[1] = $y;
        if ($x>$this->max[0]) $this->max[0] = $x;
        if ($y>$this->max[1]) $this->max[1] = $y;

    }
    public function display() {
        
        $corner_topleft = chr(0xC9); 
        $corner_botleft = chr(0xC8);
        $corner_topright = chr(0xBB);
        $corner_botright = chr(0xBC);
        $border_horz = chr(0xCD);
        $border_vert = chr(0xBA);
        $text = '';
        echo "\n";
        $text .= $corner_topleft.str_pad($border_horz,($this->max[0]-$this->min[0]+1),$border_horz).$corner_topright."\n";
        for ($j=$this->min[1];$j<=$this->max[1];$j++) {
            $text.= $border_vert;
            for ($i=$this->min[0];$i<=$this->max[0];$i++) {
                $value = $this->get($i,$j);
                $char = ' ';
                if (isset($this->characters[$value])==true) {
                    $char = $this->characters[$value];
                } else {
                    $char = chr($value);
                }
                if ($i==$this->player_x && $j==$this->player_y) {
                    $text.= $this->player_c;
                } else {
                    $text .= $char;
                }
            }
            $text .= $border_vert."\n";
        }
        
        $text .= $corner_botleft.str_pad($border_horz,($this->max[0]-$this->min[0]+1),$border_horz).$corner_botright."\n";
        echo $text;
        echo "[".$this->min[0].','.$this->min[1].']-['.$this->max[0].','.$this->max[1]."] \n";
        return $text;
    }
}
?>