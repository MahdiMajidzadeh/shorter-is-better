<?php

namespace App\Http\Webhooks\State;

use App\Http\Webhooks\StateManager;

class Stat extends StateManager
{
    public function handle($step, $text = null)
    {
//        if ($step == 1) {
//
//        } elseif ($step == 2) {
//            $this->inputs['url'] = $text;
//            if($this->isUrl($text)) {
//                $this->chat->message('send your key')->send();
//            }
//        } elseif ($step == 3) {
//            $this->inputs['key'] = $text;
//            $this->_makeShort($this->inputs['url'], $this->inputs['key']); // get for send message
//            $this->lastStep = true;
//        }
    }
}
