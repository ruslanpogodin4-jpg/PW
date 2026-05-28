<?php

namespace system\libs\struct;
if (!defined('IWEB')) { die("Error!"); }
class roleLevelUp{

    public $hp;
    public $mp;
    public $up_hp;
    public $up_mp;
    public $up_dmg;
    public $up_magic;
    
    function classConfig($class_id){
        switch ($class_id){
            case 0:	// Воин
                $this->hp = 15;
                $this->mp = 9;
                $this->up_hp = 30;
                $this->up_mp = 18;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 1:	// Маг
                $this->hp = 10;
                $this->mp = 14;
                $this->up_hp = 20;
                $this->up_mp = 28;
                $this->up_dmg = 0.2;
                $this->up_magic = 1;
                break;
            case 2:	// Шаман
                $this->hp = 10;
                $this->mp = 14;
                $this->up_hp = 20;
                $this->up_mp = 28;
                $this->up_dmg = 0.2;
                $this->up_magic = 1;
                break;
            case 3:	// Друид
                $this->hp = 12;
                $this->mp = 12;
                $this->up_hp = 24;
                $this->up_mp = 24;
                $this->up_dmg = 0.6;
                $this->up_magic = 0.6;
                break;
            case 4:	// Оборотень
                $this->hp = 17;
                $this->mp = 7;
                $this->up_hp = 34;
                $this->up_mp = 14;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 5:	// Убийца
                $this->hp = 13;
                $this->mp = 10;
                $this->up_hp = 26;
                $this->up_mp = 22;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 6:	// Лучник
                $this->hp = 13;
                $this->mp = 11;
                $this->up_hp = 26;
                $this->up_mp = 22;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 7:	// Жрец
                $this->hp = 10;
                $this->mp = 14;
                $this->up_hp = 20;
                $this->up_mp = 28;
                $this->up_dmg = 0.2;
                $this->up_magic = 1;
                break;
            case 8:	// Страж
                $this->hp = 15;
                $this->mp = 9;
                $this->up_hp = 30;
                $this->up_mp = 18;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 9:	// Мистик
                $this->hp = 10;
                $this->mp = 14;
                $this->up_hp = 20;
                $this->up_mp = 28;
                $this->up_dmg = 0.2;
                $this->up_magic = 1;
                break;
            case 10: // Призрак
                $this->hp = 13;
                $this->mp = 11;
                $this->up_hp = 26;
                $this->up_mp = 22;
                $this->up_dmg = 1;
                $this->up_magic = 0;
                break;
            case 11: // Жнец
                $this->hp = 10;
                $this->mp = 14;
                $this->up_hp = 20;
                $this->up_mp = 28;
                $this->up_dmg = 0.2;
                $this->up_magic = 1;
                break;
        }
        return $this;
    }


    function levelProperty($cls, $level, &$role)
    {
        $clsConf = $this->classConfig($cls);
        $role['max_hp']['value'] = ($level - 1) * $clsConf->up_hp + $role['vitality']['value'] * $clsConf->hp;
        $role['max_mp']['value'] = ($level - 1) * $clsConf->up_mp + $role['energy']['value'] * $clsConf->mp;
        $role['damage_low']['value'] = 1 + floor(($level - 1) * $clsConf->up_dmg);
        $role['damage_high']['value'] = $role['damage_low']['value'];
        $role['damage_magic_low']['value'] = 1 + floor(($level - 1) * $clsConf->up_magic);
        $role['damage_magic_high']['value'] = $role['damage_magic_low']['value'];
    }

}