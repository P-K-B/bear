<?php

trait Functions
{
    public function GetClan($id)
    {
        $i=0;
        foreach ($this->Clans as $clan) {
            if ($clan->id==$id) {
                $ret=&$this->Clans[$i];
                return $ret;
            }
            $i++;
        }
        return null;
    }

    public function UpdateList()
    {
        $this->EmptyList();
        foreach ($this->Fights as $fight) {
            if ($fight->in_progress !=0) {
                $this->InsertIntoList($this->GetClan($fight->attacker_id));
                $this->InsertIntoList($this->GetClan($fight->defender_id));
            }
        }
    }

    public function InsertIntoList($clan)
    {
        $was=0;
        foreach ($this->Update_list as $clans) {
            if ($clans->id==$clan->id) {
                $was=1;
            }
        }
        if ($was!=1) {
            array_push($this->Update_list, null);
            $this->Update_list[count($this->Update_list)-1]=$clan;
        }
    }

    public function EmptyList()
    {
        $this->Update_list=array();
    }

    public function UpdateClans()
    {
        $data=GetClans();
        if ($data) {
            // print_r($data);
            foreach ($data as $key) {
                $clan_tmp=new Clan($key['id'], $key['title']);
                $was=0;
                foreach ($this->Clans as $key => $clan) {
                    if ($clan->id==$clan_tmp->id) {
                        $was=1;
                        if ($clan->name != $clan_tmp->name) {
                            $this->Clans[$key]->name=$clan_tmp->name;
                        }
                    }
                }
                if ($was!=1) {
                    array_push($this->Clans, $clan_tmp);
                }
            }
            // print_r($this->Clans);
        // $fight=new Fight();
        } else {
            return ERROR;
        }
    }

    public function GetClanId($name)
    {
        // echo count($this->Clans);
        foreach ($this->Clans as $clan) {
            if ($clan->name==$name) {
                return $clan->id;
            }
        }
        return null;
    }

    public function UpdateFights()
    {
        foreach ($this->Fights as $key=>$fight) {
            if ($fight->for_delete==1) {
                // Final Update
                // $this->for_delete=1;
                $this->Match2($fight);
                unset($this->Fights[$key]);
                sort($this->Fights);
            }
        }

        $data=GetFights();
        $new_fights=array();
        // print_r($data);
        if (count($data) != 0) {
            foreach ($data as $dt) {
                $fight_tmp=new Fight(null, $this->GetClanId($dt['attacker']), $this->GetClanId($dt['defender']), strtotime($dt['declared']), strtotime($dt['resolved']), 1);
                // print_r($fight_tmp);
                array_push($new_fights, $fight_tmp);
            }
        }
        // print_r($new_fights);
        foreach ($this->Fights as $fight) {
            $fight->updated=0;
        }
        foreach ($new_fights as $fight_tmp) {
            $was=0;
            foreach ($this->Fights as $fight) {
                // $fight->updated=0;
                if (($fight->attacker_id==$fight_tmp->attacker_id) && ($fight->defender_id==$fight_tmp->defender_id) &&($fight->declared==$fight_tmp->declared)&&($fight->resolved==$fight_tmp->resolved)) {
                    $was=1;
                    $fight->updated=1;
                }
            }
            // echo "was->$was\n";
            if ($was!=1) {
                $fight_tmp->id=$this->attack_id++;
                $this->fights_played++;
                array_push($this->Fights, $fight_tmp);
            }
        }
        // print_r($this->Fights);
        foreach ($this->Fights as $key=>$fight) {
            if ($fight->updated==0) {
                // Final Update
                $fight->for_delete=1;
                // $this->Match2($fight);
                // unset($this->Fights[$key]);
                // sort($this->Fights);
            }
        }
    }

    public function UpdatePlayers()
    {
        foreach ($this->Clans as $clan) {
            $data=GetClanData($clan->id);
            foreach ($data["players"] as $player_tmp) {
                $pl=new Player($player_tmp["id"], $player_tmp["nick"], $player_tmp["frags"], $player_tmp["deaths"], $player_tmp["level"], $clan->id);
                $was=0;
                foreach ($clan->players as $player) {
                    if ($player->id==$pl->id) {
                        $was=1;
                        $player->Update($pl);
                    }
                }
                if ($was !=1) {
                    array_push($clan->players, $pl);
                }
            }
        }
    }

    public function GetVars($id)
    {

        // 1 найти все бои с этим кланом
        $found_fights=array();
        $ret=array();

        foreach ($this->Fights as $fight) {
            if ($fight->in_progress) {
                if (($fight->attacker_id == $id) || ($fight->defender_id==$id)) {
                    array_push($found_fights, $fight);
                    if ($fight->attacker_id==$id) {
                        array_push($ret, $fight->defender_id);
                    } else {
                        array_push($ret, $fight->attacker_id);
                    }
                }
            }
        }
        return $ret;
    }


    public function EasyMatch($fight)
    {
        // echo "EasyMatch!\n";
        $this->debug_info=array();
        $debug_killers=array();
        $debug_deads=array();
        $attacker_clan=$this->GetClan($fight->attacker_id);
        $defender_clan=$this->GetClan($fight->defender_id);
        // attacker match
        $killers=array();
        $deads=array();
        array_push($killers, 1);
        while (count($killers)!=count($deads)) {
            $attacker_clan->UpdatePlayers();
            $defender_clan->UpdatePlayers();
            $killers=$attacker_clan->killers;
            $deads=$defender_clan->deads;
        }
        // print_r($attacker_clan->killers);
        // print_r($defender_clan->deads);
        foreach ($attacker_clan->killers as $value) {
            array_push($debug_killers, $value);
        }
        foreach ($defender_clan->deads as $value) {
            array_push($debug_deads, $value);
        }
        if ((count($killers)==count($deads))&&(count($killers)>=1)) {
            if (count($killers)==1) {
                // echo "Lets MATCH!\n";
                $this->total++;
                array_push($fight->log, "{$attacker_clan->killers[0]->nick} killed {$defender_clan->deads[0]->nick}");
                unset($attacker_clan->killers[0]);
                sort($attacker_clan->killers);
                if (count($attacker_clan->killers)==0) {
                    $attacker_clan->killers=array();
                }
                unset($defender_clan->deads[0]);
                sort($defender_clan->deads);
                if (count($defender_clan->deads)==0) {
                    $defender_clan->deads=array();
                }
            } else {
                // ticket
                $this->total+=count($killers);
                array_push($fight->tickets, new Ticket(null, $attacker_clan->killers, $defender_clan->deads));
                $defender_clan->deads=array();
                $attacker_clan->killers=array();
            }
        }

        // defender match
        $killers=array();
        $deads=array();
        array_push($killers, 1);
        while (count($killers)!=count($deads)) {
            $attacker_clan->UpdatePlayers();
            $defender_clan->UpdatePlayers();
            $killers=$defender_clan->killers;
            $deads=$attacker_clan->deads;
        }
        // print_r($defender_clan->killers);
        // print_r($attacker_clan->deads);
        foreach ($defender_clan->killers as $value) {
            array_push($debug_killers, $value);
        }
        foreach ($attacker_clan->deads as $value) {
            array_push($debug_deads, $value);
        }
        array_push($this->debug_info, $debug_killers);
        array_push($this->debug_info, $debug_deads);
        if ((count($killers)==count($deads))&&(count($killers)>=1)) {
            if (count($killers)==1) {
                // echo "Lets MATCH!\n";
                $this->total++;
                array_push($fight->log, "{$defender_clan->killers[0]->nick} killed {$attacker_clan->deads[0]->nick}");
                unset($defender_clan->killers[0]);
                sort($defender_clan->killers);
                if (count($defender_clan->killers)==0) {
                    $defender_clan->killers=array();
                }
                unset($attacker_clan->deads[0]);
                sort($attacker_clan->deads);
                if (count($attacker_clan->deads)==0) {
                    $attacker_clan->deads=array();
                }
            } else {
                // ticket
                $this->total+=count($killers);
                array_push($fight->tickets, new Ticket(null, $defender_clan->killers, $attacker_clan->deads));
                $attacker_clan->deads=array();
                $defender_clan->killers=array();
            }
        }


        // проверяем данные
        $logs=FightLog("{$fight->attacker_id} VS {$fight->defender_id} at {$fight->resolved}");
        if (count($fight->log)>>0) {
            foreach ($fight->log as $key=>$log1) {
                foreach ($logs as $log2) {
                    if ($log1==$log2) {
                        $fight->log[$key]=$log1." [OK]";
                    }
                }
            }
        }
    }

    public function Match2()
    {
        foreach ($this->Fights as $fight) {
            if ($fight->in_progress==1) {
                // echo "$fight->attacker_id VS $fight->defender_id -> ";
                $vars1=$this->GetVars($fight->attacker_id);
                $vars2=$this->GetVars($fight->defender_id);
                if ((count($vars1)==1) && (count($vars2)==1)) {
                    if (($vars1[0]==$fight->defender_id)&&($vars2[0]==$fight->attacker_id)) {
                        // echo "easy fight\n";
                        $this->EasyMatch($fight);
                    }
                } else {
                    // echo "hard fight\n";
                }
            }
        }
    }

    // public function Match()
    // {
    //     retry:
    //     // for ($j=0;$j<count($this->Fights);$j++) {
    //     //     // echo "I am here1!\n";
    //     $kil=0;
    //     $ded=0;
    //     foreach ($this->Fights as $fight) {
    //         if ($fight->in_progress != 0) {
    //             // echo "I am here!\n";
    //             $attacker_clan=$this->GetClan($fight->attacker_id);
    //             $defender_clan=$this->GetClan($fight->defender_id);
    //             $kil+=count($attacker_clan->killers);
    //             $ded+=count($defender_clan->deads);
    //         }
    //     }
    //     // echo "kil->$kil and ded->$ded\n";
    //     if ($kil!=$ded) {
    //         $attacker_clan->UpdatePlayers();
    //         $defender_clan->UpdatePlayers();
    //         // echo "Updating!\n";
    //         goto retry;
    //         throw new Exception('Данные не совпадают! '.$kil.' and '.$ded);
    //     }
    //     foreach ($this->Fights as $fight) {
    //         if ($fight->in_progress != 0) {
    //             // echo "I am here!\n";
    //             $attacker_clan=$this->GetClan($fight->attacker_id);
    //             $defender_clan=$this->GetClan($fight->defender_id);
    //             // step1
    //             $kil=count($attacker_clan->killers);
    //             $ded=count($defender_clan->deads);
    //             if ($kil<=$ded) {
    //                 $min=$kil;
    //             } else {
    //                 $min=$ded;
    //             }
    //             for ($i=$min-1;$i>=0;$i--) {
    //                 fwrite($this->log, $attacker_clan->killers[count($attacker_clan->killers)-1]->nick." killed ".$defender_clan->deads[count($defender_clan->deads)-1]->nick."\n");
    //                 $this->Log($attacker_clan->killers[count($attacker_clan->killers)-1]->nick." killed ".$defender_clan->deads[count($defender_clan->deads)-1]->nick, null, $fight->attacker_id." VS ".$fight->defender_id);
    //                 array_splice($attacker_clan->killers, count($attacker_clan->killers)-1, 1);
    //                 array_splice($defender_clan->deads, count($defender_clan->deads)-1, 1);
    //             }
    //
    //             // step2
    //             $kil=count($defender_clan->killers);
    //             $ded=count($attacker_clan->deads);
    //             if ($kil<=$ded) {
    //                 $min=$kil;
    //             } else {
    //                 $min=$ded;
    //             }
    //             for ($i=$min-1;$i>=0;$i--) {
    //                 fwrite($this->log, $defender_clan->killers[count($defender_clan->killers)-1]->nick." killed ".$attacker_clan->deads[count($attacker_clan->deads)-1]->nick."\n");
    //                 $this->Log($defender_clan->killers[count($defender_clan->killers)-1]->nick." killed ".$attacker_clan->deads[count($attacker_clan->deads)-1]->nick, null, $fight->attacker_id." VS ".$fight->defender_id);
    //                 array_splice($defender_clan->killers, count($defender_clan->killers)-1, 1);
    //                 array_splice($attacker_clan->deads, count($attacker_clan->deads)-1, 1);
    //             }
    //         }
    //     }
    // }
    public function Sleepp() // Перевести сервер в спящий режим на вермя (время берется из config)
    {
        if ($this->config["analyzer_sleep_time"]!=0) {
            sleep($this->config["analyzer_sleep_time"]);
        } else {
            $this->Pause();
        }
    }
    public function Pause()
    {
        // echo "\nAre you sure you want to do this?  Type 'yes' to continue: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line)) {
            // echo "ABORTING!\n";
            exit;
        }
        fclose($handle);
        // echo "\n";
        // echo "Thank you, continuing...\n";
    }
}
